/**
 * TrackingTimelineController
 *
 * Responsabilidad: renderizar y mostrar el timeline de una prenda.
 * Mantiene el handler principal enfocado en orquestación.
 */
export class TrackingTimelineController {
  constructor({
    orderState,
    svgIcons,
    updateRenderer,
    formatDate,
    showError,
    closePrendasSelector,
    setupBackButton,
    startCountersTimer = () => {}, // Default no-op function
  }) {
    this.orderState = orderState;
    this.svgIcons = svgIcons;
    this.updateRenderer = updateRenderer;
    this.formatDate = formatDate;
    this.showError = showError;
    this.closePrendasSelector = closePrendasSelector;
    this.setupBackButton = setupBackButton;
    this.startCountersTimer = typeof startCountersTimer === 'function' ? startCountersTimer : () => {};
  }

  async showPrendaTracking(prenda) {
    try {
      prenda = this.#hydratePrenda(prenda);
      this.orderState.setCurrentPrenda(prenda);

      if (document.getElementById('trackingPrendasSelectorOverlay')) {
        this.closePrendasSelector();
      }

      this.updateRenderer.toggleModal('orderTrackingModal', true);
      this.startCountersTimer();
      this.setupBackButton();

      const prendasContainer = document.getElementById('trackingPrendasContainer');
      const timelineSection = document.getElementById('trackingTimelineSection');
      if (prendasContainer?.parentElement) {
        prendasContainer.parentElement.style.display = 'none';
      }
      if (timelineSection) {
        timelineSection.style.display = 'block';
      }

      this.updateRenderer.updateAddProcessButton(prenda, !!prenda?.readonly);
      this.updateRenderer.updatePrendaName(prenda);

      const areaActual = prenda.area_actual || '-';
      const numeroRecibo = prenda.recibo_display || 'Sin recibo';
      this.updateRenderer.updateReciboHeader(numeroRecibo, areaActual);

      this.renderPrendaTrackingTimeline(prenda);
    } catch (error) {
      console.error('[TrackingTimelineController.showPrendaTracking] Error:', error);
      this.showError('Error al cargar seguimiento de la prenda');
    }
  }

  renderPrendaTrackingTimeline(prenda) {
    const container = document.getElementById('trackingTimelineContainer');
    if (!container) return;

    console.log('[TrackingTimelineController.renderPrendaTrackingTimeline] Renderizando timeline para prenda:', prenda);

    container.innerHTML = '';
    this.renderSeguimientosPorArea(prenda, container);

    if (!prenda.seguimientos_por_area || Object.keys(prenda.seguimientos_por_area).length === 0) {
      this.renderNoSeguimiento(container);
    }
  }

  renderSeguimientosPorArea(prenda, container) {
    const seguimientosPorArea = prenda.seguimientos_por_area || {};
    if (Object.keys(seguimientosPorArea).length === 0) {
      return;
    }

    const datosActivacion = prenda.datos_activacion_recibo || {};

    const activationSection = document.createElement('div');
    activationSection.className = 'tracking-section tracking-section-activation';

    const activationTitle = document.createElement('div');
    activationTitle.className = 'tracking-section-title';
    activationTitle.textContent = 'Activación del recibo:';
    activationSection.appendChild(activationTitle);

    const fechasWrapper = document.createElement('div');
    fechasWrapper.className = 'tracking-info-row';

    const createInfoCard = (label, value, icon) => {
      const card = document.createElement('div');
      card.className = 'tracking-info-card';
      card.innerHTML = `
        <div class="tracking-info-icon">${icon}</div>
        <div class="tracking-info-content">
          <span class="tracking-info-label">${label}</span>
          <span class="tracking-info-value">${value}</span>
        </div>
      `;
      return card;
    };

    fechasWrapper.appendChild(createInfoCard(
      'Fecha creación orden',
      datosActivacion.fecha_creacion_orden_formateada || '-',
      this.svgIcons.calendar()
    ));

    fechasWrapper.appendChild(createInfoCard(
      'Fecha activación recibo',
      datosActivacion.fecha_activacion_recibo_formateada || '-',
      this.svgIcons.checkCircle()
    ));

    fechasWrapper.appendChild(createInfoCard(
      'Tiempo transcurrido',
      datosActivacion.tiempo_transcurrido_completo || '-',
      this.svgIcons.clock()
    ));

    activationSection.appendChild(fechasWrapper);
    container.appendChild(activationSection);

    const areasSection = document.createElement('div');
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

    Object.entries(seguimientosPorArea).forEach(([area, data]) => {
      // Validar que data tenga la estructura esperada
      if (!data || typeof data !== 'object') {
        console.warn('[TrackingTimelineController] Datos inválidos para área:', area, data);
        return;
      }
      
      const areaCard = this.createAreaCard(area, data, prenda?.readonly || false);
      areasSection.appendChild(areaCard);
    });
  }

