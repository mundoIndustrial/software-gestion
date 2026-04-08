/**
 * Domain Entity: OrderState
 * 
 * Refleja la entidad "Order" del backend (DDD pattern aplicado a frontend).
 * Centraliza todo el estado de una orden, eliminando variables globales.
 * 
 * Responsabilidades:
 * - Mantener el estado de una orden y sus prendas
 * - Validaciones básicas de dominio
 * - Acceso a datos sin side effects
 * 
 * No tiene:
 * - Lógica de presentación
 * - Llamadas a API directo
 * - Manipulación de DOM
 */

class OrderState {
  constructor() {
    // Entidad raíz: Order
    this.order = null;
    
    // Agregado: Prendas (pertenecen a Order)
    this.prendas = [];
    
    // Prenda actualmente seleccionada en el modal
    this.currentPrenda = null;
    
    // Valor seleccionado: días estimados de entrega
    this.selectedDays = null;

    // Estado de edición de procesos
    this.editingProcessId = null;
    this.processToDelete = null;

    // Datos de consecutivo-costura (contexto de recibos)
    this.consecutivoCosturaData = null;

    // Configuración de áreas desde backend
    this.areasConfig = null;
  }

  /**
   * Establecer la orden actual
   * @param {Object} orderData - Datos de la orden desde el backend
   * @throws {Error} Si orderData no es válido
   */
  setOrder(orderData) {
    if (!orderData) {
      throw new Error('OrderState: orderData no puede ser null');
    }
    
    if (!orderData.id) {
      throw new Error('OrderState: orderData debe tener una propiedad "id"');
    }

    this.order = orderData;
    console.log('[OrderState] Orden establecida:', {
      id: orderData.id,
      numero_pedido: orderData.numero_pedido,
      cliente: orderData.cliente
    });
  }

  /**
   * Obtener la orden actual
   * @returns {Object|null} Los datos de la orden o null
   */
  getOrder() {
    return this.order;
  }

  /**
   * Verificar si hay una orden cargada
   * @returns {Boolean}
   */
  hasOrder() {
    return this.order !== null;
  }

  /**
   * Obtener el ID de la orden
   * @returns {Number|null} ID de la orden o null
   */
  getOrderId() {
    return this.order?.id || null;
  }

  /**
   * Establecer las prendas de la orden actual
   * @param {Array} prendasData - Array de prendas desde el backend
   * @throws {Error} Si prendasData no es un array
   */
  setPrendas(prendasData) {
    if (!Array.isArray(prendasData)) {
      throw new Error('OrderState: prendasData debe ser un array');
    }

    this.prendas = prendasData;
    console.log('[OrderState] Prendas establecidas:', prendasData.length);
  }

  /**
   * Obtener todas las prendas
   * @returns {Array} Array de prendas
   */
  getPrendas() {
    return this.prendas;
  }

  /**
   * Obtener prenda por ID
   * @param {Number} prendasId - ID de la prenda
   * @returns {Object|null} La prenda o null
   */
  getPrendaById(prendasId) {
    return this.prendas.find(p => p.id === prendasId) || null;
  }

  /**
   * Verificar si hay prendas cargadas
   * @returns {Boolean}
   */
  hasPrendas() {
    return this.prendas.length > 0;
  }

  /**
   * Establecer días estimados seleccionados
   * @param {Number|null} days - Número de días o null
   */
  setSelectedDays(days) {
    if (days !== null && (typeof days !== 'number' || days < 0)) {
      throw new Error('OrderState: selectedDays debe ser un número positivo o null');
    }

    this.selectedDays = days;
    console.log('[OrderState] Días seleccionados:', days);
  }

  /**
   * Obtener días seleccionados
   * @returns {Number|null}
   */
  getSelectedDays() {
    return this.selectedDays;
  }

  /**
   * Verificar si hay días seleccionados
   * @returns {Boolean}
   */
  hasSelectedDays() {
    return this.selectedDays !== null;
  }

  // ────── Prenda actual ──────

  setCurrentPrenda(prendaData) {
    this.currentPrenda = prendaData;
  }

  getCurrentPrenda() {
    return this.currentPrenda;
  }

  hasCurrentPrenda() {
    return this.currentPrenda !== null;
  }

  /**
   * Buscar prenda actual en la lista de prendas cargadas y actualizarla
   */
  refreshCurrentPrenda() {
    if (!this.currentPrenda || this.prendas.length === 0) return null;
    const updated = this.prendas.find(p => String(p.id) === String(this.currentPrenda.id));
    if (updated) {
      this.currentPrenda = updated;
    }
    return updated;
  }

