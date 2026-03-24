/**
 * UpdateRenderer
 * 
 * Responsabilidad: Actualizar elementos específicos del DOM (parciales)
 * OCP: Centralizar lógica de actualización, fácil de extender
 * 
 * @class UpdateRenderer
 */
export class UpdateRenderer {
  /**
   * Actualizar información del pedido en el modal y selector
   * 
   * @param {Object} orderData - Datos del pedido
   * @param {Object} orderState - Estado centralizado
   * @param {Object} dateFormatter - Formateador de fechas
   */
  updateOrderInfo(orderData, orderState, dateFormatter) {
    console.log('[UpdateRenderer] Actualizando información del pedido');

    const numeroPedido = orderData.numero_pedido || '-';
    const cliente = orderData.cliente || '-';
    const estadoDisplay = (orderData.estado || '-').replace(/_/g, ' ').toUpperCase();
    const fechaEstimada = dateFormatter.getOrderEstimatedDate(orderData);
    const fechaInicio = dateFormatter.getOrderStartDate(orderData);

    // Helper para establecer texto
    const setText = (id, text) => {
      const el = document.getElementById(id);
      if (el) el.textContent = text;
    };

    // Modal principal
    setText('trackingOrderNumber', numeroPedido);
    setText('trackingOrderClient', cliente);
    setText('trackingOrderStatus', estadoDisplay);
    setText('trackingEstimatedDate', this.formatDate(orderData.fecha_estimada_entrega) || '-');
    setText('trackingTotalDays', orderData.total_dias || '0');

    // Recibo principal (lógica de dominio delegada a OrderState)
    setText('trackingOrderRecibo', orderState.getReciboPrincipal());

    // Selector de prendas
    setText('selectorOrderNumber', numeroPedido);
    setText('selectorOrderClient', cliente);
    setText('selectorOrderStatus', estadoDisplay);
    setText('selectorOrderStartDate', fechaInicio);
    setText('selectorOrderEstimatedDate', fechaEstimada);

    // Estilo de fecha estimada
    const fechaEstimadaEl = document.getElementById('selectorOrderEstimatedDate');
    if (fechaEstimadaEl) {
      const tieneFecha = fechaEstimada !== '-';
      fechaEstimadaEl.style.color = tieneFecha ? '#1f2937' : '#9ca3af';
      fechaEstimadaEl.style.fontWeight = tieneFecha ? '600' : '400';
      if (!tieneFecha) fechaEstimadaEl.textContent = 'No definida';
    }
  }

  /**
   * Actualizar fecha estimada de entrega del pedido
   * 
   * @param {Object} orderState - Estado centralizado
   * @param {Object} dateFormatter - Formateador de fechas
   */
  updateEstimatedDeliveryDate(orderState, dateFormatter) {
    console.log('[UpdateRenderer] Actualizando fecha estimada de entrega');

    const fechaEstimadaElement = document.getElementById('selectorOrderEstimatedDate');
    const order = orderState.getOrder();

    if (!fechaEstimadaElement || !order) return;

    const fechaEstimada = dateFormatter.getOrderEstimatedDate(order);

    if (fechaEstimada !== '-') {
      fechaEstimadaElement.textContent = fechaEstimada;
      fechaEstimadaElement.style.color = '#1f2937';
      fechaEstimadaElement.style.fontWeight = '600';
    } else {
      fechaEstimadaElement.textContent = 'No definida';
      fechaEstimadaElement.style.color = '#9ca3af';
      fechaEstimadaElement.style.fontWeight = '400';
    }
  }

  /**
   * Actualizar nombre de la prenda en el modal
   * 
   * @param {Object} prenda - Datos de prenda
   */
  updatePrendaName(prenda) {
    const nombreElement = document.getElementById('trackingPrendaName');
    if (nombreElement) {
      nombreElement.textContent = prenda.nombre_prenda || `Prenda ${prenda.id}`;
    }
  }

  /**
   * Actualizar header del recibo en el modal
   * 
   * @param {string} numeroRecibo - Número del recibo
   * @param {string} area - Área actual
   */
  updateReciboHeader(numeroRecibo, area) {
    const reciboHeaderElement = document.getElementById('trackingPrendaReciboHeader');
    if (reciboHeaderElement) {
      reciboHeaderElement.textContent = area !== '-'
        ? `${numeroRecibo} - ${area}`
        : numeroRecibo;
    }

    const reciboElement = document.getElementById('trackingPrendaRecibo');
    if (reciboElement) {
      reciboElement.textContent = numeroRecibo;
    }
  }