  renderNoSeguimiento(container) {
    const noSeguimiento = document.createElement('div');
    noSeguimiento.className = 'tracking-no-seguimiento';
    noSeguimiento.innerHTML = '<p>No hay seguimientos registrados para esta prenda</p>';
    container.appendChild(noSeguimiento);

    const prenda = this.orderState.getCurrentPrenda() || {};
    const procesoIdFallback = this.orderState.getConsecutivoCosturaData()?.proceso_id || null;
    const tieneProcesoReal = Boolean(prenda?.ultimo_proceso_id || procesoIdFallback);

    const areaActual = prenda.area_actual
      || prenda?.ultimo_proceso_area
      || (prenda?.area && String(prenda.area).trim() !== '' ? prenda.area : null)
      || null;

    const encargadoActual = prenda?.ultimo_proceso_encargado
      || this.orderState.getConsecutivoCosturaData()?.encargado
      || null;

    const encargadoNombreActual = prenda?.ultimo_proceso_encargado_nombre
      || null;

    if (!tieneProcesoReal || !areaActual) {
      return;
    }

    const estadoUltimo = prenda?.ultimo_proceso_estado || 'Pendiente';
    const estaActivo = estadoUltimo !== 'Completado';

    const fechaInicioFallback = this.orderState.getConsecutivoCosturaData()?.fecha_inicio || null;
    const fechaFinFallback = this.orderState.getConsecutivoCosturaData()?.fecha_fin || null;
    const fechaInicioReal = prenda?.ultimo_proceso_fecha_inicio || fechaInicioFallback;
    const fechaFinReal = prenda?.ultimo_proceso_fecha_fin || fechaFinFallback;

    const areaLower = String(areaActual).toLowerCase();
    const areasConfig = this.orderState.getAreasConfig();
    const areasQueRequierenEncargado = areasConfig?.areas_que_requieren_encargado || ['corte', 'costura', 'control de calidad'];
    const needsEncargado = areasQueRequierenEncargado.some((area) => areaLower.includes(area));

    const metadata = {
      isInsumos: areaLower === 'insumos',
      isCorte: areaLower.includes('corte'),
      isCostura: areaLower.includes('costura'),
      needsEncargado,
      shouldHideEncargado: areaLower === 'insumos'
    };

    const card = this.createAreaCard(areaActual, {
      id: prenda?.ultimo_proceso_id || procesoIdFallback,
      can_edit: true,
      area: areaActual,
      estado: estadoUltimo,
      fecha_inicio: fechaInicioReal,
      fecha_fin: fechaFinReal,
      encargado: encargadoActual || 'No asignado',
      encargado_nombre: encargadoNombreActual,
      observaciones: prenda?.ultimo_proceso_observaciones || '',
      codigo_referencia: prenda?.ultimo_proceso_codigo_referencia || null,
      dias_duracion: prenda?.ultimo_proceso_dias_duracion || null,
      esta_activo: estaActivo,
      metadata,
      duraciones: {
        duracion_asignacion: '---',
        duracion_en_area: '---',
        total_dias: '---',
        estado_display: estadoUltimo,
        esta_activo_display: estaActivo
      },
      fechas_formateadas: {
        fecha_llegada: this.formatDate(fechaInicioReal) || '---',
        fecha_asignacion: '---',
        fecha_fin: this.formatDate(fechaFinReal) || '---'
      }
    }, prenda?.readonly || false);

    container.appendChild(card);
  }

