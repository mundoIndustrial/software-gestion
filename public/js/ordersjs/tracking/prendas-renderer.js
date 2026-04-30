'use strict';

// Renderizado de prendas y timeline
class PrendasRenderer {
  constructor() {
    this.init();
  }

  init() {
    // Inicialización si es necesaria
  }

  // Renderizar tabla única de prendas en el overlay
  renderPrendas(prendas) {
    const container = document.getElementById('trackingPrendasSelectorContainer');
    if (!container) return;
    
    console.log('[renderPrendas] Renderizando tabla de prendas:', prendas.length);
    
    container.innerHTML = '';
    
    if (prendas.length === 0) {
      container.innerHTML = `
        <div class="tracking-no-prendas">
          <p>No hay prendas registradas para este pedido</p>
        </div>
      `;
      return;
    }
    
    // Crear tabla única con todas las prendas
    const tableHtml = this.createPrendasTable(prendas);
    container.innerHTML = tableHtml;
    
    // Actualizar fecha estimada de entrega del pedido
    if (typeof updateEstimatedDeliveryDate === 'function') {
      updateEstimatedDeliveryDate();
    }
  }

  // Crear tabla HTML con todas las prendas
  createPrendasTable(prendas) {
    let tableHtml = `
      <div class="prendas-table-container">
        <table class="prendas-report-table">
          <thead>
            <tr>
              <th>Prenda</th>
              <th>N° Recibo</th>
              <th>Cantidad</th>
              <th>Procesos</th>
              <th>Área</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
    `;
    
    // Almacenar las prendas globalmente para acceso desde onclick
    globalThis.prendasData = prendas;
    
    prendas.forEach((prenda, index) => {
      // Logging para depuración
      console.log(`[createPrendasTable] Prenda ${index}:`, {
        nombre: prenda.nombre_prenda,
        tipos_recibo_procesos: prenda.tipos_recibo_procesos,
        procesos_generales: prenda.procesos
      });
      
      // Extraer información de la prenda
      const nombrePrenda = prenda.nombre_prenda || `Prenda ${index + 1}`;
      const cantidad = prenda.cantidad || 0;
      const totalProcesos = prenda.total_procesos || 0;
      
      // Obtener número de recibo (consecutivo_actual del recibo COSTURA)
      let numeroRecibo = '-';
      if (prenda.consecutivos && Array.isArray(prenda.consecutivos)) {
        const reciboCostura = prenda.consecutivos.find(r => r.tipo_recibo === 'COSTURA');
        if (reciboCostura) {
          numeroRecibo = reciboCostura.consecutivo_actual || '-';
        }
      }
      
      // Contar recibos especiales para el badge de procesos
      const recibosEspeciales = prenda.recibos_especiales || [];
      const procesosCount = Array.isArray(recibosEspeciales) ? recibosEspeciales.length : 0;
      const prendaId = prenda.id || prenda.prenda_pedido_id || null;
      const prendaIdArg = prendaId !== null ? `'${String(prendaId)}'` : 'null';
      
      // Crear botón de procesos o texto según haya procesos
      let procesosHtml = '';
      if (procesosCount > 0) {
        procesosHtml = `
          <button class="btn-procesos-badge" onclick="globalThis.handleVerProcesos && globalThis.handleVerProcesos(${index}, ${prendaIdArg})" title="Ver procesos especiales">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="1"></circle>
              <circle cx="19" cy="12" r="1"></circle>
              <circle cx="5" cy="12" r="1"></circle>
            </svg>
            <span class="procesos-badge">${procesosCount}</span>
          </button>
        `;
      } else {
        procesosHtml = `
          <span class="procesos-sin-datos">Sin procesos</span>
        `;
      }
      
      // Extraer área basada en el proceso más reciente
      let area = '-';
      if (prenda.area_mas_reciente) {
        area = prenda.area_mas_reciente;
      } else if (prenda.ultimo_proceso_area) {
        // Si ya viene el área del último proceso, usarla
        area = prenda.ultimo_proceso_area;
      } else if (prenda.area && prenda.area.trim() !== '') {
        // Si tiene área asignada directamente, usarla
        area = prenda.area;
      }
      
      // Usar el estado del pedido en lugar del estado calculado de procesos
      const estadoPedido = globalThis.currentOrderData?.estado || 'Sin estado';
      const estadoFormateado = estadoPedido.replace(/_/g, ' ').toUpperCase();
      
      // Determinar si el botón debe estar desactivado (para prendas de bodega)
      const esDeBodega = prenda.de_bodega || false;
      const botonDisabled = esDeBodega ? 'disabled' : '';
      const botonTitle = esDeBodega ? 'Prenda de bodega - no disponible para seguimiento' : 'Ver seguimiento detallado';
      const botonClass = esDeBodega ? 'btn-ver-seguimiento disabled' : 'btn-ver-seguimiento';
      
      // Badge de origen de prenda
      let badgeHtml = '';
      if (prenda.de_bodega) {
        badgeHtml = '<span class="bodega-badge">SE SACA DE BODEGA</span>';
      } else {
        badgeHtml = '<span class="confeciona-badge">SE CONFECCIONA</span>';
      }
      
      tableHtml += `
        <tr class="prendas-table-row" data-prenda-index="${index}" data-prenda-id="${prendaId !== null ? String(prendaId) : ''}">
          <td class="prendas-table-cell prendas-name-cell">
            <div class="prendas-name">${nombrePrenda}</div>
            ${badgeHtml}
          </td>
          <td class="prendas-table-cell">
            <span class="receipt-number-badge">#${numeroRecibo}</span>
          </td>
          <td class="prendas-table-cell">${cantidad}</td>
          <td class="prendas-table-cell procesos-cell">
            ${procesosHtml}
          </td>
          <td class="prendas-table-cell">${area}</td>
          <td class="prendas-table-cell">
            <span class="estado-badge estado-${estadoPedido.toLowerCase().replace(/_/g, '-')}">${estadoFormateado}</span>
          </td>
          <td class="prendas-table-cell acciones-cell">
            <button class="${botonClass}" ${botonDisabled} onclick="showPrendaTrackingFromTable(${index}, ${prendaIdArg})" title="${botonTitle}" data-prenda-id="${prendaId !== null ? String(prendaId) : ''}">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 11l3 3L22 4"></path>
                <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"></path>
              </svg>
              Ver
            </button>
          </td>
        </tr>
      `;
    });
    
    tableHtml += `
          </tbody>
        </table>
      </div>
    `;
    
    return tableHtml;
  }

