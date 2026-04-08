'use strict';

// Gestión de carga de datos desde API
class TrackingDataLoader {
  constructor() {
    this.init();
  }

  init() {
    // Precargar festivos para mejorar rendimiento
    if (typeof precargarFestivos === 'function') {
      precargarFestivos();
    }
  }

  // Abrir selector de prendas (overlay)
  async openOrderTracking(orderId, mostrarSelector = true) {
    try {
      console.log('[openOrderTracking] Abriendo selector de prendas para orden:', orderId, 'mostrarSelector:', mostrarSelector);
      
      // Cargar datos básicos del pedido
      await this.loadOrderBasicData(orderId);
      
      // Cargar prendas con seguimiento
      await this.loadPrendasWithTracking(orderId);
      
      // Mostrar overlay de prendas solo si se solicita
      if (mostrarSelector) {
        if (typeof showPrendasSelector === 'function') {
          showPrendasSelector();
        }
      }
      
      console.log('[openOrderTracking] Datos cargados correctamente. currentOrderData:', window.currentOrderData);
      
    } catch (error) {
      console.error('[openOrderTracking] Error:', error);
      if (typeof showError === 'function') {
        showError('Error al cargar datos de seguimiento');
      }
    }
  }

  // Compatibilidad con implementación vieja (tracking-modal-script.blade.php)
  mostrarTrackingModal(pedidoData) {
    try {
      const orderId = pedidoData?.id || pedidoData?.pedido_id || pedidoData?.pedido?.id || null;
      if (!orderId) {
        console.error('[mostrarTrackingModal] No se encontró orderId en pedidoData:', pedidoData);
        return;
      }

      // El flujo nuevo carga datos desde /registros/{id}/... y abre el selector de prendas.
      this.openOrderTracking(orderId, true);
    } catch (e) {
      console.error('[mostrarTrackingModal] Error:', e);
    }
  }

  // Cargar datos básicos del pedido
  async loadOrderBasicData(orderId) {
    try {
      const response = await fetch(`/registros/${orderId}/recibos-datos`);
      if (!response.ok) throw new Error('Error al cargar datos del pedido');
      
      const result = await response.json();
      console.log('[loadOrderBasicData] Respuesta del endpoint:', result);
      
      // Extraer datos desde la estructura del endpoint
      const data = result.data || result;
      console.log('[loadOrderBasicData] Datos extraídos:', data);
      
      window.currentOrderData = data;
      
      // Ensure days selector is initialized before updating order info
      if (typeof ensureDaysSelectorInitialized === 'function') {
        ensureDaysSelectorInitialized();
      }
      
      // Actualizar información del pedido en el modal
      if (typeof updateOrderInfo === 'function') {
        updateOrderInfo(data);
      }
      
    } catch (error) {
      console.error('[loadOrderBasicData] Error:', error);
      throw error;
    }
  }

  // Cargar prendas con seguimiento
  async loadPrendasWithTracking(orderId) {
    try {
      console.log('[loadPrendasWithTracking] Cargando prendas para orden:', orderId);
      
      const response = await fetch(`/registros/${orderId}/seguimiento-prenda`);
      if (!response.ok) throw new Error('Error al cargar seguimiento de prendas');
      
      const data = await response.json();
      console.log('[loadPrendasWithTracking] Datos recibidos:', data);
      
      // Renderizar prendas
      if (typeof renderPrendas === 'function') {
        renderPrendas(data.prendas || []);
      }
      
    } catch (error) {
      console.error('[loadPrendasWithTracking] Error:', error);
      throw error;
    }
  }

  // Guardar selección de día de entrega desde el modal de seguimiento
  async saveDiaEntregaSelection() {
    try {
      const diasSeleccionados = window.__trackingDiasSeleccionados;
      
      // Obtener el ID del recibo/orden del header del modal
      let reciboId = null;
      let ordenId = null;
      
      // Intentar obtener orden ID desde los datos globales
      if (window.currentOrderData) {
        ordenId = window.currentOrderData.id;
      }
      
      // Si no tenemos orden ID, intentar obtenerlo dari el DOM
      if (!ordenId) {
        const ordenNumberEl = document.getElementById('trackingOrderNumber');
        if (ordenNumberEl) {
          const text = ordenNumberEl.textContent;
          // Si el texto es un número, usarlo como orden ID
          if (/^\d+$/.test(text)) {
            ordenId = parseInt(text);
          }
        }
      }
      
      if (!ordenId) {
        console.warn('[saveDiaEntregaSelection] No se encontró el ID de la orden');
        return;
      }

      // Obtener el prenda_id del estado global
      let prendaId = null;
      if (window.currentPrendaData && window.currentPrendaData.id) {
        prendaId = window.currentPrendaData.id;
      }
      
      console.log('[saveDiaEntregaSelection] Guardando:', {
        dias_seleccionados: diasSeleccionados,
        orden_id: ordenId,
        prenda_id: prendaId
      });
      
      // Enviar al servidor
      const response = await fetch(`/registros/${ordenId}/dia-entrega`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        },
        body: JSON.stringify({
          dia_de_entrega: diasSeleccionados,
          prenda_id: prendaId,
          calcular_fecha_estimada: true
        })
      });
      
      if (!response.ok) {
        throw new Error('Error al guardar día de entrega');
      }
      
      const result = await response.json();
      console.log('[saveDiaEntregaSelection] Respuesta del servidor:', result);
      
