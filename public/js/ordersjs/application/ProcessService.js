/**
 * ProcessService
 * 
 * Responsabilidad: Orquestar operaciones de procesos
 * 
 * SRP: Una sola razón para cambiar — lógica de orquestación de procesos
 * 
 * Parámetros inyectados:
 * - processDeleteService: Servicio de eliminación
 * - formValidationService: Servicio de validación
 * - formStateManager: Gestor de estado del formulario
 * - dataReloadService: Servicio de recarga de datos
 * - orderApiService: Servicio de API
 * - orderState: Estado global
 * - uiFeedbackService: Servicio de feedback (éxito/error)
 */

export class ProcessService {
  constructor(
    processDeleteService,
    formValidationService,
    formStateManager,
    dataReloadService,
    orderApiService,
    orderState,
    uiFeedbackService
  ) {
    this.processDeleteService = processDeleteService;
    this.formValidationService = formValidationService;
    this.formStateManager = formStateManager;
    this.dataReloadService = dataReloadService;
    this.orderApiService = orderApiService;
    this.orderState = orderState;
    this.uiFeedbackService = uiFeedbackService;
  }

  /**
   * Iniciar creación de nuevo proceso en un área
   * @param {string} areaName
   * @param {string} encargadoPrefill - Encargado prefillado (opcional)
   */
  initiateCreate(areaName, encargadoPrefill = '') {
    this.formStateManager.openForAdd();
    this.formStateManager.setValues({
      area: areaName,
      encargado: encargadoPrefill
    });
    return this.formStateManager.getState();
  }

  /**
   * Iniciar edición de proceso existente
   * @param {string} procesoId
   * @param {object} processData - Datos actuales del proceso
   */
  initiateEdit(procesoId, processData) {
    this.formStateManager.openForEdit(procesoId, processData);
    return this.formStateManager.getState();
  }

  /**
   * Cerrar formulario
   */
  closeForm() {
    this.formStateManager.close();
  }

  /**
   * Validar datos del formulario antes de guardar
   * @param {object} formData
   * @returns {object} { valid: boolean, errors: [] }
   */
  validateFormData(formData) {
    return this.formValidationService.validateAll(formData);
  }

  /**
   * Guardar proceso (crear o actualizar)
   * @param {object} processData - { area, estado, fechaInicio, encargado, observaciones }
   * @returns {Promise<object>}
   */
  async saveProcess(processData) {
    try {
      // 1. Validar datos
      const validation = this.formValidationService.validateAll(processData);
      if (!validation.valid) {
        const errorMessage = this.formValidationService.getErrorMessage(validation);
        this.uiFeedbackService.showError(errorMessage);
        return { success: false, errors: validation.errors };
      }

      // 2. Determinar si es crear o actualizar
      const isEdit = this.formStateManager.isEditing();
      const procesoId = this.formStateManager.editingProcessId;

      let result;
      if (isEdit && procesoId) {
        // 3a. Actualizar proceso existente
        result = await this.orderApiService.updateProceso(procesoId, processData);
      } else {
        // 3b. Crear nuevo proceso
        result = await this.orderApiService.saveProceso(processData);
      }

      // 4. Recargar datos post-guardado
      const orderId = this.orderState.getOrderId();
      const prendaId = this.orderState.getCurrentPrenda()?.id;
      await this.dataReloadService.reloadAfterSave({ orderId, prendaId });

      // 5. Cerrar formulario
      this.formStateManager.close();

      // 6. Feedback positivo
      const message = isEdit ? 'Proceso actualizado correctamente' : 'Proceso creado correctamente';
      this.uiFeedbackService.showSuccess(message);

      return { success: true, data: result };

    } catch (error) {
      this.uiFeedbackService.showError(`Error al guardar proceso: ${error.message}`);
      return { success: false, error };
    }
  }

  /**
   * Eliminar proceso
   * @param {string} procesoId
   * @param {object} context - { areaName, orderId, prendaId }
   * @returns {Promise<object>}
   */
  async deleteProcess(procesoId, context = {}) {
    try {
      // Validar ID
      if (!procesoId) {
        throw new Error('ID de proceso requerido');
      }

      // Delegar a ProcessDeleteService
      const result = await this.processDeleteService.execute(procesoId, context);

      return { success: true, data: result };

    } catch (error) {
      return { success: false, error };
    }
  }

  /**
   * Obtener estado actual del formulario
   */
  getFormState() {
    return this.formStateManager.getState();
  }

  /**
   * Obtener estado del botón (para actualizar UI)
   */
  getButtonState() {
    return this.formStateManager.getButtonState();
  }
}