  // Mostrar seguimiento de una prenda específica desde la tabla
  resolvePrendaFromClickContext(prendas, prendaId, index) {
    let resolvedPrendaId = prendaId;
    let resolvedConsecutivo = null;

    try {
      const evt = globalThis.event;
      const target = evt?.target || null;
      const row = target?.closest ? target.closest('.prendas-table-row') : null;

      if (row) {
        if (!resolvedPrendaId) {
          const rowPrendaId = row.getAttribute('data-prenda-id');
          if (rowPrendaId && String(rowPrendaId).trim() !== '') {
            resolvedPrendaId = rowPrendaId;
          }
        }

        const consecutivoText = row.querySelector('.prendas-consecutivo-cell, .receipt-number-badge')?.textContent?.replace('#', '').trim() || '';
        if (/^\d+$/.test(consecutivoText)) {
          resolvedConsecutivo = String(parseInt(consecutivoText, 10));
        }
      }
    } catch (e) {
      console.warn('[PrendasRenderer.resolvePrendaFromClickContext] No se pudo leer contexto del click:', e);
    }

    console.log('[resolvePrendaFromClickContext] Contexto click:', {
      index,
      prendaIdParametro: prendaId,
      resolvedPrendaId,
      resolvedConsecutivo
    });

    let prenda = null;

    if (resolvedPrendaId !== null && resolvedPrendaId !== undefined && String(resolvedPrendaId).trim() !== '') {
      prenda = prendas.find((p) =>
        String(p?.id) === String(resolvedPrendaId) ||
        String(p?.prenda_pedido_id) === String(resolvedPrendaId)
      ) || null;
    }

    if (!prenda && resolvedConsecutivo) {
      prenda = prendas.find((p) => {
        const consecutivos = Array.isArray(p?.consecutivos) ? p.consecutivos : [];
        const costura = consecutivos.find((c) => String(c?.tipo_recibo || '').toUpperCase() === 'COSTURA');
        return costura && String(costura?.consecutivo_actual) === resolvedConsecutivo;
      }) || null;
    }

    if (!prenda) {
      prenda = prendas[index];
    }

    console.log('[resolvePrendaFromClickContext] Resultado resolución:', {
      resolvedBy: prenda ? (
        (resolvedPrendaId && (String(prenda?.id) === String(resolvedPrendaId) || String(prenda?.prenda_pedido_id) === String(resolvedPrendaId)))
          ? 'prendaId'
          : (resolvedConsecutivo ? 'consecutivo' : 'index')
      ) : 'none',
      prendaId: prenda?.id || prenda?.prenda_pedido_id || null,
      prendaNombre: prenda?.nombre_prenda || null
    });

    return prenda;
  }