  createAreaCard(area, data, readonly = false) {
    const card = document.createElement('div');
    
    // Asegurar que data sea un objeto válido con defaul values
    if (!data || typeof data !== 'object') {
      console.warn('[TrackingTimelineController.createAreaCard] Datos inválidos para:', area, data);
      data = {};
    }
    
    const metadata = data.metadata || {};
    const duraciones = data.duraciones || {};
    const fechasFormateadas = data.fechas_formateadas || {};

    // Valores por defecto si el objeto está vacío
    const estadoDisplay = duraciones?.estado_display || 'Pendiente';
    const estaActivoDisplay = duraciones?.esta_activo_display ?? true;

    if (readonly) {
      card.classList.add('tracking-readonly-mode');
    }

    const iconSvg = this.svgIcons.get(area);
    
    //  USAR FECHAS DEL BACKEND si están disponibles, formatearlas si es necesario
    const fechaLlegada = fechasFormateadas?.fecha_llegada || this.formatDate(data.fecha_inicio) || '---';
    const fechaAsignacion = fechasFormateadas?.fecha_asignacion || this.formatDate(data.fecha_de_asignacion_encargado) || '---';
    const fechaFin = fechasFormateadas?.fecha_fin || this.formatDate(data.fecha_fin) || this.formatDate(data.fecha_completado) || '---';

    //  USAR VALORES DEL BACKEND: duraciones.total_dias_numero que el backend calcula
    const formatDuracionDias = (dias) => {
      if (dias === null || dias === undefined) return '---';
      dias = Number(dias);
      if (dias === 0) return '0 días';
      return `${dias} día${dias !== 1 ? 's' : ''}`;
    };

    const duracionAsignacion = (() => {
      if (duraciones?.duracion_asignacion !== undefined && duraciones?.duracion_asignacion !== null) {
        return formatDuracionDias(duraciones.duracion_asignacion);
      }
      return duraciones?.duracion_asignacion_display || '---';
    })();

    const duracionEnArea = (() => {
      if (duraciones?.duracion_en_area_dias !== undefined && duraciones?.duracion_en_area_dias !== null) {
        return formatDuracionDias(duraciones.duracion_en_area_dias);
      }
      return duraciones?.duracion_en_area || '---';
    })();

    const totalDiasAreaDisplay = (() => {
      // Intentar obtener total_dias_numero (nuevo formato backend)
      if (duraciones?.total_dias_numero !== undefined && duraciones?.total_dias_numero !== null) {
        return formatDuracionDias(duraciones.total_dias_numero);
      }
      // Fallback a total_dias (formato antiguo)
      return duraciones?.total_dias || '---';
    })();

    const accionesHtml = readonly ? '' : `${(data.id || data.can_edit) ? `
            <button class="tracking-edit-btn" onclick="${data.id ? `handleEditarProceso(${data.id}, '${area}', ${JSON.stringify(data).replace(/"/g, '&quot;')}, event)` : `handleCrearProcesoDesdeArea('${area}', event, '${String(data.encargado || '').replace(/'/g, "\\'")}')`}" title="Editar proceso">
              ${this.svgIcons.edit()}
            </button>
            ${data.id ? `
            <button class="tracking-delete-btn" onclick="handleEliminarProceso(${data.id}, '${area}', event)" title="Eliminar proceso">
              ${this.svgIcons.delete()}
            </button>
            ` : ''}
            ` : ''}`;

    card.className = `tracking-area-card tracking-area-card-v2 ${estaActivoDisplay ? 'pending' : 'completed'}`;

    if (metadata.isInsumos) {
      card.innerHTML = `
        <div class="tracking-area-v2-left">
          <div class="tracking-area-v2-icon">${iconSvg}</div>
          <div class="tracking-area-v2-name">${area}</div>
        </div>
        <div class="tracking-area-v2-body">
          <div class="tracking-area-v2-row">
            <div class="tracking-area-v2-field">
              <div class="tracking-area-v2-label">Fecha de llegada:</div>
              <div class="tracking-area-v2-pill">${fechaLlegada}</div>
            </div>
            <div class="tracking-area-v2-field">
              <div class="tracking-area-v2-label">Fecha de envío a producción</div>
              <div class="tracking-area-v2-pill">${fechaFin}</div>
            </div>
            <div class="tracking-area-v2-field tracking-area-v2-field-right"></div>
          </div>
          <div class="tracking-area-v2-footer">
            <div class="tracking-area-v2-status">
              <span class="tracking-days-badge ${estaActivoDisplay ? '' : 'tracking-days-badge-zero'}">${estadoDisplay}</span>
            </div>
            <div class="tracking-area-v2-actions">${accionesHtml}</div>
            <div class="tracking-area-v2-total-days">
              <span class="tracking-area-v2-total-label">Total Días:</span>
              <span class="tracking-area-v2-total-value">${totalDiasAreaDisplay}</span>
            </div>
          </div>
        </div>
      `;
      return card;
    }

    card.innerHTML = `
      <div class="tracking-area-v2-left">
        <div class="tracking-area-v2-icon">${iconSvg}</div>
        <div class="tracking-area-v2-name">${area}</div>
      </div>
      <div class="tracking-area-v2-body">
        <div class="tracking-area-v2-row">
          <div class="tracking-area-v2-field">
            <div class="tracking-area-v2-label">Fecha de llegada:</div>
            <div class="tracking-area-v2-pill">${fechaLlegada}</div>
          </div>
          ${!data.encargado || data.encargado.trim() === '' ? `
          <div class="tracking-area-v2-field tracking-area-v2-field-right">
            <div class="tracking-area-v2-label">Fecha fin</div>
            <div class="tracking-area-v2-pill">${fechaFin}</div>
          </div>
          ` : `
          <div class="tracking-area-v2-field">
            <div class="tracking-area-v2-label">Fecha de asignación de ${String(area).toLowerCase()}:</div>
            <div class="tracking-area-v2-pill">${fechaAsignacion}</div>
          </div>
          <div class="tracking-area-v2-field tracking-area-v2-field-right">
            <div class="tracking-area-v2-label">Duración asignación de ${String(area).toLowerCase()}:</div>
            <div class="tracking-area-v2-badge">${duracionAsignacion}</div>
          </div>
          `}
        </div>
        <div class="tracking-area-v2-row">
          ${!data.encargado || data.encargado.trim() === '' ? '' : `
          ${metadata.shouldHideEncargado || data.hide_encargado ? '' : `
          <div class="tracking-area-v2-field">
            <div class="tracking-area-v2-label">Encargado:</div>
            <div class="tracking-area-v2-pill">${data.encargado_nombre || data.encargado || '---'}</div>
          </div>
          `}
          <div class="tracking-area-v2-field">
            <div class="tracking-area-v2-label">Fecha fin</div>
            <div class="tracking-area-v2-pill">${fechaFin}</div>
          </div>
          <div class="tracking-area-v2-field tracking-area-v2-field-right">
            <div class="tracking-area-v2-label">Duración en ${area}</div>
            <div class="tracking-area-v2-badge">${duracionEnArea}</div>
          </div>
          `}
        </div>
        <div class="tracking-area-v2-footer">
          <div class="tracking-area-v2-status">
            <span class="tracking-days-badge ${estaActivoDisplay ? '' : 'tracking-days-badge-zero'}">${estadoDisplay}</span>
          </div>
          <div class="tracking-area-v2-actions">${accionesHtml}</div>
          <div class="tracking-area-v2-total-days">
            <span class="tracking-area-v2-total-label">Total Días:</span>
            <span class="tracking-area-v2-total-value">${totalDiasAreaDisplay}</span>
          </div>
        </div>
      </div>
    `;

    return card;
  }

  #hydratePrenda(prenda) {
    const tieneSeguimiento = prenda && (
      (prenda.seguimientos_por_area && Object.keys(prenda.seguimientos_por_area).length > 0) ||
      (prenda.seguimientos && Object.keys(prenda.seguimientos).length > 0) ||
      (prenda.ultimo_recibo_numero && prenda.ultimo_recibo_numero !== '-')
    );

    if (!tieneSeguimiento && this.orderState.hasPrendas()) {
      const prendaId = prenda?.id || prenda?.prenda_pedido_id;
      const prendaEnriquecida = this.orderState.getPrendas().find((item) =>
        String(item?.id) === String(prendaId) || String(item?.prenda_pedido_id) === String(prendaId)
      );

      if (prendaEnriquecida) {
        return Object.assign({}, prendaEnriquecida, prenda);
      }
    }

    return prenda;
  }
}