  // ────── Edición de procesos ──────

  setEditingProcessId(processId) {
    this.editingProcessId = processId;
  }

  getEditingProcessId() {
    return this.editingProcessId;
  }

  isEditing() {
    return this.editingProcessId !== null;
  }

  setProcessToDelete(processInfo) {
    this.processToDelete = processInfo;
  }

  getProcessToDelete() {
    return this.processToDelete;
  }

  clearProcessToDelete() {
    this.processToDelete = null;
  }

  // ────── Consecutivo Costura ──────

  setConsecutivoCosturaData(data) {
    this.consecutivoCosturaData = data;
  }

  getConsecutivoCosturaData() {
    return this.consecutivoCosturaData;
  }

  // ────── Configuración de Áreas (desde backend) ──────

  setAreasConfig(config) {
    this.areasConfig = config;
  }

  getAreasConfig() {
    return this.areasConfig;
  }

  /**
   * Preparar datos de una prenda para renderizar en tabla de prendas.
   * Centraliza la lógica de formateo y decisiones de presentación.
   * @param {Object} prenda
   * @returns {Object} Datos formateados para renderer de tabla
   */
  preparePrendaTableData(prenda) {
    const nombrePrenda = prenda.nombre_prenda || `Prenda ${prenda.id}`;
    const cantidad = prenda.cantidad || 0;

    // Procesos: preferir tipos_recibo_procesos, fallback a procesos genéricos
    let procesosInfo = '-';
    if (prenda.tipos_recibo_procesos?.length > 0) {
      procesosInfo = prenda.tipos_recibo_procesos
        .map(p => `${p.nombre || 'Proceso'} (${(p.estado || 'PENDIENTE').replace(/_/g, ' ')})`)
        .join(', ');
    } else if (prenda.procesos?.length > 0) {
      procesosInfo = prenda.procesos
        .map(p => `${p.tipo_proceso?.nombre || 'Proceso'} (${(p.estado || 'PENDIENTE').replace(/_/g, ' ')})`)
        .join(', ');
    }

    // Área: usar el valor pre-computado por el backend
    const area = prenda.area_actual || '-';

    // Estado: usar del orden (no de procesos)
    const estadoPedido = this.order?.estado || 'Sin estado';
    const estadoDisplay = estadoPedido.replace(/_/g, ' ').toUpperCase();

    // Bodega: determinar si es de bodega
    const esDeBodega = Boolean(prenda.de_bodega);

    return {
      nombrePrenda,
      cantidad,
      procesosInfo,
      area,
      estadoPedido,
      estadoDisplay,
      esDeBodega
    };
  }

  /**
   * Preparar datos de una prenda para renderizar como card.
   * @param {Object} prenda
   * @returns {Object} Datos formateados { cantidad, procesos[], bodega }
   */
  preparePrendaCardData(prenda) {
    const cantidad = prenda.cantidad || 0;

    // Procesos: acceder correctamente a tipo_proceso (JS chain-safe)
    let procesos = [];
    if (prenda.procesos?.length > 0) {
      procesos = prenda.procesos.map(p => ({
        nombre: p.tipo_proceso?.nombre || 'Proceso',
        estado: p.estado || 'PENDIENTE'
      }));
    }

    return {
      cantidad,
      procesos,
      bodega: Boolean(prenda.de_bodega)
    };
  }

  /**
   * Limpiar todo el estado
   * Se llama cuando se cierra el modal
   */
  clear() {
    this.order = null;
    this.prendas = [];
    this.currentPrenda = null;
    this.selectedDays = null;
    this.editingProcessId = null;
    this.processToDelete = null;
    this.consecutivoCosturaData = null;
    this.areasConfig = null;
    console.log('[OrderState] Estado limpiado');
  }

  /**
   * Obtener snapshot del estado completo (para debugging)
   * @returns {Object} Estado actual
   */
  getSnapshot() {
    return {
      order: this.order ? {
        id: this.order.id,
        numero_pedido: this.order.numero_pedido,
        cliente: this.order.cliente,
        estado: this.order.estado
      } : null,
      prendasCount: this.prendas.length,
      currentPrendaId: this.currentPrenda?.id || null,
      selectedDays: this.selectedDays,
      editingProcessId: this.editingProcessId,
      processToDelete: this.processToDelete,
      hasConsecutivoCostura: this.consecutivoCosturaData !== null
    };
  }
}

/**
 * Singleton: instancia única del estado de la orden
 * Se usa en toda la aplicación para acceder al estado
 */
const orderState = new OrderState();

export { OrderState, orderState };
export default orderState;