  getConsecutivoCosturaFromPrenda(prenda) {
    const consecutivos = Array.isArray(prenda?.consecutivos) ? prenda.consecutivos : [];
    const costura = consecutivos.find((c) => String(c?.tipo_recibo || '').toUpperCase() === 'COSTURA');
    return costura?.consecutivo_actual ? String(costura.consecutivo_actual) : null;
  }

  async showPrendaTrackingFromTable(index, prendaId = null) {
    try {
      console.log('[showPrendaTrackingFromTable] INICIO - Índice:', index, 'prendaId:', prendaId);

      // Preferir currentOrderData; fallback a prendasData
      const prendas = globalThis.currentOrderData?.prendas || globalThis.prendasData;
      if (!prendas || !Array.isArray(prendas)) {
        console.error('[showPrendaTrackingFromTable] No hay prendas disponibles');
        return;
      }
      
      const prenda = this.resolvePrendaFromClickContext(prendas, prendaId, index);

      if (!prenda) {
        console.error('[showPrendaTrackingFromTable] Prenda no encontrada para índice/id:', { index, prendaId });
        return;
      }
      
      console.log('[showPrendaTrackingFromTable] Prenda encontrada:', prenda);
      
      // Llamar a la función original con el objeto prenda
      await this.showPrendaTracking(prenda);
      
    } catch (error) {
      console.error('[showPrendaTrackingFromTable] Error:', error);
    }
  }