  /**
   * Actualizar botón de agregar proceso
   * 
   * @param {Object} prenda - Datos de prenda
   * @param {boolean} readonly - Si es solo lectura
   */
  updateAddProcessButton(prenda, readonly = false) {
    const btnAgregar = document.getElementById('btnOpenAddProcesoModal');
    if (btnAgregar) {
      btnAgregar.style.display = readonly ? 'none' : 'block';
      btnAgregar.disabled = !!readonly;
    }
  }

  /**
   * Actualizar estado del contador de días (dinámico)
   * 
   * @param {string} elementId - ID del elemento a actualizar
   * @param {number} days - Cantidad de días
   */
  updateDayCounter(elementId, days) {
    const element = document.getElementById(elementId);
    if (!element) return;

    element.textContent = `${days} ${days === 1 ? 'día' : 'días'}`;
  }

  /**
   * Actualizar fila en tabla de recibos-costura
   * 
   * @param {HTMLElement} row - Fila a actualizar
   * @param {Object} data - Nuevos datos
   */
  updateReciboCosturaRow(row, data) {
    if (!row) return;

    console.log('[UpdateRenderer] Actualizando fila de recibo-costura', data);

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

    // Fechas si están disponibles
    if (data.fecha_inicio) {
      const fechaInicioSpan = row.querySelector('[data-fecha-inicio]');
      if (fechaInicioSpan) {
        fechaInicioSpan.textContent = this.formatDate(data.fecha_inicio);
      }
    }

    if (data.fecha_fin) {
      const fechaFinSpan = row.querySelector('[data-fecha-fin]');
      if (fechaFinSpan) {
        fechaFinSpan.textContent = this.formatDate(data.fecha_fin);
      }
    }
  }

  /**
   * Mostrar/ocultar modal
   * 
   * @param {string} modalId - ID del modal
   * @param {boolean} show - True para mostrar, false para ocultar
   */
  toggleModal(modalId, show = true) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    if (show) {
      modal.classList.add('show');
      modal.style.setProperty('display', 'flex', 'important');
      modal.style.setProperty('visibility', 'visible', 'important');
      modal.style.setProperty('opacity', '1', 'important');
      modal.style.setProperty('z-index', '9999999', 'important');
      modal.style.setProperty('position', 'fixed', 'important');
      modal.style.setProperty('top', '0', 'important');
      modal.style.setProperty('left', '0', 'important');
      modal.style.setProperty('width', '100vw', 'important');
      modal.style.setProperty('height', '100vh', 'important');
      modal.style.setProperty('background', 'rgba(0, 0, 0, 0.5)', 'important');
      modal.style.setProperty('align-items', 'center', 'important');
      modal.style.setProperty('justify-content', 'center', 'important');
    } else {
      modal.classList.remove('show');
      modal.style.display = 'none';
    }
  }

  /**
   * Mostrar/ocultar sección
   * 
   * @param {string} sectionId - ID de la sección
   * @param {boolean} visible - True para mostrar
   */
  toggleSection(sectionId, visible = true) {
    const section = document.getElementById(sectionId);
    if (section) {
      section.style.display = visible ? 'block' : 'none';
    }
  }

  /**
   * Actualizar botón estado (cargando, activo, inactivo)
   * 
   * @param {HTMLElement} button - Elemento botón
   * @param {string} state - Estado ('loading', 'active', 'inactive')
   * @param {string} text - Texto del botón
   */
  updateButtonState(button, state, text = '') {
    if (!button) return;

    button.disabled = state === 'loading' || state === 'inactive';

    if (text) button.textContent = text;

    button.classList.remove('loading', 'active', 'inactive');
    button.classList.add(state);
  }

  /**
   * Formatear fecha (helper privado)
   * 
   * @private
   */
  formatDate(dateString) {
    if (!dateString) return null;

    try {
      const date = new Date(dateString);
      return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });
    } catch (e) {
      return null;
    }
  }

  /**
   * Limpiar (no requiere limpieza, métodos son stateless)
   */
  clear() {
    // Stateless - no requiere limpieza
  }
}
