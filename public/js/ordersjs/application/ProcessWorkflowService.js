/**
 * PROCESS WORKFLOW SERVICE
 * 
 * Responsabilidad: Orquestar el flujo completo de agregar/editar un proceso
 * Divide handleAgregarProceso en pasos reutilizables
 * 
 * ANTES: Una función monolítica de ~120 líneas
 * DESPUÉS: Pasos pequeños y reutilizables
 * 
 * Arquitectura: DIP + Facade pattern
 */

export class ProcessWorkflowService {
  /**
   * @param {Object} dependencies - Servicios requeridos
   */
  constructor(dependencies = {}) {
    this.orderApiService = dependencies.orderApiService;
    this.orderState = dependencies.orderState;
    this.formManager = dependencies.formManager;
    this.areasConfigService = dependencies.areasConfigService;
    this.uiFeedbackService = dependencies.uiFeedbackService;
    this.logger = dependencies.logger || console;
  }

  /**
   * PASO 1: Validar que el formulario esté completo
   * @returns {Object} { isValid: boolean, errors: string[] }
   */
  validateFormData() {
    const errors = [];

    // Validar que hay área seleccionada
    const elements = this.formManager.getElements();
    if (!elements.area?.value?.trim()) {
      errors.push('El área/proceso es obligatorio');
    }

    // Validar encargado para áreas que lo requieren
    const area = elements.area?.value || '';
    if (this.areasConfigService.requiresEncargado(area)) {
      const encargado = this.formManager.getEncargadoValue();
      if (!encargado?.trim()) {
        errors.push('El encargado es obligatorio para esta área');
      }
    }

    // Validar que hay una prenda y pedido cargado
    if (!this.orderState.getCurrentPrenda()) {
      errors.push('No hay datos de la prenda');
    }

    if (!this.orderState.getOrder()) {
      errors.push('No hay datos del pedido');
    }

    return {
      isValid: errors.length === 0,
      errors
    };
  }

  /**
   * PASO 2: Preparar datos del proceso para enviar a API
   * @returns {Object} Datos preparados o null si falta info
   */
  prepareProcessData() {
    try {
      const encargado = this.formManager.getEncargadoValue();
      const data = this.formManager.collectData(encargado);

      const currentPrenda = this.orderState.getCurrentPrenda();
      const currentOrder = this.orderState.getOrder();

      if (!currentOrder?.numero_pedido) {
        throw new Error('No hay número de pedido');
      }

      return {
        pedido_produccion_id: currentOrder.numero_pedido,
        prenda_id: currentPrenda.id,
        area: data.area,
        encargado: data.encargado,
        estado: data.estado || 'Pendiente',
        fecha_inicio: data.fecha_inicio,
        observaciones: data.observaciones
      };
    } catch (error) {
      this.logger.error('[ProcessWorkflowService.prepareProcessData] Error:', error);
      throw error;
    }
  }

  /**
   * PASO 3: Guardar el proceso en la API
   * @param {Object} processData - Datos preparados del proceso
   * @returns {Promise<Object>} Respuesta de la API
   */
  async saveProcessToAPI(processData) {
    try {
      const currentPrenda = this.orderState.getCurrentPrenda();

      this.logger.log('[ProcessWorkflowService.saveProcessToAPI] Guardando:', {
        prenda_id: currentPrenda.id,
        area: processData.area
      });

      const result = await this.orderApiService.saveProceso(
        currentPrenda.id,
        processData
      );

      return result;
    } catch (error) {
      this.logger.error('[ProcessWorkflowService.saveProcessToAPI] Error:', error);
      throw error;
    }
  }

