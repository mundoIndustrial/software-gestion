/**
 * DataReloadService
 * 
 * Responsabilidad: Orquestar el reload de datos después de cambios de procesos
 * 
 * SRP: Una sola razón para cambiar — lógica de qué datos recargar post-operación
 * DIP: Depende de abstracciones inyectadas (OrderApiService, OrderState, renderers)
 * 
 * Parámetros inyectados:
 * - orderApiService: Servicio de API
 * - orderState: Estado global de la orden
 * - renderers: Funciones de renderización (opcional)
 */

export class DataReloadService {
  constructor(orderApiService, orderState, renderers = {}) {
    this.orderApiService = orderApiService;
    this.orderState = orderState;
    this.renderers = renderers;
  }

  /**
   * Actualizar callbacks de renderizado sin reinstanciar el servicio.
   *
   * @param {object} renderers
   */
  setRenderers(renderers = {}) {
    this.renderers = renderers;
  }

  /**
   * Recargar datos después de eliminar un proceso
   * 
   * @param {object} context - { orderId, prendaId, areaName }
   * @returns {Promise<void>}
   */
  async reloadAfterDelete(context = {}) {
    try {
      const orderId = context.orderId || this.orderState.getOrderId();
      if (!orderId) {
        return; // No se puede recargar sin ID de orden
      }

      // 1. Recargar prendas con tracking y sincronizar estado enriquecido
      await this._reloadTrackingState(orderId);

      // 2. Refrescar datos específicos de la prenda actual (si estamos en recibos-costura)
      if (window.location.pathname.includes('/recibos-costura')) {
        const prendaId = context.prendaId || this.orderState.getCurrentPrenda()?.id;
        if (prendaId) {
          try {
            const data = await this.orderApiService.loadConsecutivoCostura(orderId, prendaId);
            this.orderState.setConsecutivoCosturaData(data);
          } catch (e) {
            // Silenciosamente ignorar si no se puede refrescar consecutivo
          }
        }
      }

      // 3. Actualizar prenda actual si hay cambios
      await this._updateCurrentPrenda();

      // 4. Re-renderizar timeline si hay renderer disponible
      if (this.renderers.renderPrendaTrackingTimeline && this.orderState.getCurrentPrenda()) {
        this.renderers.renderPrendaTrackingTimeline(this.orderState.getCurrentPrenda());
      }

    } catch (error) {
      console.error('[DataReloadService.reloadAfterDelete] Error:', error);
      throw error;
    }
  }

  /**
   * Recargar datos después de agregar o actualizar un proceso
   * 
   * @param {object} context - { orderId, prendaId }
   * @returns {Promise<void>}
   */
  async reloadAfterSave(context = {}) {
    try {
      const orderId = context.orderId || this.orderState.getOrderId();
      if (!orderId) {
        return;
      }

      // 1. Recargar prendas y sincronizar estado enriquecido
      await this._reloadTrackingState(orderId);

      // 2. Actualizar prenda actual
      await this._updateCurrentPrenda();

      // 3. Refrescar table si es necesario
      if (this.renderers.actualizarAreaEnTablaRecibos) {
        await this.renderers.actualizarAreaEnTablaRecibos();
      }

      // 4. Re-renderizar timeline
      if (this.renderers.renderPrendaTrackingTimeline && this.orderState.getCurrentPrenda()) {
        this.renderers.renderPrendaTrackingTimeline(this.orderState.getCurrentPrenda());
      }

    } catch (error) {
      console.error('[DataReloadService.reloadAfterSave] Error:', error);
      throw error;
    }
  }

  /**
   * Actualizar la prenda actual con datos recargados
   * @private
   */
  async _updateCurrentPrenda() {
    const currentPrendaId = this.orderState.getCurrentPrenda()?.id;
    if (!currentPrendaId || !this.orderState.hasPrendas()) {
      return;
    }

    const prendaActualizada = this.orderState.getPrendas().find(
      p => String(p.id) === String(currentPrendaId)
    );

    if (prendaActualizada) {
      this.orderState.setCurrentPrenda(prendaActualizada);
    }
  }

  /**
   * Recargar estado de tracking desde backend y actualizar OrderState.
   * @private
   */
  async _reloadTrackingState(orderId) {
    const { prendas, areasConfig, pedido } = await this.orderApiService.loadPrendasWithTracking(orderId);

    this.orderState.setPrendas(prendas);
    this.orderState.setAreasConfig(areasConfig);

    const order = this.orderState.getOrder();
    if (order && pedido?.recibo_principal) {
      order.recibo_principal = pedido.recibo_principal;
    }
  }

  /**
   * Obtener prenda para renderizar (fallback a primera si no existe actual)
   */
  async _getPrendaForRender() {
    let prendaParaRender = this.orderState.getCurrentPrenda();

    if (!prendaParaRender && this.orderState.hasPrendas()) {
      // Fallback a primera prenda
      const prendas = this.orderState.getPrendas();
      if (prendas.length > 0) {
        prendaParaRender = prendas[0];
        this.orderState.setCurrentPrenda(prendaParaRender);
      }
    }

    return prendaParaRender;
  }
}
