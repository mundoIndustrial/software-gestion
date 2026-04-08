/**
 * OrderLoaderService - Load order data and garments with tracking
 * 
 * Responsibility: Encapsulate all API calls and order state updates
 * for loading complete order data with garments and areas configuration.
 * 
 * Usage:
 *   const loader = new OrderLoaderService({
 *     orderApiService,
 *     orderState,
 *     onOrderLoaded: (orderData) => { ... },
 *     onPrendasLoaded: (prendas) => { ... }
 *   });
 *   await loader.loadCompleteOrder(orderId);
 */

class OrderLoaderService {
  constructor(options = {}) {
    this.orderApiService = options.orderApiService;
    this.orderState = options.orderState;
    this.onOrderLoaded = options.onOrderLoaded || (() => {});
    this.onPrendasLoaded = options.onPrendasLoaded || (() => {});
    this.onError = options.onError || (() => {});
  }

  /**
   * Load complete order data (order basics + prendas + areas)
   * 
   * @param {number} orderId - Order ID to load
   * @returns {Promise<{success: boolean, order: object, prendas: array}>}
   */
  async loadCompleteOrder(orderId) {
    try {
      console.log('[OrderLoaderService] Iniciando carga de orden:', orderId);
      
      // Ejecutar ambas consultas en paralelo para reducir latencia total.
      const [orderData, prendasData] = await Promise.all([
        this.loadOrderBasicData(orderId),
        this.loadPrendasWithTracking(orderId)
      ]);

      return {
        success: true,
        order: orderData,
        prendas: prendasData.prendas
      };

    } catch (error) {
      console.error('[OrderLoaderService] Error completo:', error);
      this.onError(error);
      throw error;
    }
  }

  /**
   * Load basic order data and update state
   * 
   * @private
   * @param {number} orderId - Order ID
   * @returns {Promise<object>}
   */
  async loadOrderBasicData(orderId) {
    try {
      console.log('[OrderLoaderService.loadOrderBasicData] Cargando datos básicos');

      // Application Layer: OrderApiService maneja la API
      const data = await this.orderApiService.loadOrderData(orderId);

      // Domain Layer: Guardar en estado centralizado
      this.orderState.setOrder(data);

      // Trigger callback for UI update
      this.onOrderLoaded(data);

      return data;

    } catch (error) {
      console.error('[OrderLoaderService.loadOrderBasicData] Error:', error);
      throw error;
    }
  }

  /**
   * Load prendas with tracking and areas configuration
   * 
   * @private
   * @param {number} orderId - Order ID
   * @returns {Promise<{prendas: array, areasConfig: object, pedido: object}>}
   */
  async loadPrendasWithTracking(orderId) {
    try {
      console.log('[OrderLoaderService.loadPrendasWithTracking] Cargando prendas');

      // Application Layer: OrderApiService maneja la API
      const { prendas, areasConfig, pedido } = await this.orderApiService.loadPrendasWithTracking(orderId);

      // Domain Layer: Guardar en estado centralizado
      this.orderState.setPrendas(prendas);
      this.orderState.setAreasConfig(areasConfig);

      // Enriquecer orden con recibo_principal del endpoint de seguimiento
      const order = this.orderState.getOrder();
      if (order && pedido?.recibo_principal) {
        order.recibo_principal = pedido.recibo_principal;
        // Refrescar cabecera cuando ya conocemos el recibo principal
        this.onOrderLoaded(order);
      }

      // Trigger callback for UI update (rendering)
      this.onPrendasLoaded(prendas);

      return { prendas, areasConfig, pedido };

    } catch (error) {
      console.error('[OrderLoaderService.loadPrendasWithTracking] Error:', error);
      throw error;
    }
  }

  /**
   * Reload prendas after a process is saved
   * 
   * @param {number} orderId - Order ID to reload
   * @returns {Promise<array>}
   */
  async reloadPrendas(orderId) {
    try {
      console.log('[OrderLoaderService.reloadPrendas] Reloading prendas');

      const { prendas } = await this.orderApiService.loadPrendasWithTracking(orderId);

      // Update state
      this.orderState.setPrendas(prendas);

      // Trigger callback
      this.onPrendasLoaded(prendas);

      return prendas;

    } catch (error) {
      console.error('[OrderLoaderService.reloadPrendas] Error:', error);
      throw error;
    }
  }
}

export { OrderLoaderService };
