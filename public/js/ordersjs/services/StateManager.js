/**
 * State Manager - Gestión centralizada del estado de la aplicación
 * 
 * ARQUITECTURA: State Management Pattern
 * - Responsabilidad única: gestionar estado global
 * - SRP (Single Responsibility Principle) ✓
 * - Evita variables globales anárquicas ✓
 * - Permite tracking de cambios de estado
 * - Facilita debugging
 * 
 * USO:
 * StateManager.setOrder(orderData);
 * const order = StateManager.getOrder();
 * StateManager.subscribe('order', callback);
 */

const StateManager = {
  /**
   * Estado interno de la aplicación
   * @private
   */
  _state: {
    currentOrder: null,
    currentPrenda: null,
    currentConsecutivoCostura: null,
    prendasList: [],
    editingProcessId: null,
    isLoading: false
  },

  /**
   * Observadores de cambios de estado
   * @private
   */
  _observers: {},

  /**
   * Inicializar StateManager y crear referencias en window para compatibilidad
   */
  init: function() {
    console.log('[StateManager] Inicializando...');
    
    // Proxies para compatibilidad con código antiguo que usa window.currentOrderData
    Object.defineProperty(window, 'currentOrderData', {
      get: () => this._state.currentOrder,
      set: (value) => this.setOrder(value)
    });

    Object.defineProperty(window, 'currentPrendaData', {
      get: () => this._state.currentPrenda,
      set: (value) => this.setPrenda(value)
    });

    Object.defineProperty(window, 'currentConsecutivoCosturaData', {
      get: () => this._state.currentConsecutivoCostura,
      set: (value) => this.setConsecutivoCostura(value)
    });

    Object.defineProperty(window, 'prendasData', {
      get: () => this._state.prendasList,
      set: (value) => this.setPrendasList(value)
    });

    Object.defineProperty(window, 'editingProcessId', {
      get: () => this._state.editingProcessId,
      set: (value) => this.setEditingProcessId(value)
    });

    console.log('[StateManager] Inicializado correctamente');
  },

  /**
   * Obtener el estado completo (solo lectura)
   * @returns {Object} Copia del estado interno
   */
  getState: function() {
    return JSON.parse(JSON.stringify(this._state));
  },

  /**
   * Setear orden/pedido
   * @param {Object} orderData - Datos de la orden
   */
  setOrder: function(orderData) {
    this._state.currentOrder = orderData;
    this._notify('order', orderData);
    console.log('[StateManager] Orden seteada:', orderData?.numero_pedido);
  },

  /**
   * Obtener orden actual
   * @returns {Object|null} Datos de la orden o null
   */
  getOrder: function() {
    return this._state.currentOrder;
  },

  /**
   * Setear prenda actual
   * @param {Object} prendaData - Datos de la prenda
   */
  setPrenda: function(prendaData) {
    this._state.currentPrenda = prendaData;
    this._notify('prenda', prendaData);
    console.log('[StateManager] Prenda seteada:', prendaData?.id);
  },

  /**
   * Obtener prenda actual
   * @returns {Object|null} Datos de la prenda o null
   */
  getPrenda: function() {
    return this._state.currentPrenda;
  },

  /**
   * Setear datos de consecutivo de costura
   * @param {Object} data - Datos de costura
   */
  setConsecutivoCostura: function(data) {
    this._state.currentConsecutivoCostura = data;
    this._notify('consecutivoCostura', data);
    console.log('[StateManager] ConsecutivoCostura seteado');
  },

  /**
   * Obtener datos de consecutivo de costura
   * @returns {Object|null}
   */
  getConsecutivoCostura: function() {
    return this._state.currentConsecutivoCostura;
  },

  /**
   * Setear lista de prendas
   * @param {Array} prendasList - Array de prendas
   */
  setPrendasList: function(prendasList) {
    this._state.prendasList = prendasList || [];
    this._notify('prendasList', prendasList);
    console.log('[StateManager] Lista de prendas seteada:', prendasList?.length, 'prendas');
  },

  /**
   * Obtener lista de prendas
   * @returns {Array} Lista de prendas
   */
  getPrendasList: function() {
    return this._state.prendasList;
  },

  /**
   * Obtener prenda por ID
   * @param {number} prendaId - ID de la prenda
   * @returns {Object|null} Prenda encontrada o null
   */
  getPrendaById: function(prendaId) {
    return this._state.prendasList.find(p => p.id == prendaId) || null;
  },

  /**
   * Setear ID del proceso en edición
   * @param {number|null} processId - ID del proceso o null
   */
  setEditingProcessId: function(processId) {
    this._state.editingProcessId = processId;
    this._notify('editingProcessId', processId);
    if (processId) {
      console.log('[StateManager] Editando proceso:', processId);
    } else {
      console.log('[StateManager] Modo: agregar nuevo proceso');
    }
  },

  /**
   * Obtener ID del proceso en edición
   * @returns {number|null}
   */
  getEditingProcessId: function() {
    return this._state.editingProcessId;
  },

  /**
   * ¿Hay un proceso en edición?
   * @returns {boolean}
   */
  isEditing: function() {
    return this._state.editingProcessId !== null;
  },

  /**
   * Setear estado de carga global
   * @param {boolean} isLoading
   */
  setIsLoading: function(isLoading) {
    this._state.isLoading = isLoading;
    this._notify('isLoading', isLoading);
  },

  /**
   * Obtener estado de carga global
   * @returns {boolean}
   */
  getIsLoading: function() {
    return this._state.isLoading;
  },

  /**
   * Resetear todo el estado
   */
  reset: function() {
    this._state = {
      currentOrder: null,
      currentPrenda: null,
      currentConsecutivoCostura: null,
      prendasList: [],
      editingProcessId: null,
      isLoading: false
    };
    this._notify('reset', null);
    console.log('[StateManager] Estado reseteado');
  },

  /**
   * Resetear solo la prenda actual
   */
  resetPrenda: function() {
    this._state.currentPrenda = null;
    this._state.currentConsecutivoCostura = null;
    this._notify('prenda', null);
    console.log('[StateManager] Prenda reseteada');
  },

  /**
   * Validar que hay orden actual
   * @returns {boolean}
   */
  hasOrder: function() {
    return !!(this._state.currentOrder && this._state.currentOrder.numero_pedido);
  },

  /**
   * Validar que hay prenda actual
   * @returns {boolean}
   */
  hasPrenda: function() {
    return !!(this._state.currentPrenda && this._state.currentPrenda.id);
  },

  /**
   * Suscribirse a cambios de estado
   * @param {string} key - Clave de estado a monitorear
   * @param {Function} callback - Función a ejecutar cuando cambie
   * @returns {Function} Función para desuscribirse
   * @example
   * const unsubscribe = StateManager.subscribe('order', (newOrder) => {
   *   console.log('Orden cambió:', newOrder);
   * });
   * // Más tarde...
   * unsubscribe();
   */
  subscribe: function(key, callback) {
    if (!this._observers[key]) {
      this._observers[key] = [];
    }
    this._observers[key].push(callback);

    // Retornar función de desuscripción
    return () => {
      this._observers[key] = this._observers[key].filter(cb => cb !== callback);
    };
  },

  /**
   * Notificar a todos los observadores de un cambio
   * @private
   */
  _notify: function(key, value) {
    if (this._observers[key]) {
      this._observers[key].forEach(callback => {
        try {
          callback(value);
        } catch (error) {
          console.error(`[StateManager] Error en observer de '${key}':`, error);
        }
      });
    }
  },

  /**
   * Obtener estadísticas del estado actual
   * @returns {Object} Información sobre el estado
   */
  getStats: function() {
    return {
      hasOrder: this.hasOrder(),
      hasPrenda: this.hasPrenda(),
      prendasCount: this._state.prendasList.length,
      isEditing: this.isEditing(),
      isLoading: this._state.isLoading,
      orderNumber: this._state.currentOrder?.numero_pedido || null,
      prendaId: this._state.currentPrenda?.id || null
    };
  }
};

// Exportar para uso global
if (typeof window !== 'undefined') {
  window.StateManager = StateManager;
  
  // Inicializar automáticamente cuando se carga el script
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => StateManager.init());
  } else {
    StateManager.init();
  }
}