  // Mostrar seguimiento de una prenda específica
  async showPrendaTracking(prenda) {
    try {
      console.log('[showPrendaTracking] INICIO - Mostrando seguimiento para prenda:', prenda);

      try {
        const tieneSeguimiento = prenda && (
          (prenda.seguimientos_por_area && Object.keys(prenda.seguimientos_por_area).length > 0) ||
          (prenda.seguimientos && Object.keys(prenda.seguimientos).length > 0) ||
          (prenda.ultimo_recibo_numero && prenda.ultimo_recibo_numero !== '-')
        );

        if (!tieneSeguimiento && Array.isArray(globalThis.prendasData) && globalThis.prendasData.length > 0) {
          const prendaId = prenda?.id || prenda?.prenda_pedido_id;
          const prendaEnriquecida = globalThis.prendasData.find(p =>
            String(p?.id) === String(prendaId) || String(p?.prenda_pedido_id) === String(prendaId)
          );

          if (prendaEnriquecida) {
            prenda = Object.assign({}, prendaEnriquecida, prenda);
            console.log('[showPrendaTracking] Usando prenda enriquecida desde prendasData:', prendaEnriquecida);
          }
        }
      } catch (e) {
        console.warn('[showPrendaTracking] Error hidratando prenda desde prendasData:', e);
      }
      
      globalThis.currentPrendaData = prenda;
      
      // Cerrar overlay de prendas
      const overlaySelector = document.getElementById('trackingPrendasSelectorOverlay');
      if (overlaySelector) {
        console.log('[showPrendaTracking] Cerrando overlay selector...');
        if (typeof cerrarSelectorPrendas === 'function') {
          cerrarSelectorPrendas();
        }
      }
      
      // Mostrar modal de seguimiento
      console.log('[showPrendaTracking] Buscando modal...');
      const modal = document.getElementById('orderTrackingModal');
      if (modal) {
        console.log('[showPrendaTracking] Modal encontrado, agregando clase show...');
        modal.classList.add('show');
        
        // FORZAR ESTILO DIRECTAMENTE CON JAVASCRIPT
        modal.style.setProperty('display', 'flex', 'important');
        modal.style.setProperty('visibility', 'visible', 'important');
        modal.style.setProperty('opacity', '1', 'important');
        
        // Iniciar timer para contadores dinámicos
        if (typeof iniciarTimerContadores === 'function') {
          iniciarTimerContadores();
        }
        modal.style.setProperty('z-index', '9999999', 'important');
        modal.style.setProperty('position', 'fixed', 'important');
        modal.style.setProperty('top', '0', 'important');
        modal.style.setProperty('left', '0', 'important');
        modal.style.setProperty('width', '100vw', 'important');
        modal.style.setProperty('height', '100vh', 'important');
        modal.style.setProperty('background', 'rgba(0, 0, 0, 0.5)', 'important');
        modal.style.setProperty('align-items', 'center', 'important');
        modal.style.setProperty('justify-content', 'center', 'important');
        
        // Asegurar que el botón volver funcione
        if (typeof setupBackButton === 'function') {
          setupBackButton();
        }
        
        console.log('[showPrendaTracking] Modal mostrado con estilos forzados');
        
        // Debug visual - verificar estado del modal
        setTimeout(() => {
          const modalElement = document.getElementById('orderTrackingModal');
          const computedStyle = globalThis.getComputedStyle(modalElement);
          console.log('[showPrendaTracking] DEBUG - Estado del modal:', {
            display: computedStyle.display,
            visibility: computedStyle.visibility,
            opacity: computedStyle.opacity,
            zIndex: computedStyle.zIndex,
            hasClass: modalElement.classList.contains('show'),
            inlineDisplay: modalElement.style.display,
            inlineVisibility: modalElement.style.visibility
          });
        }, 100);
      } else {
        console.error('[showPrendaTracking] Modal no encontrado');
        return;
      }
      
      // Ocultar vista de prendas y mostrar timeline
      console.log('[showPrendaTracking] Actualizando vistas...');
      const prendasContainer = document.getElementById('trackingPrendasContainer');
      if (prendasContainer && prendasContainer.parentElement) {
        prendasContainer.parentElement.style.display = 'none';
      }
      const timelineSection = document.getElementById('trackingTimelineSection');
      if (timelineSection) {
        timelineSection.style.display = 'block';
      }
      
      // CONTROLAR VISIBILIDAD DE BOTÓN AGREGAR BASADO EN READONLY
      const btnAgregar = document.getElementById('btnOpenAddProcesoModal');
      if (btnAgregar) {
        if (prenda?.readonly) {
          console.log('[showPrendaTracking] Modo READONLY: Ocultando botón AGREGAR ÁREA');
          btnAgregar.style.display = 'none';
          btnAgregar.disabled = true;
        } else {
          console.log('[showPrendaTracking] Modo NORMAL: Mostrando botón AGREGAR ÁREA');
          btnAgregar.style.display = 'block';
          btnAgregar.disabled = false;
        }
      }
      
      // Actualizar nombre de la prenda y número de recibo
      console.log('[showPrendaTracking] Actualizar nombre de la prenda y número de recibo');
      
      const nombreElement = document.getElementById('trackingPrendaName');
      if (nombreElement) {
        nombreElement.textContent = prenda.nombre_prenda || `Prenda ${prenda.id}`;
      }
      
      // Actualizar el header del recibo con el número más reciente
      const reciboHeaderElement = document.getElementById('trackingPrendaReciboHeader');
      if (reciboHeaderElement) {
        // Resolver área actual (prioridad: último proceso > área en prenda > área del pedido)
        let areaActual = '-';
        if (prenda.ultimo_proceso_area) {
          areaActual = prenda.ultimo_proceso_area;
        } else if (prenda.area && String(prenda.area).trim() !== '') {
          areaActual = prenda.area;
        } else if (globalThis.currentOrderData?.area && String(globalThis.currentOrderData.area).trim() !== '') {
          areaActual = globalThis.currentOrderData.area;
        }

        // Usar ultimo_recibo_numero directamente (más confiable)
        const numeroReciboCostura = this.getConsecutivoCosturaFromPrenda(prenda);
        const numeroRecibo = prenda.ultimo_recibo_numero || numeroReciboCostura || 'Sin recibo';
        
        console.log('[DEBUG] Datos de prenda para recibo:', {
          'prenda_id': prenda.id || prenda.prenda_pedido_id,
          'ultimo_recibo_numero': prenda.ultimo_recibo_numero,
          'consecutivo_costura_desde_prenda': numeroReciboCostura,
          'area_actual_resuelta': areaActual
        });
        
        // Actualizar header con número de recibo y área
        if (numeroRecibo && numeroRecibo !== '-' && numeroRecibo !== 'Sin recibo') {
          reciboHeaderElement.textContent = areaActual && areaActual !== '-'
            ? `Recibo #${numeroRecibo} - ${areaActual}`
            : `Recibo #${numeroRecibo}`;
          console.log('[DEBUG] Header actualizado con:', numeroRecibo, 'Area:', areaActual);
        } else {
          reciboHeaderElement.textContent = areaActual && areaActual !== '-'
            ? `Sin recibo - ${areaActual}`
            : 'Sin recibo';
          console.log('[DEBUG] Sin número de recibo disponible');
        }
      }
      
      // Para el elemento trackingPrendaRecibo (compatible con código existente)
      const numeroReciboCostura = this.getConsecutivoCosturaFromPrenda(prenda);
      const numeroReciboFinal = prenda.ultimo_recibo_numero || numeroReciboCostura || null;
      let numeroRecibo = 'Sin recibo';
      if (numeroReciboFinal && numeroReciboFinal !== '-') {
        numeroRecibo = `Recibo #${numeroReciboFinal}`;
      }
      
      const reciboElement = document.getElementById('trackingPrendaRecibo');
      if (reciboElement) {
        console.log('[showPrendaTracking] Actualizando trackingPrendaRecibo:', {
          antes: reciboElement.textContent,
          despues: numeroRecibo,
          fuente: prenda.ultimo_recibo_numero ? 'ultimo_recibo_numero' : (numeroReciboCostura ? 'consecutivos.COSTURA' : 'sin_recibo')
        });
        reciboElement.textContent = numeroRecibo;
      }

      const trackingOrderReciboEl = document.getElementById('trackingOrderRecibo');
      if (trackingOrderReciboEl) {
        const nuevoValor = numeroReciboFinal || '-';
        console.log('[showPrendaTracking] Actualizando trackingOrderRecibo:', {
          prendaId: prenda.id || prenda.prenda_pedido_id,
          antes: trackingOrderReciboEl.textContent,
          despues: nuevoValor,
          fuente: prenda.ultimo_recibo_numero ? 'ultimo_recibo_numero' : (numeroReciboCostura ? 'consecutivos.COSTURA' : 'fallback')
        });
        trackingOrderReciboEl.textContent = nuevoValor;
      }
      
      // Renderizar timeline de seguimiento
      console.log('[showPrendaTracking] Renderizando timeline...');
      this.renderPrendaTrackingTimeline(prenda);
      
      console.log('[showPrendaTracking] FINALIZADO - Seguimiento mostrado exitosamente');
      
    } catch (error) {
      console.error('[showPrendaTracking] Error:', error);
      if (typeof showError === 'function') {
        showError('Error al cargar seguimiento de la prenda');
      }
    }
  }

