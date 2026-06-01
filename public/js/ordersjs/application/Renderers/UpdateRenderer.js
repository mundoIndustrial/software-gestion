/**
 * UpdateRenderer
 * 
 * Responsabilidad: Actualizar elementos especi­ficos del DOM (parciales)
 * OCP: Centralizar logica de actualizacion, facil de extender
 * 
 * @class UpdateRenderer
 */
export class UpdateRenderer {
  /**
   * Actualizar informacion del pedido en el modal y selector
   * 
   * @param {Object} orderData - Datos del pedido
   * @param {Object} orderState - Estado centralizado
   * @param {Object} dateFormatter - Formateador de fechas
   */
  updateOrderInfo(orderData, orderState, dateFormatter) {
    console.log('[UpdateRenderer] Actualizando informacion del pedido');

    const numeroPedido = orderData.numero_pedido || '-';
    const cliente = orderData.cliente || '-';
    const estadoDisplay = (orderData.estado || '-').replace(/_/g, ' ').toUpperCase();
    const fechaEstimada = dateFormatter.getOrderEstimatedDate(orderData);
    const fechaInicio = this.formatDateTimeLabel(
      orderData?.created_at || orderData?.fecha_creacion || null,
      dateFormatter.getOrderStartDate(orderData)
    );

    // Helper para establecer texto
    const setText = (id, text) => {
      const el = document.getElementById(id);
      if (el) el.textContent = text;
    };

    // Modal principal
    setText('trackingOrderNumber', numeroPedido);
    setText('trackingOrderClient', cliente);
    setText('trackingOrderStatus', estadoDisplay);
    setText('trackingEstimatedDate', this.formatDate(orderData.fecha_estimada_de_entrega) || '-');

    // Recibo principal (pre-computado y enviado por el backend)
    const reciboCtx = globalThis.currentTrackingReceiptContext?.numeroRecibo;
    const reciboPrincipal = (reciboCtx !== null && reciboCtx !== undefined && String(reciboCtx).trim() !== '')
      ? String(reciboCtx).trim()
      : (orderData.recibo_principal || '-');
    console.log('[UpdateRenderer.updateOrderInfo] trackingOrderRecibo <- recibo_principal:', {
      pedidoId: orderData.id || null,
      reciboPrincipal,
      pathname: globalThis.location?.pathname || ''
    });
    setText('trackingOrderRecibo', reciboPrincipal);

    // Selector de prendas
    setText('selectorOrderNumber', numeroPedido);
    setText('selectorOrderClient', cliente);
    setText('selectorOrderStatus', estadoDisplay);
    setText('selectorOrderStartDate', fechaInicio);
    setText('selectorOrderEstimatedDate', fechaEstimada);

    this.removeInlineTimelineSelector();
    this.renderApprovalSummaryInfo(orderData);

    // Estilo de fecha estimada
    const fechaEstimadaEl = document.getElementById('selectorOrderEstimatedDate');
    if (fechaEstimadaEl) {
      const tieneFecha = fechaEstimada !== '-';
      fechaEstimadaEl.style.color = tieneFecha ? '#1f2937' : '#9ca3af';
      fechaEstimadaEl.style.fontWeight = tieneFecha ? '600' : '400';
      if (!tieneFecha) fechaEstimadaEl.textContent = 'No definida';
    }

    // Actualizar selector de di­as si dia_de_entrega existe
    if (orderData.dia_de_entrega) {
      console.log('[UpdateRenderer] Dias de entrega encontrados:', orderData.dia_de_entrega);
      
      // Usar la funcion de reintentos si existe (definida en tracking-modal-handler)
      if (typeof window.updateDaysSelectorWithRetry === 'function') {
        window.updateDaysSelectorWithRetry(orderData.dia_de_entrega);
      } else {
        console.warn('[UpdateRenderer] updateDaysSelectorWithRetry no disponible');
      }
    } else {
      console.log('[UpdateRenderer] Sin dia_de_entrega en orderData');
    }
  }