  /**
   * PASO 4: Recargar datos después de guardar
   * @param {Object} result - Resultado del API
   * @returns {Promise<void>}
   */
  async reloadDataAfterSave(result) {
    try {
      const orderId = this.orderState.getOrderId();

      // Si vinieron datos de la prenda en la respuesta, usar esos
      if (result?.data?.prenda) {
        this.orderState.setCurrentPrenda(result.data.prenda);
        this.logger.log('[ProcessWorkflowService.reloadDataAfterSave] Prenda actualizada desde respuesta');
        return;
      }

      // Si no, recargar completo
      this.logger.log('[ProcessWorkflowService.reloadDataAfterSave] Recargando datos completos');
      
      const { prendas } = await this.orderApiService.loadPrendasWithTracking(orderId);
      
      // Actualizar prendas en estado
      this.orderState.setPrendas(prendas);
      
      // Buscar y actualizar la prenda actual
      const currentPrendaId = this.orderState.getCurrentPrenda()?.id;
      const updatedPrenda = prendas.find(p => String(p.id) === String(currentPrendaId));
      
      if (updatedPrenda) {
        this.orderState.setCurrentPrenda(updatedPrenda);
      }
    } catch (error) {
      this.logger.error('[ProcessWorkflowService.reloadDataAfterSave] Error:', error);
      throw error;
    }
  }

  /**
   * PASO 5: Mostrar feedback al usuario
   * @param {Object} result - Resultado de la operación
   */
  showFeedback(result) {
    const mensaje = result?.action === 'actualizado'
      ? 'Proceso actualizado correctamente'
      : 'Proceso agregado correctamente';

    if (this.uiFeedbackService?.showSuccess) {
      this.uiFeedbackService.showSuccess(mensaje);
    }
  }

  /**
   * Ejecutar flujo completo de agregar/editar proceso
   * @param {Object} callbacks - Callbacks para UI (closeModal, render, etc)
   * @returns {Promise<Object>} Resultado de la operación
   */
  async executeCompleteWorkflow(callbacks = {}) {
    const {
      onValidationError = null,
      onBeforeSave = null,
      onAfterSave = null,
      onComplete = null,
      onError = null
    } = callbacks;

    try {
      // PASO 1: Validar
      const validation = this.validateFormData();
      if (!validation.isValid) {
        const errorMsg = validation.errors.join(' | ');
        if (this.uiFeedbackService?.showError) {
          this.uiFeedbackService.showError(errorMsg);
        }
        if (onValidationError) onValidationError(validation.errors);
        return { success: false, errors: validation.errors };
      }

      if (onBeforeSave) onBeforeSave();

      // PASO 2: Preparar datos
      const processData = this.prepareProcessData();

      // PASO 3: Guardar
      const result = await this.saveProcessToAPI(processData);

      if (onAfterSave) onAfterSave(result);

      // PASO 4: Recargar
      await this.reloadDataAfterSave(result);

      // PASO 5: Feedback
      this.showFeedback(result);

      if (onComplete) onComplete(result);

      return { success: true, result };
    } catch (error) {
      this.logger.error('[ProcessWorkflowService.executeCompleteWorkflow] Error:', error);
      
      if (this.uiFeedbackService?.showError) {
        this.uiFeedbackService.showError(
          'Error al guardar proceso: ' + error.message
        );
      }

      if (onError) onError(error);

      return { success: false, error };
    }
  }

  /**
   * Preparar formulario para editar un proceso existente
   * @param {Object} processData - Datos del proceso a editar
   */
  async prepareForEdit(processData) {
    try {
      this.orderState.setEditingProcessId(processData.id);
      
      // Establecer datos en el formulario
      this.formManager.setData(processData);
      
      // Si el área requiere selector dinámico, dispara el evento change
      // para que se cree el selector automáticamente
      const elements = this.formManager.getElements();
      if (elements.area) {
        const changeEvent = new Event('change', { bubbles: true });
        elements.area.dispatchEvent(changeEvent);
        
        // Esperar a que se cree el selector dinámicamente
        await new Promise(resolve => setTimeout(resolve, 150));
      }

      // Establecer encargado después de que se cree el campo
      this.formManager.setEncargadoValue(processData.encargado);

      return true;
    } catch (error) {
      this.logger.error('[ProcessWorkflowService.prepareForEdit] Error:', error);
      return false;
    }
  }

  /**
   * Resetear el flujo a estado inicial (para nuevo proceso)
   */
  resetWorkflow() {
    this.formManager.clear();
    this.orderState.setEditingProcessId(null);
    return true;
  }
}

export default ProcessWorkflowService;