  // Renderizar timeline de seguimiento de prenda
  renderPrendaTrackingTimeline(prenda) {
    const container = document.getElementById('trackingTimelineContainer');
    if (!container) return;

    console.log('[renderPrendaTrackingTimeline] Renderizando timeline para prenda:', prenda);
    console.log('[renderPrendaTrackingTimeline] Seguimientos por área en prenda:', prenda.seguimientos_por_area);

    // Botón de volver (eliminado - ya está en el header)
    container.innerHTML = '';

    // Renderizar seguimientos por área (procesos de producción)
    this.renderSeguimientosPorArea(prenda, container);

    // Renderizar seguimientos por tipo de recibo (ELIMINADO - no mostrar recibos en modal de seguimiento)
    // renderSeguimientosPorTipo(prenda, container);

    // Si no hay seguimientos por área, mostrar mensaje
    if (!prenda.seguimientos_por_area || Object.keys(prenda.seguimientos_por_area).length === 0) {
      this.renderNoSeguimiento(container);
    }
  }

  // Renderizar seguimientos por área (procesos)
  renderSeguimientosPorArea(prenda, container) {
    const seguimientosPorArea = prenda.seguimientos_por_area || {};
    const hasSeguimientos = Object.keys(seguimientosPorArea).length > 0;

    let reciboCreatedAt = null;

    let activationSection = null;
    let areasSection = null;

    // Sección: fechas relevantes (creación de orden / activación del recibo)
    try {
      activationSection = document.createElement('div');
      activationSection.className = 'tracking-section tracking-section-activation';

      const activationTitle = document.createElement('div');
      activationTitle.className = 'tracking-section-title';
      activationTitle.textContent = 'Activación del recibo:';
      activationSection.appendChild(activationTitle);

      const fechasWrapper = document.createElement('div');
      fechasWrapper.className = 'tracking-info-row';

      // Usar datos_activacion_recibo del backend (más confiable)
      const datosActivacion = prenda?.datos_activacion_recibo || {};
      
      const fechaCreacionOrden =
        datosActivacion.fecha_creacion_orden
        || prenda?.fecha_creacion
        || globalThis.currentOrderData?.fecha_creacion
        || globalThis.currentOrderData?.created_at
        || null;
      const fechaCreacionOrdenFormateada =
        datosActivacion.fecha_creacion_orden_formateada
        || (typeof formatDate === 'function' && fechaCreacionOrden ? formatDate(fechaCreacionOrden) : null);
      
      const fechaActivacionRecibo =
        datosActivacion.fecha_activacion_recibo
        || globalThis.currentConsecutivoCosturaData?.fecha_creacion
        || null;
      const fechaActivacionReciboFormateada =
        datosActivacion.fecha_activacion_recibo_formateada
        || (typeof formatDate === 'function' && fechaActivacionRecibo ? formatDate(fechaActivacionRecibo) : null);
      
      let diasTranscurridos = datosActivacion.dias_transcurridos;
      const diasTranscurridosTexto = datosActivacion.dias_transcurridos_texto || '-';
      if ((diasTranscurridos === null || diasTranscurridos === undefined) && fechaCreacionOrden && fechaActivacionRecibo) {
        const ini = typeof toDateObject === 'function' ? toDateObject(fechaCreacionOrden) : null;
        const fin = typeof toDateObject === 'function' ? toDateObject(fechaActivacionRecibo) : null;
        if (ini && fin) {
          diasTranscurridos = Math.max(0, Math.floor((fin.getTime() - ini.getTime()) / (1000 * 60 * 60 * 24)));
        }
      }

      // Fecha base para área virtual de Insumos cuando el backend no la envía.
      reciboCreatedAt = fechaActivacionRecibo || fechaCreacionOrden || null;

      console.log('[prendas-renderer] datosActivacion:', {
        fechaCreacionOrden: fechaCreacionOrdenFormateada,
        fechaActivacionRecibo: fechaActivacionReciboFormateada,
        diasTranscurridos: diasTranscurridosTexto
      });

      const cardCreacionOrden = document.createElement('div');
      cardCreacionOrden.className = 'tracking-info-card';
      cardCreacionOrden.innerHTML = `
        <div class="tracking-info-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
        </div>
        <div class="tracking-info-content">
          <span class="tracking-info-label">Fecha creación orden</span>
          <span class="tracking-info-value">${fechaCreacionOrdenFormateada || '-'}</span>
        </div>
      `;

      const cardActivacionRecibo = document.createElement('div');
      cardActivacionRecibo.className = 'tracking-info-card';
      cardActivacionRecibo.innerHTML = `
        <div class="tracking-info-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M9 14l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            <path d="M12 6v4m0 2h2"></path>
          </svg>
        </div>
        <div class="tracking-info-content">
          <span class="tracking-info-label">Fecha activación recibo</span>
          <span class="tracking-info-value">${fechaActivacionReciboFormateada || '-'}</span>
        </div>
      `;

      // Tiempo transcurrido
      // Wrapper para las dos tarjetas de fechas (lado izquierdo)
      const fechasLeft = document.createElement('div');
      fechasLeft.className = 'tracking-activation-left';
      fechasLeft.appendChild(cardCreacionOrden);
      fechasLeft.appendChild(cardActivacionRecibo);
      fechasWrapper.appendChild(fechasLeft);

      // Badge de total días (lado derecho)
      const totalDiasDisplay = diasTranscurridos !== null && diasTranscurridos !== undefined 
        ? (diasTranscurridos === 1 ? '1 día' : `${diasTranscurridos} días`)
        : '-';
      
      const badgeTotalDias = document.createElement('div');
      badgeTotalDias.className = 'tracking-activation-right';
      badgeTotalDias.innerHTML = `
        <div class="tracking-total-dias-badge">
          <div class="tracking-total-dias-label">TOTAL DÍAS</div>
          <div class="tracking-total-dias-value">${totalDiasDisplay}</div>
        </div>
      `;
      fechasWrapper.appendChild(badgeTotalDias);
      activationSection.appendChild(fechasWrapper);
      container.appendChild(activationSection);
    } catch (e) {
      console.warn('[renderSeguimientosPorArea] No se pudo renderizar sección de fechas:', e);
    }

    // Sección: Seguimiento por áreas + botón Agregar Área
    areasSection = document.createElement('div');
    areasSection.className = 'tracking-section tracking-section-areas';

    const areasHeader = document.createElement('div');
    areasHeader.className = 'tracking-section-header';

    const headerTitle = document.createElement('div');
    headerTitle.className = 'tracking-section-title';
    headerTitle.textContent = 'Seguimiento por áreas:';
    areasHeader.appendChild(headerTitle);

    const btnAgregarArea = document.getElementById('btnAgregarArea');
    if (btnAgregarArea) {
      areasHeader.appendChild(btnAgregarArea);
    }

    areasSection.appendChild(areasHeader);
    container.appendChild(areasSection);

    // Insertar área virtual "Insumos" (fecha llegada = activación recibo)
    const mergedAreas = { ...seguimientosPorArea };
    const hasInsumos = Object.keys(mergedAreas).some(k => String(k || '').toLowerCase() === 'insumos');
    if (!hasInsumos && reciboCreatedAt) {
      const areaCorteKey = Object.keys(mergedAreas).find(k => String(k || '').toLowerCase().includes('corte')) || null;
      let areaEnvioProduccionKey = areaCorteKey;
      let fechaEnvioProduccion = areaCorteKey ? (mergedAreas[areaCorteKey]?.fecha_inicio || null) : null;

      // Fallback: si no hay Corte, usar la primera área con fecha_inicio más temprana (lo más cercano a "envío")
      if (!fechaEnvioProduccion) {
        let bestKey = null;
        let bestDate = null;
        Object.entries(mergedAreas).forEach(([k, v]) => {
          if (String(k || '').toLowerCase() === 'insumos') return;
          const d = typeof toDateObject === 'function' ? toDateObject(v?.fecha_inicio) : null;
          if (!d) return;
          if (!bestDate || d.getTime() < bestDate.getTime()) {
            bestDate = d;
            bestKey = k;
          }
        });
        if (bestKey && bestDate) {
          areaEnvioProduccionKey = bestKey;
          fechaEnvioProduccion = mergedAreas[bestKey]?.fecha_inicio || null;
        }
      }

      const yaEnviadoAProduccion = Boolean(fechaEnvioProduccion);

      mergedAreas['Insumos'] = {
        estado: yaEnviadoAProduccion ? 'Enviado a producción' : 'Llegó a insumos',
        encargado: '-',
        fecha_inicio: reciboCreatedAt,
        fecha_fin: fechaEnvioProduccion,
        duracion_dias: null,
        icono: 'inventory_2',
        esta_activo: !yaEnviadoAProduccion,
        can_edit: false,
        hide_encargado: true,
        tiempo_transcurrido: (function() {
          const ini = typeof toDateObject === 'function' ? toDateObject(reciboCreatedAt) : null;
          const fin = typeof toDateObject === 'function' ? toDateObject(fechaEnvioProduccion) : null;
          if (!ini || !fin) return null;
          return typeof formatDurationHuman === 'function' 
            ? formatDurationHuman(Math.max(0, fin.getTime() - ini.getTime()))
            : null;
        })()
      };
    }

    const orderedEntries = [];
    if (mergedAreas['Insumos']) {
      orderedEntries.push(['Insumos', mergedAreas['Insumos']]);
    }
    Object.entries(mergedAreas).forEach(([area, data]) => {
      if (String(area || '').toLowerCase() === 'insumos') return;
      orderedEntries.push([area, data]);
    });

    orderedEntries.forEach(([area, data]) => {
      if (typeof createAreaCard === 'function') {
        const areaCard = createAreaCard(area, data, prenda?.readonly || false);
        areasSection.appendChild(areaCard);
      }
    });

    if (!hasSeguimientos && !mergedAreas['Insumos']) {
      // No agregar cards vacías; renderNoSeguimiento se encarga del mensaje final.
      return;
    }
  }