  removeInlineTimelineSelector() {
    const trigger = document.getElementById('selectorTimelineAccordionBtn');
    if (trigger && trigger.parentElement) {
      trigger.parentElement.remove();
    }
  }

  renderApprovalSummaryInfo(orderData) {
    const infoContainer = document.querySelector('#trackingPrendasSelectorOverlay .tracking-prendas-info');
    if (!infoContainer) return;

    infoContainer.querySelectorAll('[data-approval-extra="1"]').forEach((el) => el.remove());

    const creadoEn = orderData?.created_at || null;
    const carteraEn = orderData?.aprobado_por_cartera_en || null;
    const supervisorEn = orderData?.aprobado_por_supervisor_en || null;
    const carteraNombre = String(orderData?.cartera_nombre || '').trim();
    const supervisorNombre = String(
      orderData?.supervisor_nombre ||
      orderData?.aprobado_por_supervisor_nombre ||
      ''
    ).trim();

    const extras = [
      {
        label: 'Aprob. Cartera',
        value: this.formatDateTimeLabel(carteraEn, 'Pendiente'),
        meta: this.formatDurationLabel(creadoEn, carteraEn)
      },
      {
        label: 'Aprob. Supervisor',
        value: this.formatDateTimeLabel(supervisorEn, 'Pendiente'),
        meta: this.formatDurationLabel(carteraEn, supervisorEn) || (supervisorNombre ? `Por: ${supervisorNombre}` : '')
      }
    ];

    extras.forEach((item) => {
      const card = document.createElement('div');
      card.className = 'tracking-prendas-info-item';
      card.dataset.approvalExtra = '1';
      card.innerHTML = `
        <span class="tracking-prendas-info-label">${item.label}</span>
        <span class="tracking-prendas-info-value">${item.value}</span>
        ${item.meta ? `<span class="tracking-prendas-info-meta">${item.meta}</span>` : ''}
      `;
      infoContainer.appendChild(card);
    });

    // Mantener "Fecha Estimada" como ultima tarjeta del resumen
    const estimatedValueEl = document.getElementById('selectorOrderEstimatedDate');
    const estimatedCard = estimatedValueEl ? estimatedValueEl.closest('.tracking-prendas-info-item') : null;
    if (estimatedCard && estimatedCard.parentElement === infoContainer) {
      infoContainer.appendChild(estimatedCard);
    }
  }

