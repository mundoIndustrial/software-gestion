/**
 * ProcessDeleteService
 * 
 * Responsabilidad: Orquestar la eliminación de un proceso
 * 
 * SRP: Una sola razón para cambiar — lógica de eliminación de procesos
 * DIP: Depende de abstracciones (inyectadas), no de implementaciones concretas
 * 
 * Parámetros inyectados:
 * - processRepository: Abstracción de API (actualizada por OrderApiService)
 * - dataReloadService: Maneja recarga de datos post-operación
 * - uiFeedbackService: Maneja mensajes de éxito/error
 * - modalService: Maneja apertura/cierre de modales (opcional)
 */

export class ProcessDeleteService {
  constructor(processRepository, dataReloadService, uiFeedbackService, modalService = null) {
    this.processRepository = processRepository;
    this.dataReloadService = dataReloadService;
    this.uiFeedbackService = uiFeedbackService;
    this.modalService = modalService;
  }

  /**
   * Ejecutar eliminación de proceso
   * 
   * @param {string} procesoId - ID del proceso a eliminar
   * @param {object} context - Contexto (optBoolean{orderId, prendaId, areaName})
   * @returns {Promise<object>} - Resultado de la operación
   * 
   * Responsabilidades:
   * 1. Llamar al repositorio para eliminar
   * 2. Delegar el reload de datos (SRP)
   * 3. Delegar feedback al usuario (SRP)
   * 4. Cerrar modalsi está disponible
   */
  async execute(procesoId, context = {}) {
    try {
      // 1. Eliminar proceso via repositorio (abstracción)
      const result = await this.processRepository.delete(procesoId);

      // 2. Cerrar modal de confirmación (si disponible)
      if (this.modalService) {
        await this.modalService.closeConfirmDeleteModal();
      }

      // 3. Delegar reload de datos (responsabilidad de DataReloadService)
      await this.dataReloadService.reloadAfterDelete(context);

      // 4. Mostrar feedback positivo
      this.uiFeedbackService.showSuccess('Proceso eliminado correctamente');

      return {
        success: true,
        message: 'Proceso eliminado exitosamente',
        data: result
      };

    } catch (error) {
      // 5. Mostrar feedback negativo
      this.uiFeedbackService.showError(`Error al eliminar proceso: ${error.message}`);

      // 6. No cerrar modal en caso de error (usuario puede reintentar)
      throw error;
    }
  }

  /**
   * Validar que el proceso pueda ser eliminado
   * 
   * @param {string} procesoId
   * @returns {Promise<boolean>}
   */
  async canDelete(procesoId) {
    // Validación del lado del cliente (ej: permisos)
    // La validación del servidor ocurre en processRepository
    if (!procesoId) {
      throw new Error('ID de proceso requerido');
    }
    return true;
  }
}