  // Mostrar mensaje si no hay seguimientos
  renderNoSeguimiento(container) {
    const noSeguimiento = document.createElement('div');
    noSeguimiento.className = 'tracking-no-seguimiento';

    // Mantener el mensaje original
    noSeguimiento.innerHTML = '<p>No hay seguimientos registrados para esta prenda</p>';
    container.appendChild(noSeguimiento);

    // Usar la UI original del tracking para mostrar el área actual y encargado si se puede
    // (sin inventar una vista nueva). La edición/creación se hace con el botón "Agregar Área".
    const prenda = globalThis.currentPrendaData || {};
    const procesoIdFallback = globalThis.currentConsecutivoCosturaData?.proceso_id || null;
    const tieneProcesoReal = Boolean(prenda?.ultimo_proceso_id || procesoIdFallback);

    const areaActual = prenda?.ultimo_proceso_area
      || (prenda?.area && String(prenda.area).trim() !== '' ? prenda.area : null)
      || (globalThis.currentOrderData?.area && String(globalThis.currentOrderData.area).trim() !== '' ? globalThis.currentOrderData.area : null)
      || null;

    // Encargado real solo desde procesos_prenda; fallback a /consecutivo-costura si está disponible
    const encargadoActual = prenda?.ultimo_proceso_encargado
      || globalThis.currentConsecutivoCosturaData?.encargado
      || null;

    // Si hay algo que mostrar, renderizar una tarjeta estándar de área.
    if (tieneProcesoReal && areaActual && typeof createAreaCard === 'function') {
      const estadoUltimo = prenda?.ultimo_proceso_estado || 'Pendiente';
      const estaActivo = estadoUltimo !== 'Completado';

      const fechaInicioFallback = globalThis.currentConsecutivoCosturaData?.fecha_inicio || null;
      const fechaFinFallback = globalThis.currentConsecutivoCosturaData?.fecha_fin || null;

      const card = createAreaCard(areaActual, {
        id: prenda?.ultimo_proceso_id || procesoIdFallback,
        can_edit: true,
        area: areaActual,
        estado: estadoUltimo,
        fecha_inicio: prenda?.ultimo_proceso_fecha_inicio || fechaInicioFallback,
        fecha_fin: prenda?.ultimo_proceso_fecha_fin || fechaFinFallback,
        encargado: encargadoActual || 'No asignado',
        observaciones: prenda?.ultimo_proceso_observaciones || '',
        codigo_referencia: prenda?.ultimo_proceso_codigo_referencia || null,
        dias_duracion: prenda?.ultimo_proceso_dias_duracion || null,
        esta_activo: estaActivo,
      }, prenda?.readonly || false);
      container.appendChild(card);
    }
  }
}

// Exportar para uso global
globalThis.PrendasRenderer = PrendasRenderer;
globalThis.prendasRenderer = new PrendasRenderer();

// Funciones globales para compatibilidad (sin sobreescribir handlers modernos)
const hasModernTrackingHandlers =
  typeof globalThis.openOrderTracking === 'function' &&
  typeof globalThis.handleVerProcesos === 'function';

if (!hasModernTrackingHandlers) {
  globalThis.renderPrendas = (prendas) => globalThis.prendasRenderer.renderPrendas(prendas);
  globalThis.showPrendaTrackingFromTable = (index, prendaId) => globalThis.prendasRenderer.showPrendaTrackingFromTable(index, prendaId);
  globalThis.showPrendaTracking = (prenda) => globalThis.prendasRenderer.showPrendaTracking(prenda);
  globalThis.renderPrendaTrackingTimeline = (prenda) => globalThis.prendasRenderer.renderPrendaTrackingTimeline(prenda);
} else {
  console.log('[prendas-renderer] Tracking moderno detectado, no se sobreescriben handlers globales');
}