  formatDateTimeLabel(dateValue, fallback = '-') {
    if (!dateValue) return fallback;
    const date = new Date(dateValue);
    if (Number.isNaN(date.getTime())) return fallback;
    return date.toLocaleString('es-CO', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      hour12: true
    });
  }

  formatDurationLabel(startValue, endValue) {
    if (!startValue || !endValue) return '';
    const start = new Date(startValue);
    const end = new Date(endValue);
    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end < start) return '';

    const totalMinutes = Math.floor((end.getTime() - start.getTime()) / 60000);
    const days = Math.floor(totalMinutes / (60 * 24));
    const hours = Math.floor((totalMinutes % (60 * 24)) / 60);
    const minutes = totalMinutes % 60;

    if (days > 0) return `Demora: ${days}d`;
    if (hours > 0 && minutes > 0) return `Demora: ${hours}h ${minutes}m`;
    if (hours > 0) return `Demora: ${hours}h`;
    return `Demora: ${minutes}m`;
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
   * @param {string} numeroRecibo - Numero del recibo
   * @param {string} area - Area actual
   */
  updateReciboHeader(numeroRecibo, area) {
    console.log('[UpdateRenderer.updateReciboHeader] Entrada:', {
      numeroRecibo,
      area,
      pathname: globalThis.location?.pathname || ''
    });

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

    const trackingOrderRecibo = document.getElementById('trackingOrderRecibo');
    if (trackingOrderRecibo) {
      const numeroNormalizado = String(numeroRecibo || '')
        .replace(/^COSTURA\s*#\s*/i, '')
        .replace(/^Recibo\s*#\s*/i, '')
        .trim();

      const reciboCtx = globalThis.currentTrackingReceiptContext?.numeroRecibo;
      const valorDesdeContexto = (reciboCtx !== null && reciboCtx !== undefined && String(reciboCtx).trim() !== '')
        ? String(reciboCtx).trim()
        : null;

      const valorFinal = valorDesdeContexto
        ? valorDesdeContexto
        : (numeroNormalizado && numeroNormalizado !== 'Sin recibo'
        ? numeroNormalizado
        : '-');

      console.log('[UpdateRenderer.updateReciboHeader] trackingOrderRecibo <-', {
        numeroReciboOriginal: numeroRecibo,
        numeroNormalizado,
        valorFinal
      });

      trackingOrderRecibo.textContent = valorFinal;
    } else {
      console.warn('[UpdateRenderer.updateReciboHeader] No existe #trackingOrderRecibo en DOM');
    }
  }

  /**
   * Actualizar dias y fecha estimada desde el recibo COSTURA de la prenda seleccionada.
   * Si no existen en la prenda, usa fallback del pedido.
   *
   * @param {Object} prenda
   * @param {Object} orderData
   */
  updateDeliveryInfoFromPrenda(prenda, orderData = {}) {
    const costuraData = this.getCosturaDataFromPrenda(prenda);

    const diaRecibo = this.parseIntegerOrNull(
      prenda?.dia_de_entrega ?? costuraData?.dia_de_entrega ?? null
    );
    const fechaRecibo = prenda?.fecha_estimada_de_entrega
      ?? costuraData?.fecha_estimada_de_entrega
      ?? null;

    const diaFallbackPedido = this.parseIntegerOrNull(orderData?.dia_de_entrega ?? null);
    const fechaFallbackPedido = orderData?.fecha_estimada_de_entrega ?? null;

    const diaFinal = diaRecibo ?? diaFallbackPedido;
    const fechaFinal = fechaRecibo || fechaFallbackPedido || null;

    console.log('[UpdateRenderer.updateDeliveryInfoFromPrenda] Fuente de datos:', {
      prendaId: prenda?.id || prenda?.prenda_pedido_id || null,
      diaRecibo,
      fechaRecibo,
      diaFallbackPedido,
      fechaFallbackPedido,
      diaFinal,
      fechaFinal
    });

    const fechaTexto = this.formatDate(fechaFinal) || 'No definida';
    const trackingEstimatedDate = document.getElementById('trackingEstimatedDate');
    const selectorEstimatedDate = document.getElementById('selectorOrderEstimatedDate');

    if (trackingEstimatedDate) {
      trackingEstimatedDate.textContent = fechaTexto;
    }
    if (selectorEstimatedDate) {
      selectorEstimatedDate.textContent = fechaTexto;
      const tieneFecha = fechaTexto !== 'No definida';
      selectorEstimatedDate.style.color = tieneFecha ? '#1f2937' : '#9ca3af';
      selectorEstimatedDate.style.fontWeight = tieneFecha ? '600' : '400';
    }

    const selector = window.trackingDaysSelector;
    if (selector && typeof selector.setValue === 'function') {
      selector.setValue(diaFinal ?? 0);
    }
  }
  ensureInlineTimelineSelector(orderData) {
    const selectorBody = document.querySelector('#trackingPrendasSelectorOverlay .tracking-prendas-selector-body');
    if (!selectorBody) return;

    let trigger = document.getElementById('selectorTimelineAccordionBtn');
    let wrapper = document.getElementById('selectorInlineTimelineWrapper');
    let content = document.getElementById('selectorInlineTimelineContent');

    if (!trigger) {
      const actionRow = document.createElement('div');
      actionRow.style.marginTop = '10px';
      actionRow.style.border = '1px solid #e5e7eb';
      actionRow.style.borderRadius = '10px';
      actionRow.style.overflow = 'hidden';
      actionRow.style.background = '#fff';

      trigger = document.createElement('button');
      trigger.id = 'selectorTimelineAccordionBtn';
      trigger.type = 'button';
      trigger.style.width = '100%';
      trigger.style.display = 'flex';
      trigger.style.justifyContent = 'space-between';
      trigger.style.alignItems = 'center';
      trigger.style.padding = '10px 12px';
      trigger.style.background = 'transparent';
      trigger.style.border = '0';
      trigger.style.cursor = 'pointer';
      trigger.innerHTML = '<span style="font-weight:600;color:#1f2937;">Linea de tiempo del pedido</span><span id="selectorTimelineChevron" style="font-size:12px;color:#64748b;">▼</span>';
      actionRow.appendChild(trigger);
      selectorBody.appendChild(actionRow);
    }

    if (!wrapper) {
      wrapper = document.createElement('div');
      wrapper.id = 'selectorInlineTimelineWrapper';
      wrapper.style.maxHeight = '0';
      wrapper.style.opacity = '0';
      wrapper.style.overflow = 'hidden';
      wrapper.style.transition = 'max-height 0.25s ease, opacity 0.2s ease';
      wrapper.style.borderTop = '1px solid #e5e7eb';

      content = document.createElement('div');
      content.id = 'selectorInlineTimelineContent';
      content.style.padding = '12px';
      content.style.background = '#fff';
      content.innerHTML = '<div style="font-size:13px;color:#64748b;">Haz clic para cargar la linea de tiempo.</div>';
      wrapper.appendChild(content);

      trigger.parentElement.appendChild(wrapper);
    }

    const pedidoNormalizado = String(orderData.numero_pedido || '').trim();
    const pedidoParam = encodeURIComponent(pedidoNormalizado);
    trigger.dataset.timelineUrl = pedidoParam
      ? `/dashboard/timeline-pedidos?search_pedido=${pedidoParam}`
      : '/dashboard/timeline-pedidos';

    trigger.onclick = async () => {
      if (!content || !wrapper) return;

      const isOpen = wrapper.style.maxHeight && wrapper.style.maxHeight !== '0px';
      const chevron = document.getElementById('selectorTimelineChevron');
      if (isOpen) {
        wrapper.style.maxHeight = '0';
        wrapper.style.opacity = '0';
        if (chevron) chevron.textContent = '▼';
        return;
      }

      const loadedPedido = content.dataset.loadedPedido || '';
      if (pedidoNormalizado && loadedPedido !== pedidoNormalizado) {
        const timelineUrl = trigger.dataset.timelineUrl || '/dashboard/timeline-pedidos';
        await this.loadInlineTimelineHtml(timelineUrl, content, pedidoNormalizado);
      }

      wrapper.style.maxHeight = '700px';
      wrapper.style.opacity = '1';
      if (chevron) chevron.textContent = '▲';
    };
  }

  async loadInlineTimelineHtml(timelineUrl, container, pedidoNumero) {
    if (!container) return;

    container.innerHTML = '<div style="font-size:13px;color:#64748b;">Cargando linea de tiempo...</div>';

    try {
      const response = await fetch(timelineUrl, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'text/html'
        }
      });

      if (!response.ok) {
        throw new Error(`Timeline HTTP ${response.status}`);
      }

      const html = await response.text();
      const doc = new DOMParser().parseFromString(html, 'text/html');
      const source = doc.querySelector('.container-fluid.py-4') || doc.querySelector('.container-fluid');

      if (!source) {
        container.innerHTML = '<div style="font-size:13px;color:#b91c1c;">No se pudo renderizar la linea de tiempo.</div>';
        return;
      }

      const timelineWrapper = document.createElement('div');
      timelineWrapper.className = 'selector-inline-timeline';
      timelineWrapper.innerHTML = source.innerHTML;
      timelineWrapper.querySelectorAll('script').forEach((node) => node.remove());
      timelineWrapper.querySelectorAll('[onclick]').forEach((node) => node.removeAttribute('onclick'));

      container.innerHTML = '';
      container.appendChild(timelineWrapper);
      container.dataset.loadedPedido = pedidoNumero || '';
      this.bindInlineTimelineAccordions(container);
    } catch (error) {
      console.error('[UpdateRenderer.loadInlineTimelineHtml] Error cargando timeline inline:', error);
      container.innerHTML = '<div style="font-size:13px;color:#b91c1c;">No fue posible cargar la linea de tiempo.</div>';
    }
  }

  bindInlineTimelineAccordions(container) {
    if (!container) return;

    const buttons = container.querySelectorAll('.accordion-button[aria-controls]');
    buttons.forEach((btn) => {
      if (btn.dataset.inlineTimelineBound === '1') return;
      btn.dataset.inlineTimelineBound = '1';

      btn.addEventListener('click', () => {
        const targetId = btn.getAttribute('aria-controls');
        if (!targetId) return;

        const collapse = container.querySelector(`#${targetId}`);
        if (!collapse) return;

        const isOpen = collapse.style.display === 'block';
        collapse.style.display = isOpen ? 'none' : 'block';
        btn.classList.toggle('collapsed', isOpen);
        btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
      });
    });
  }

  getCosturaDataFromPrenda(prenda) {
    const consecutivos = Array.isArray(prenda?.consecutivos) ? prenda.consecutivos : [];
    return consecutivos.find((c) => String(c?.tipo_recibo || '').toUpperCase() === 'COSTURA') || null;
  }

  parseIntegerOrNull(value) {
    if (value === null || value === undefined || value === '') return null;
    const n = parseInt(value, 10);
    return Number.isFinite(n) ? n : null;
  }

  /**
   * Actualizar boton de agregar proceso
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
   * Actualizar estado del contador de di­as (dinamico)
   * 
   * @param {string} elementId - ID del elemento a actualizar
   * @param {number} days - Cantidad de di­as
   */
  updateDayCounter(elementId, days) {
    const element = document.getElementById(elementId);
    if (!element) return;

    element.textContent = `${days} ${days === 1 ? 'día' : 'dí­as'}`;
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

    // area (columna 3)
    const areaBadge = row.querySelector('td:nth-child(3) .badge');
    if (areaBadge && data.area) {
      areaBadge.textContent = data.area;
    }

    // Encargado orden (ultima columna)
    const encargadoSpan = row.querySelector('td:last-child span');
    if (encargadoSpan) {
      encargadoSpan.textContent = (data.encargado && String(data.encargado).trim() !== '')
        ? String(data.encargado).trim()
        : '-';
    }

    // Fechas si estan disponibles
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
   * Mostrar/ocultar seccion
   * 
   * @param {string} sectionId - ID de la seccion
   * @param {boolean} visible - True para mostrar
   */
  toggleSection(sectionId, visible = true) {
    const section = document.getElementById(sectionId);
    if (section) {
      section.style.display = visible ? 'block' : 'none';
    }
  }

  /**
   * Actualizar boton estado (cargando, activo, inactivo)
   * 
   * @param {HTMLElement} button - Elemento boton
   * @param {string} state - Estado ('loading', 'active', 'inactive')
   * @param {string} text - Texto del boton
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
      let date = null;

      if (typeof dateString === 'string') {
        const ymdMatch = dateString.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (ymdMatch) {
          const [, year, month, day] = ymdMatch;
          date = new Date(
            parseInt(year, 10),
            parseInt(month, 10) - 1,
            parseInt(day, 10),
            12,
            0,
            0
          );
        }
      }

      if (!date) {
        date = new Date(dateString);
      }

      if (isNaN(date.getTime())) {
        return null;
      }

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
   * Limpiar (no requiere limpieza, metodos son stateless)
   */
  clear() {
    // Stateless - no requiere limpieza
  }
}
