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

  // ────── Lógica de dominio: Recibo Principal ──────

  /**
   * Orden de prioridad para buscar el recibo principal
   */
  static PRIORIDAD_RECIBOS = ['COSTURA', 'REFLECTIVO', 'ESTAMPADO', 'BORDADO', 'DTF', 'SUBLIMADO'];

  /**
   * Buscar el recibo principal de la orden según prioridad de tipo.
   * Busca entre todas las prendas el primer recibo activo del tipo con mayor prioridad.
   * 
   * @returns {String} Texto del recibo (ej: "COSTURA #3") o '-'
   */
  getReciboPrincipal() {
    const prendas = this.order?.prendas;
    if (!prendas || prendas.length === 0) return '-';

    for (const prenda of prendas) {
      if (!prenda.consecutivos || typeof prenda.consecutivos !== 'object') continue;

      const recibos = Object.entries(prenda.consecutivos)
        .filter(([, datos]) => datos !== null && datos !== undefined)
        .map(([tipo, datos]) => ({
          tipo_recibo: tipo,
          consecutivo_actual: datos.consecutivo_actual || datos,
          activo: datos.activo !== undefined ? datos.activo : 1
        }));

      for (const prioridad of OrderState.PRIORIDAD_RECIBOS) {
        const encontrado = recibos.find(r => r.activo === 1 && r.tipo_recibo === prioridad);
        if (encontrado) {
          return `${encontrado.tipo_recibo} #${encontrado.consecutivo_actual}`;
        }
      }
    }

    return '-';
  }

  // ────── Lógica de dominio: Resolución de Área y Recibo ──────

  /**
   * Resolver el área actual de una prenda.
   * Cadena de prioridad: último proceso > área de prenda > área del pedido.
   * @param {Object} prenda
   * @param {Object} [options] - { excludeOrderArea: boolean }
   * @returns {String} Área resuelta o '-'
   */
  resolveAreaActual(prenda, options = {}) {
    if (prenda?.ultimo_proceso_area) return prenda.ultimo_proceso_area;
    if (prenda?.area && String(prenda.area).trim() !== '') return prenda.area;
    if (!options.excludeOrderArea && this.order?.area && String(this.order.area).trim() !== '') return this.order.area;
    return '-';
  }

  /**
   * Resolver el texto de recibo para una prenda individual.
   * Busca en consecutivos por prioridad de tipo (COSTURA primero).
   * @param {Object} prenda
   * @returns {String} Texto del recibo (ej: "COSTURA #3") o "Sin recibo"
   */
  resolveReciboForPrenda(prenda) {
    const consecutivos = this.#normalizeConsecutivos(prenda?.consecutivos);

    if (consecutivos.length > 0) {
      const costuraActivo = consecutivos.find(r =>
        String(r.tipo_recibo || '').toUpperCase() === 'COSTURA' && (r.activo === 1 || r.activo === true)
      );
      const activo = costuraActivo || consecutivos.find(r => r.activo === 1 || r.activo === true);
      if (activo) return `${activo.tipo_recibo} #${activo.consecutivo_actual}`;
      if (consecutivos[0]) return `${consecutivos[0].tipo_recibo} #${consecutivos[0].consecutivo_actual}`;
    }

    if (prenda?.ultimo_recibo_numero && prenda.ultimo_recibo_numero !== '-') {
      return `Recibo #${prenda.ultimo_recibo_numero}`;
    }

    return 'Sin recibo';
  }

  /**
   * Normalizar consecutivos (array u objeto indexado → array)
   * @private
   */
  #normalizeConsecutivos(consecutivos) {
    if (!consecutivos) return [];
    if (Array.isArray(consecutivos)) return consecutivos;
    if (typeof consecutivos === 'object') {
      try { return Object.values(consecutivos).filter(Boolean); } catch { return []; }
    }
    return [];
  }

  /**
   * Resolver características y reglas de un área.
   * @param {String} area - Nombre del área (ej: "CORTE", "INSUMOS")
   * @returns {Object} Metadata con propiedades booleanas
   */
  resolveAreaMetadata(area) {
    const areaLower = String(area || '').toLowerCase();
    const isInsumos = areaLower === 'insumos';
    const isCorte = areaLower.includes('corte');
    const isCostura = areaLower.includes('costura');
    const isControlCalidad = areaLower.includes('control') && areaLower.includes('calidad');
    const needsEncargado = isCorte || isCostura || isControlCalidad;
    
    return {
      isInsumos,
      isCorte,
      isCostura,
      isControlCalidad,
      needsEncargado,
      shouldHideEncargado: isInsumos || !needsEncargado
    };
  }

  /**
   * Determinar el estado visible y si está activo para mostrar en card.
   * @param {Object} data - Datos del proceso
   * @param {Object} metadata - Resultado de resolveAreaMetadata()
   * @returns {Object} { estadoDisplay, estaActivoDisplay }
   */
  resolveAreaStatus(data, metadata) {
    const hasFechaCompletado = !metadata.isInsumos && Boolean(data.fecha_completado);
    return {
      estadoDisplay: metadata.isInsumos ? (data.estado || 'Pendiente') : (hasFechaCompletado ? 'Completado' : 'Pendiente'),
      estaActivoDisplay: metadata.isInsumos ? Boolean(data.esta_activo) : !hasFechaCompletado
    };
  }

  /**
   * Buscar el recibo activo de una prenda (preferencia: COSTURA > cualquier activo > primero).
   * @param {Object} prenda
   * @returns {Object|null} El recibo activo o null
   */
  findActiveRecibo(prenda) {
    const consecutivos = this.#normalizeConsecutivos(prenda?.consecutivos);
    if (consecutivos.length === 0) return null;

    const reciboCosturaActivo = consecutivos.find(r =>
      String(r.tipo_recibo || '').toUpperCase() === 'COSTURA' && (r.activo === 1 || r.activo === true)
    );
    return reciboCosturaActivo || consecutivos.find(r => r.activo === 1 || r.activo === true) || consecutivos[0] || null;
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

    // Área: resolver con cadena de prioridad
    const area = this.resolveAreaActual(prenda) || '-';

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
