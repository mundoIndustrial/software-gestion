/**
 * Tracking Modal Handler - Seguimiento por Prenda
 * Maneja la integración del modal de seguimiento con la vista de órdenes
 * Funcionalidad completa de seguimiento por prenda con áreas y procesos
 */

(function() {
  'use strict';

  let currentOrderData = null;
  let currentPrendaData = null;

  // Inicializar listeners del modal
  function initTrackingModalListeners() {
    // Cerrar modal al hacer clic en el overlay
    const overlay = document.getElementById('trackingModalOverlay');
    if (overlay) {
      overlay.addEventListener('click', closeTrackingModal);
    }

    // Cerrar modal con botón X (si existe)
    const closeBtn = document.querySelector('.tracking-modal-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', closeTrackingModal);
    }

    // Botón de volver a prendas (se configura en setupBackButton)
    const backBtn = document.getElementById('backToPrendasBtn');
    // No agregar event listener aquí, se maneja en setupBackButton()

    // Botón de abrir modal agregar proceso
    const btnOpenAddProcesoModal = document.getElementById('btnOpenAddProcesoModal');
    if (btnOpenAddProcesoModal) {
      btnOpenAddProcesoModal.addEventListener('click', openAddProcesoModal);
    }

    // Botones del modal agregar proceso
    const closeAddProcesoBtn = document.getElementById('closeAddProcesoModal');
    if (closeAddProcesoBtn) {
      closeAddProcesoBtn.addEventListener('click', closeAddProcesoModal);
    }

    const btnCancelAddProceso = document.getElementById('btnCancelAddProceso');
    if (btnCancelAddProceso) {
      btnCancelAddProceso.addEventListener('click', closeAddProcesoModal);
    }

    const btnConfirmAddProceso = document.getElementById('btnConfirmAddProceso');
    if (btnConfirmAddProceso) {
      btnConfirmAddProceso.addEventListener('click', handleAgregarProceso);
    }

    // Cerrar modal al hacer clic en el overlay
    const addProcesoOverlay = document.getElementById('addProcesoOverlay');
    if (addProcesoOverlay) {
      addProcesoOverlay.addEventListener('click', closeAddProcesoModal);
    }

    // Cerrar con ESC
    document.addEventListener('keydown', (e) => {
      const modal = document.getElementById('orderTrackingModal');
      if (e.key === 'Escape' && modal && modal.style.display !== 'none') {
        closeTrackingModal();
      }
    });
  }

  // Cerrar modal
  function closeTrackingModal() {
    const modal = document.getElementById('orderTrackingModal');
    if (modal) {
      modal.classList.remove('show');
      modal.style.display = 'none';
      // Resetear vistas (sin llamar a showPrendasView para evitar recursividad)
      console.log('[closeTrackingModal] Modal de seguimiento cerrado');
    }
  }

  // Función para abrir el modal de agregar proceso
  function openAddProcesoModal() {
    const modal = document.getElementById('addProcesoModal');
    if (modal) {
      modal.classList.add('show');
      modal.style.setProperty('display', 'flex', 'important');
      modal.style.setProperty('visibility', 'visible', 'important');
      modal.style.setProperty('opacity', '1', 'important');
      modal.style.setProperty('z-index', '9999', 'important');
      
      // Asegurar que los botones de cerrar funcionen
      setupAddProcesoModalListeners();
    }
  }

  // Configurar listeners del modal agregar proceso
  function setupAddProcesoModalListeners() {
    const closeBtn = document.getElementById('closeAddProcesoModal');
    if (closeBtn) {
      closeBtn.onclick = closeAddProcesoModal;
    }

    const cancelBtn = document.getElementById('btnCancelAddProceso');
    if (cancelBtn) {
      cancelBtn.onclick = closeAddProcesoModal;
    }

    const overlay = document.getElementById('addProcesoOverlay');
    if (overlay) {
      overlay.onclick = closeAddProcesoModal;
    }
  }

  // Función para cerrar el modal de agregar proceso
  function closeAddProcesoModal() {
    const modal = document.getElementById('addProcesoModal');
    if (modal) {
      modal.classList.remove('show');
      modal.style.display = 'none';
    }
  }

  // Configurar botón volver
  function setupBackButton() {
    const backBtn = document.getElementById('backToPrendasBtn');
    if (backBtn) {
      backBtn.onclick = showPrendasView;
      console.log('[setupBackButton] Botón volver configurado');
    } else {
      console.warn('[setupBackButton] Botón volver no encontrado');
    }
  }

  // Abrir selector de prendas (overlay)
  window.openOrderTracking = async function(orderId) {
    try {
      console.log('[openOrderTracking] Abriendo selector de prendas para orden:', orderId);
      
      // Cargar datos básicos del pedido
      await loadOrderBasicData(orderId);
      
      // Cargar prendas con seguimiento
      await loadPrendasWithTracking(orderId);
      
      // Mostrar overlay de prendas
      showPrendasSelector();
      
    } catch (error) {
      console.error('[openOrderTracking] Error:', error);
      showError('Error al cargar datos de seguimiento');
    }
  };

  // Cargar datos básicos del pedido
  async function loadOrderBasicData(orderId) {
    try {
      const response = await fetch(`/registros/${orderId}/recibos-datos`);
      if (!response.ok) throw new Error('Error al cargar datos del pedido');
      
      const data = await response.json();
      currentOrderData = data;
      
      // Actualizar información del pedido en el modal
      updateOrderInfo(data);
      
    } catch (error) {
      console.error('[loadOrderBasicData] Error:', error);
      throw error;
    }
  }

  // Actualizar información del pedido en el modal y selector
  function updateOrderInfo(orderData) {
    // Actualizar modal principal
    document.getElementById('trackingOrderNumber').textContent = orderData.numero_pedido || '-';
    document.getElementById('trackingOrderClient').textContent = orderData.cliente || '-';
    document.getElementById('trackingOrderStatus').textContent = orderData.estado || '-';
    document.getElementById('trackingOrderDate').textContent = formatDate(orderData.fecha_de_creacion_de_orden) || '-';
    document.getElementById('trackingEstimatedDate').textContent = formatDate(orderData.fecha_estimada_entrega) || '-';
    document.getElementById('trackingTotalDays').textContent = orderData.total_dias || '0';

    // Actualizar selector de prendas
    document.getElementById('selectorOrderNumber').textContent = orderData.numero_pedido || '-';
    document.getElementById('selectorOrderClient').textContent = orderData.cliente || '-';
    document.getElementById('selectorOrderStatus').textContent = orderData.estado || '-';
  }

  // Cargar prendas con seguimiento
  async function loadPrendasWithTracking(orderId) {
    try {
      console.log('[loadPrendasWithTracking] Cargando prendas para orden:', orderId);
      
      const response = await fetch(`/registros/${orderId}/seguimiento-prenda`);
      if (!response.ok) throw new Error('Error al cargar seguimiento de prendas');
      
      const data = await response.json();
      console.log('[loadPrendasWithTracking] Datos recibidos:', data);
      
      // Renderizar prendas
      renderPrendas(data.prendas || []);
      
    } catch (error) {
      console.error('[loadPrendasWithTracking] Error:', error);
      throw error;
    }
  }

  // Renderizar prendas en el overlay
  function renderPrendas(prendas) {
    const container = document.getElementById('trackingPrendasSelectorContainer');
    if (!container) return;
    
    console.log('[renderPrendas] Renderizando prendas en overlay:', prendas.length);
    
    container.innerHTML = '';
    
    if (prendas.length === 0) {
      container.innerHTML = `
        <div class="tracking-no-prendas">
          <p>No hay prendas registradas para este pedido</p>
        </div>
      `;
      return;
    }
    
    prendas.forEach((prenda, index) => {
      const prendaCard = createPrendaCard(prenda, index);
      container.appendChild(prendaCard);
    });
  }

  // Mostrar selector de prendas (overlay)
  function showPrendasSelector() {
    const overlay = document.getElementById('trackingPrendasSelectorOverlay');
    console.log('[showPrendasSelector] Overlay encontrado:', !!overlay);
    if (overlay) {
      overlay.classList.add('show');
      console.log('[showPrendasSelector] Overlay mostrado');
    } else {
      console.error('[showPrendasSelector] No se encontró el overlay');
    }
  }

  // Cerrar selector de prendas
  window.cerrarSelectorPrendas = function() {
    const overlay = document.getElementById('trackingPrendasSelectorOverlay');
    if (overlay) {
      overlay.classList.remove('show');
    }
  };

  // Crear tarjeta de prenda
  function createPrendaCard(prenda, index) {
    const card = document.createElement('div');
    card.className = 'tracking-prenda-card';
    
    // Añadir event listener con debug
    card.addEventListener('click', function(e) {
      console.log('[createPrendaCard] Click en tarjeta de prenda:', prenda);
      e.preventDefault();
      e.stopPropagation();
      showPrendaTracking(prenda);
    });
    
    const seguimientosHtml = renderSeguimientosBadges(prenda.seguimientos || {});
    const areasHtml = renderAreasBadges(prenda.seguimientos_por_area || {});
    
    // Construir HTML de procesos
    let procesosHtml = '';
    if (prenda.procesos && prenda.procesos.length > 0) {
      procesosHtml = '<div class="tracking-prenda-procesos">';
      prenda.procesos.forEach(proceso => {
        // Acceder correctamente a los datos del tipo_proceso
        const tipoProceso = proceso.tipo_proceso;
        const procesoNombre = tipoProceso?.nombre || 'Proceso';
        const procesoIcono = tipoProceso?.icono || 'description';
        const procesoColor = tipoProceso?.color || '#6b7280';
        
        console.log('[createPrendaCard] Proceso:', proceso);
        console.log('[createPrendaCard] TipoProceso:', tipoProceso);
        
        procesosHtml += `
          <div class="tracking-prenda-proceso-item">
            <div class="tracking-proceso-icon" style="color: ${procesoColor}">
              ${getIconSvg(procesoIcono)}
            </div>
            <div class="tracking-proceso-info">
              <div class="tracking-proceso-nombre">${procesoNombre}</div>
              <div class="tracking-proceso-estado">${proceso.estado || 'PENDIENTE'}</div>
            </div>
          </div>
        `;
      });
      procesosHtml += '</div>';
    }

    // Badge de bodega si aplica
    let bodegaBadge = '';
    if (prenda.de_bodega) {
      bodegaBadge = '<div class="tracking-bodega-badge">Se saca de bodega</div>';
    }

    card.innerHTML = `
      <div class="tracking-prenda-header">
        <div class="tracking-prenda-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"></path>
          </svg>
        </div>
        <div class="tracking-prenda-name">${prenda.nombre_prenda || `Prenda ${index + 1}`}</div>
      </div>
      <div class="tracking-prenda-details">
        <div class="tracking-prenda-detail">
          <span class="tracking-prenda-detail-label">Cantidad:</span>
          <span class="tracking-prenda-detail-value">${prenda.cantidad || 0}</span>
        </div>
        <div class="tracking-prenda-detail">
          <span class="tracking-prenda-detail-label">Procesos:</span>
          <span class="tracking-prenda-detail-value">${prenda.total_procesos || 0}</span>
        </div>
      </div>
      ${procesosHtml}
      ${bodegaBadge}
      ${seguimientosHtml}
      ${areasHtml}
    `;
    
    return card;
  }

  // Renderizar badges de seguimientos por tipo de recibo
  function renderSeguimientosBadges(seguimientos) {
    if (Object.keys(seguimientos).length === 0) {
      return '';
    }
    
    let badgesHtml = '<div class="tracking-prenda-seguimientos">';
    
    Object.entries(seguimientos).forEach(([tipo, data]) => {
      const statusClass = data.tiene_disponibles ? 'pendiente' : 'completado';
      
      badgesHtml += `
        <span class="tracking-seguimiento-badge ${statusClass}">
          ${tipo}: ${data.consecutivo_actual}/${data.consecutivo_inicial}
        </span>
      `;
    });
    
    badgesHtml += '</div>';
    return badgesHtml;
  }

  // Renderizar badges de áreas/procesos
  function renderAreasBadges(areas) {
    if (Object.keys(areas).length === 0) {
      return '';
    }
    
    let badgesHtml = '<div class="tracking-prenda-areas">';
    
    Object.entries(areas).forEach(([area, data]) => {
      const statusClass = data.esta_activo ? 'pendiente' : 'completado';
      
      badgesHtml += `
        <span class="tracking-seguimiento-badge ${statusClass}">
          ${area}: ${data.estado}
        </span>
      `;
    });
    
    badgesHtml += '</div>';
    return badgesHtml;
  }

  // Mostrar seguimiento de una prenda específica
  window.showPrendaTracking = async function(prenda) {
    try {
      console.log('[showPrendaTracking] INICIO - Mostrando seguimiento para prenda:', prenda);
      
      currentPrendaData = prenda;
      
      // Cerrar overlay de prendas
      console.log('[showPrendaTracking] Cerrando overlay selector...');
      cerrarSelectorPrendas();
      
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
        modal.style.setProperty('z-index', '9998', 'important');
        modal.style.setProperty('position', 'fixed', 'important');
        modal.style.setProperty('top', '0', 'important');
        modal.style.setProperty('left', '0', 'important');
        modal.style.setProperty('width', '100vw', 'important');
        modal.style.setProperty('height', '100vh', 'important');
        modal.style.setProperty('background', 'rgba(0, 0, 0, 0.5)', 'important');
        modal.style.setProperty('align-items', 'center', 'important');
        modal.style.setProperty('justify-content', 'center', 'important');
        
        // Asegurar que el botón volver funcione
        setupBackButton();
        
        console.log('[showPrendaTracking] Modal mostrado con estilos forzados');
        
        // Debug visual - verificar estado del modal
        setTimeout(() => {
          const modalElement = document.getElementById('orderTrackingModal');
          const computedStyle = window.getComputedStyle(modalElement);
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
      document.getElementById('trackingPrendasContainer').parentElement.style.display = 'none';
      document.getElementById('trackingTimelineSection').style.display = 'block';
      
      // Actualizar nombre de la prenda y número de recibo
      console.log('[showPrendaTracking] Actualizar nombre de la prenda y número de recibo');
      
      const nombreElement = document.getElementById('trackingPrendaName');
      if (nombreElement) {
        nombreElement.textContent = prenda.nombre_prenda || 'Prenda';
      }
      
      // Determinar número de recibo desde la tabla consecutivos_recibos_pedidos
      let numeroRecibo = 'Sin recibo';
      if (prenda.consecutivos && prenda.consecutivos.length > 0) {
        // Buscar el primer recibo activo
        const reciboActivo = prenda.consecutivos.find(r => r.activo === 1);
        if (reciboActivo) {
          numeroRecibo = `${reciboActivo.tipo_recibo} #${reciboActivo.consecutivo_actual}`;
        } else if (prenda.consecutivos[0]) {
          // Si no hay activo, tomar el primero
          const primerRecibo = prenda.consecutivos[0];
          numeroRecibo = `${primerRecibo.tipo_recibo} #${primerRecibo.consecutivo_actual}`;
        }
      }
      
      // Actualizar tanto el subtítulo del header como el del timeline
      const reciboHeaderElement = document.getElementById('trackingPrendaReciboHeader');
      if (reciboHeaderElement) {
        reciboHeaderElement.textContent = numeroRecibo;
      }
      
      const reciboElement = document.getElementById('trackingPrendaRecibo');
      if (reciboElement) {
        reciboElement.textContent = numeroRecibo;
      }
      
      // Renderizar timeline de seguimiento
      console.log('[showPrendaTracking] Renderizando timeline...');
      renderPrendaTrackingTimeline(prenda);
      
      console.log('[showPrendaTracking] FINALIZADO - Seguimiento mostrado exitosamente');
      
    } catch (error) {
      console.error('[showPrendaTracking] Error:', error);
      showError('Error al cargar seguimiento de la prenda');
    }
  };

  // Renderizar timeline de seguimiento de prenda
  function renderPrendaTrackingTimeline(prenda) {
    const container = document.getElementById('trackingTimelineContainer');
    if (!container) return;

    console.log('[renderPrendaTrackingTimeline] Renderizando timeline para prenda:', prenda);

    // Botón de volver (eliminado - ya está en el header)
    container.innerHTML = '';

    // Renderizar seguimientos por área (procesos de producción)
    renderSeguimientosPorArea(prenda, container);

    // Renderizar seguimientos por tipo de recibo (ELIMINADO - no mostrar recibos en modal de seguimiento)
    // renderSeguimientosPorTipo(prenda, container);

    // Si no hay seguimientos por área, mostrar mensaje
    if (!prenda.seguimientos_por_area || Object.keys(prenda.seguimientos_por_area).length === 0) {
      renderNoSeguimiento(container);
    }
  }

  // Renderizar seguimientos por área (procesos)
  function renderSeguimientosPorArea(prenda, container) {
    const seguimientosPorArea = prenda.seguimientos_por_area || {};
    if (Object.keys(seguimientosPorArea).length > 0) {
      const seguimientosTitle = document.createElement('h4');
      seguimientosTitle.textContent = 'Seguimiento por Áreas/Procesos';
      seguimientosTitle.style.marginTop = '24px';
      container.appendChild(seguimientosTitle);
      
      Object.entries(seguimientosPorArea).forEach(([area, data]) => {
        const areaCard = createAreaCard(area, data);
        container.appendChild(areaCard);
      });
    }
  }

  // Renderizar seguimientos por tipo de recibo
  function renderSeguimientosPorTipo(prenda, container) {
    const seguimientosPorTipo = prenda.seguimientos || {};
    if (Object.keys(seguimientosPorTipo).length > 0) {
      const recibosTitle = document.createElement('h4');
      recibosTitle.textContent = 'Seguimiento por Tipo de Recibo';
      recibosTitle.style.marginTop = '24px';
      container.appendChild(recibosTitle);
      
      Object.entries(seguimientosPorTipo).forEach(([tipo, data]) => {
        const seguimientoCard = createSeguimientoCard(tipo, data);
        container.appendChild(seguimientoCard);
      });
    }
  }

  // Mostrar mensaje si no hay seguimientos
  function renderNoSeguimiento(container) {
    const noSeguimiento = document.createElement('div');
    noSeguimiento.className = 'tracking-no-seguimiento';
    noSeguimiento.innerHTML = '<p>No hay seguimientos registrados para esta prenda</p>';
    container.appendChild(noSeguimiento);
  }

  // Crear tarjeta de área/proceso
  function createAreaCard(area, data) {
    const card = document.createElement('div');
    card.className = `tracking-area-card ${data.esta_activo ? 'pending' : 'completed'}`;
    
    const iconSvg = getIconSvg(data.icono || 'description');
    
    card.innerHTML = `
      <div class="tracking-area-name">
        ${iconSvg}
        ${area}
      </div>
      <div class="tracking-area-details">
        <div class="tracking-detail-row">
          <span class="tracking-detail-label">Estado</span>
          <span class="tracking-detail-value">
            <span class="tracking-days-badge ${data.esta_activo ? '' : 'tracking-days-badge-zero'}">
              ${data.estado}
            </span>
          </span>
        </div>
        <div class="tracking-detail-row">
          <span class="tracking-detail-label">Encargado</span>
          <span class="tracking-detail-value">${data.encargado || 'No asignado'}</span>
        </div>
        <div class="tracking-detail-row">
          <span class="tracking-detail-label">Fecha Inicio</span>
          <span class="tracking-detail-value">${formatDate(data.fecha_inicio) || 'No iniciado'}</span>
        </div>
        <div class="tracking-detail-row">
          <span class="tracking-detail-label">Fecha Fin</span>
          <span class="tracking-detail-value">${formatDate(data.fecha_fin) || 'En progreso'}</span>
        </div>
        ${data.duracion_dias ? `
        <div class="tracking-detail-row">
          <span class="tracking-detail-label">Duración</span>
          <span class="tracking-detail-value">${data.duracion_dias} días</span>
        </div>
        ` : ''}
        ${data.observaciones ? `
        <div class="tracking-detail-row">
          <span class="tracking-detail-label">Observaciones</span>
          <span class="tracking-detail-value">${data.observaciones}</span>
        </div>
        ` : ''}
      </div>
    `;
    
    return card;
  }

  // Crear tarjeta de seguimiento
  function createSeguimientoCard(tipo, data) {
    const card = document.createElement('div');
    card.className = 'tracking-area-card';
    
    const statusClass = data.tiene_disponibles ? 'pending' : 'completed';
    const statusText = data.tiene_disponibles ? 'En Progreso' : 'Completado';
    
    card.innerHTML = `
      <div class="tracking-area-name">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
        </svg>
        ${tipo}
      </div>
      <div class="tracking-area-details">
        <div class="tracking-detail-row">
          <span class="tracking-detail-label">Consecutivo Actual</span>
          <span class="tracking-detail-value">${data.consecutivo_actual}</span>
        </div>
        <div class="tracking-detail-row">
          <span class="tracking-detail-label">Consecutivo Inicial</span>
          <span class="tracking-detail-value">${data.consecutivo_inicial}</span>
        </div>
        <div class="tracking-detail-row">
          <span class="tracking-detail-label">Siguiente Consecutivo</span>
          <span class="tracking-detail-value">${data.siguiente_consecutivo}</span>
        </div>
        <div class="tracking-detail-row">
          <span class="tracking-detail-label">Estado</span>
          <span class="tracking-detail-value">
            <span class="tracking-days-badge ${data.tiene_disponibles ? '' : 'tracking-days-badge-zero'}">
              ${statusText}
            </span>
          </span>
        </div>
        ${data.notas ? `
        <div class="tracking-detail-row">
          <span class="tracking-detail-label">Notas</span>
          <span class="tracking-detail-value">${data.notas}</span>
        </div>
        ` : ''}
      </div>
    `;
    
    return card;
  }

  // Obtener SVG del icono
  function getIconSvg(iconName) {
    const icons = {
      'description': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>',
      'inventory_2': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"></path></svg>',
      'content_cut': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="6" r="3"></circle><circle cx="18" cy="18" r="3"></circle><path d="M20.41 3.59l-7.06 7.06a2 2 0 01-2.83 0l-2.12-2.12a2 2 0 010-2.83l7.06-7.06a2 2 0 012.83 0l2.12 2.12a2 2 0 010 2.83z"></path></svg>',
      'brush': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.71 4.63l-1.34-1.34a1 1 0 00-1.41 0L9 12.59 10.41 14l8.3-8.3a1 1 0 000-1.41z"></path><path d="M18 13l3 3"></path><path d="M3 21l9-9"></path></svg>',
      'print': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"></path><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>',
      'dry_cleaning': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><path d="M12 8v8"></path><path d="M8 12h8"></path></svg>',
      'checkroom': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V7l-10-5z"></path><path d="M12 22V12"></path></svg>',
      'construction': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 21l6-6m0 0V9m0 6h6m-6-6l6-6m6 0l6 6m0 0v6m0-6h-6m6 6l-6 6"></path></svg>',
      'local_laundry_service': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7v10c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V7l-10-5z"></path><circle cx="12" cy="13" r="4"></circle></svg>',
      'handyman': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v7m0 0l3-3m-3 3l-3-3"></path><path d="M12 22v-7m0 0l3 3m-3-3l-3 3"></path><path d="M2 12h7m0 0l-3-3m3 3l-3 3"></path><path d="M22 12h-7m0 0l3-3m-3 3l3 3"></path></svg>',
      'verified': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
      'local_shipping': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path><polyline points="14,2 14,8 20,8"></polyline><line x1="16" y1="13" x2="16" y2="21"></line><line x1="8" y1="13" x2="8" y2="21"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>',
      'directions_car': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 17l2-2h8l2 2M5 7l2 2h8l2-2"></path><path d="M7 12h10"></path></svg>',
      'highlight': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11H3m6 0v6m0-6l-6 6m12 0h6m-6 0v6m0-6l6 6"></path></svg>',
      'search': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg>'
    };
    
    return icons[iconName] || icons.description;
  }

  // Mostrar vista de prendas (cerrar modal de seguimiento y volver a prendas)
  function showPrendasView() {
    console.log('[showPrendasView] Cerrando modal de seguimiento y volviendo a prendas...');
    
    // Cerrar el modal de seguimiento
    closeTrackingModal();
    
    // Mostrar el overlay de selección de prendas
    showPrendasSelector();
    
    console.log('[showPrendasView] Modal de seguimiento cerrado y selector de prendas mostrado');
  }

  // Formatear fecha
  function formatDate(dateString) {
    if (!dateString) return null;
    
    try {
      const date = new Date(dateString);
      return date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
      });
    } catch (error) {
      return dateString;
    }
  }

  // Mostrar error
  function showError(message) {
    console.error('[showError] ' + message);
    // Aquí podrías mostrar una notificación de error al usuario
  }

  // Manejar agregar proceso
  async function handleAgregarProceso() {
    try {
      const area = document.getElementById('procesoArea').value;
      const estado = document.getElementById('procesoEstado').value;
      const encargado = document.getElementById('procesoEncargado').value;
      const observaciones = document.getElementById('procesoObservaciones').value;

      if (!area) {
        showError('Por favor selecciona un área/proceso');
        return;
      }

      if (!currentPrendaData || !currentOrderData) {
        showError('No hay datos de la prenda o pedido');
        return;
      }

      console.log('[handleAgregarProceso] Agregando proceso:', {
        area,
        estado,
        encargado,
        observaciones,
        prenda_id: currentPrendaData.id,
        currentOrderData: currentOrderData
      });

      // Verificar que los datos necesarios existan
      console.log('[handleAgregarProceso] Verificando estructura de datos:', {
        currentOrderData: currentOrderData,
        'currentOrderData.numero_pedido': currentOrderData?.numero_pedido,
        'currentOrderData.pedido': currentOrderData?.pedido
      });
      
      if (!currentOrderData) {
        throw new Error('No hay datos del pedido');
      }
      
      if (!currentOrderData.numero_pedido) {
        throw new Error('No hay número de pedido');
      }

      // Enviar datos al backend
      const response = await fetch('/seguimiento-proceso/guardar', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        },
        body: JSON.stringify({
          pedido_produccion_id: currentOrderData.numero_pedido,
          prenda_id: currentPrendaData.id,
          area: area,
          estado: estado,
          encargado: encargado,
          observaciones: observaciones
        })
      });

      if (!response.ok) {
        throw new Error('Error al agregar proceso');
      }

      const result = await response.json();
      console.log('[handleAgregarProceso] Proceso agregado:', result);

      // Limpiar formulario
      limpiarFormularioProceso();

      // Recargar seguimientos de la prenda
      await loadPrendasWithTracking(currentOrderData.pedido.id);
      
      // Actualizar vista actual
      if (currentPrendaData) {
        const prendaActualizada = result.prenda || currentPrendaData;
        renderPrendaTrackingTimeline(prendaActualizada);
      }

      // Mostrar mensaje de éxito
      showSuccess('Proceso agregado correctamente');

    } catch (error) {
      console.error('[handleAgregarProceso] Error:', error);
      showError('Error al agregar proceso: ' + error.message);
    }
  }

  // Limpiar formulario de proceso
  function limpiarFormularioProceso() {
    document.getElementById('procesoArea').value = '';
    document.getElementById('procesoEstado').value = 'Pendiente';
    document.getElementById('procesoEncargado').value = '';
    document.getElementById('procesoObservaciones').value = '';
  }

  // Mostrar mensaje de éxito
  function showSuccess(message) {
    // Crear elemento temporal para mostrar éxito
    const successDiv = document.createElement('div');
    successDiv.className = 'tracking-success-message';
    successDiv.textContent = message;
    successDiv.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: white;
      padding: 12px 20px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
      z-index: 100000;
      font-weight: 600;
      animation: slideInRight 0.3s ease-out;
    `;

    document.body.appendChild(successDiv);

    // Remover después de 3 segundos
    setTimeout(() => {
      if (successDiv.parentNode) {
        successDiv.parentNode.removeChild(successDiv);
      }
    }, 3000);
  }

  // Inicializar cuando el DOM esté listo
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTrackingModalListeners);
  } else {
    initTrackingModalListeners();
  }

})();