      // Actualizar la UI con la fecha estimada calculada
      if (result.data && result.data.fecha_estimada_de_entrega) {
        const fechaEstimadaEl = document.getElementById('trackingEstimatedDate');
        if (fechaEstimadaEl) {
          const fechaFormato = typeof formatDate === 'function' ? formatDate(result.data.fecha_estimada_de_entrega) : '-';
          fechaEstimadaEl.textContent = fechaFormato;
        }
        
        const selectorEstimatedDateEl = document.getElementById('selectorOrderEstimatedDate');
        if (selectorEstimatedDateEl) {
          const fechaFormato = typeof formatDate === 'function' ? formatDate(result.data.fecha_estimada_de_entrega) : '-';
          selectorEstimatedDateEl.textContent = fechaFormato;
        }
      }
      
      // Mostrar notificación de éxito
      if (typeof showSuccess === 'function') {
        const diasText = diasSeleccionados === null ? 'Sin seleccionar' : `${diasSeleccionados} día${diasSeleccionados !== 1 ? 's' : ''}`;
        showSuccess(`Día de entrega actualizado: ${diasText}`);
      }
      
    } catch (error) {
      console.error('[saveDiaEntregaSelection] Error:', error);
      if (typeof showError === 'function') {
        showError('Error al guardar el día de entrega');
      }
    }
  }

  // Actualizar el área en la tabla de recibos-costura
  async actualizarAreaEnTablaRecibos() {
    try {
      console.log('[actualizarAreaEnTablaRecibos] Verificando si estamos en recibos-costura');
      
      // Verificar si estamos en la página de recibos-costura
      if (!window.location.pathname.includes('/recibos-costura')) {
        console.log('[actualizarAreaEnTablaRecibos] No estamos en recibos-costura, omitiendo actualización');
        return;
      }

      const pedidoId = window.currentOrderData?.id || null;
      const prendaId = window.currentPrendaData?.id || null;
      const numeroRecibo = window.currentConsecutivoCosturaData?.consecutivo || null;

      if (!pedidoId || !prendaId || !numeroRecibo) {
        console.warn('[actualizarAreaEnTablaRecibos] Datos insuficientes para refrescar fila', {
          pedidoId,
          prendaId,
          numeroRecibo
        });
        return;
      }

      const row = this.findReciboCosturaRow(pedidoId, prendaId, numeroRecibo);
      if (!row) {
        console.warn('[actualizarAreaEnTablaRecibos] No se encontró fila a actualizar', {
          pedidoId,
          prendaId,
          numeroRecibo
        });
        return;
      }

      const url = `/registros/${pedidoId}/consecutivo-costura?prenda_id=${encodeURIComponent(prendaId)}`;
      const resp = await fetch(url);
      if (!resp.ok) {
        throw new Error(`Error HTTP ${resp.status} al refrescar consecutivo-costura`);
      }
      const data = await resp.json();
      console.log('[actualizarAreaEnTablaRecibos] Respuesta consecutivo-costura:', data);

      if (!data || !data.success) {
        return;
      }

      // Área (columna 3)
      const areaBadge = row.querySelector('td:nth-child(3) .badge');
      if (areaBadge && data.area) {
        areaBadge.textContent = data.area;
      }

      // Encargado orden (última columna)
      const encargadoSpan = row.querySelector('td:last-child span');
      if (encargadoSpan) {
        encargadoSpan.textContent = (data.encargado && String(data.encargado).trim() !== '')
          ? String(data.encargado).trim()
          : '-';
      }
      
    } catch (error) {
      console.error('[actualizarAreaEnTablaRecibos] Error general:', error);
    }
  }

  findReciboCosturaRow(pedidoId, prendaId, numeroRecibo) {
    const filas = document.querySelectorAll('#tablaRecibosBody tr[data-pedido-id][data-numero-recibo]');
    for (const fila of filas) {
      const filaPedidoId = fila.getAttribute('data-pedido-id');
      const filaNumeroRecibo = fila.getAttribute('data-numero-recibo');
      if (String(filaPedidoId) !== String(pedidoId) || String(filaNumeroRecibo) !== String(numeroRecibo)) {
        continue;
      }
      const btn = fila.querySelector('.btn-ver-dropdown');
      const filaPrendaId = btn ? btn.getAttribute('data-prenda-id') : null;
      if (String(filaPrendaId) === String(prendaId)) {
        return fila;
      }
    }
    return null;
  }
}

// Exportar para uso global
window.TrackingDataLoader = TrackingDataLoader;
window.trackingDataLoader = new TrackingDataLoader();

// Funciones globales para compatibilidad
window.openOrderTracking = (orderId, mostrarSelector) => window.trackingDataLoader.openOrderTracking(orderId, mostrarSelector);
window.mostrarTrackingModal = (pedidoData) => window.trackingDataLoader.mostrarTrackingModal(pedidoData);
window.loadOrderBasicData = (orderId) => window.trackingDataLoader.loadOrderBasicData(orderId);
window.loadPrendasWithTracking = (orderId) => window.trackingDataLoader.loadPrendasWithTracking(orderId);
window.saveDiaEntregaSelection = () => window.trackingDataLoader.saveDiaEntregaSelection();
window.actualizarAreaEnTablaRecibos = () => window.trackingDataLoader.actualizarAreaEnTablaRecibos();
