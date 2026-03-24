'use strict';

/**
 * TRACKING MODAL HANDLER - Main Orchestrator
 * 
 * Architecture: Domain-Driven Design (DDD)
 * 
 * Responsabilidad: Coordinación de eventos y flujo de datos
 * 
 * Capas utilizadas:
 * - Domain: OrderState, DateFormatter
 * - Infrastructure: QueryUtils
 * - Application: OrderApiService (próxima fase)
 * - Interface: DOMRenderer (próxima fase)
 */

// ============================================================
// IMPORTS: Domain Layer
// ============================================================
import { orderState, DateFormatter } from './domain/index.js';

// ============================================================
// IMPORTS: Infrastructure Layer
// ============================================================
import { QueryUtils, SvgIcons, dateUtils, ModalUtils } from './infrastructure/index.js';

// ============================================================
// IMPORTS: Application Layer
// ============================================================
import { OrderApiService } from './application/index.js';

// Festivos now handled by backend CalculadorDiasService
// No need to preload client-side

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

    // Configurar listeners del modal agregar proceso
    setupAddProcesoModalListeners();

    // Configurar listeners del modal de confirmación
    setupConfirmDeleteModalListeners();

    // Configurar botón volver
    setupBackButton();

    setupDaysSelector();

    // Festivos now handled by backend CalculadorDiasService
  }

  function setupDaysSelector() {
    const selector = document.getElementById('trackingDaysSelector');
    const trigger = document.getElementById('trackingDaysSelectorTrigger');
    const menu = document.getElementById('trackingDaysSelectorMenu');
    const valueEl = document.getElementById('trackingDaysSelectorValue');
    if (!selector || !trigger || !menu || !valueEl) return;

    if (!menu.dataset.bound) {
      // Agregar opción "Sin seleccionar" al inicio
      let menuItems = '<button type="button" class="tracking-days-selector-item" data-value="0">Sin seleccionar</button>';
      menuItems += Array.from({ length: 35 }, (_, i) => {
        const n = i + 1;
        const label = `${n} ${n === 1 ? 'día' : 'días'}`;
        return `<button type="button" class="tracking-days-selector-item" data-value="${n}">${label}</button>`;
      }).join('');
      menu.innerHTML = menuItems;
      menu.dataset.bound = '1';
    }

    const closeMenu = () => {
      menu.style.display = 'none';
      selector.classList.remove('open');
    };

    const openMenu = () => {
      menu.style.display = 'block';
      selector.classList.add('open');
    };

    const toggleMenu = () => {
      const isOpen = menu.style.display !== 'none' && menu.style.display !== '';
      if (isOpen) closeMenu();
      else openMenu();
    };

    if (!trigger.dataset.bound) {
      trigger.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        toggleMenu();
      });
      trigger.dataset.bound = '1';
    }

    if (!menu.dataset.clickBound) {
      menu.addEventListener('click', (e) => {
        const btn = e.target.closest('.tracking-days-selector-item');
        if (!btn) return;
        const n = parseInt(btn.dataset.value, 10);
        if (!Number.isFinite(n)) return;

        // Manejar "Sin seleccionar" (valor 0)
        if (n === 0) {
          valueEl.textContent = 'Sin seleccionar';
          orderState.setSelectedDays(null); // DDD: Usar orderState en lugar de variable global
        } else {
          valueEl.textContent = `${n} ${n === 1 ? 'día' : 'días'}`;
          orderState.setSelectedDays(n); // DDD: Usar orderState en lugar de variable global
        }
        
        // Guardar los datos al cambiar la selección
        saveDiaEntregaSelection();
        closeMenu();
      });
      menu.dataset.clickBound = '1';
    }

    if (!document.body.dataset.trackingDaysSelectorGlobalBound) {
      document.addEventListener('click', (e) => {
        if (!selector.contains(e.target)) {
          closeMenu();
        }
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          closeMenu();
        }
      });
      document.body.dataset.trackingDaysSelectorGlobalBound = '1';
    }
  }

  // Timer para actualizar contadores dinámicos
  let contadorTimer = null;

  // Actualizar contadores de días dinámicos (procesos sin fecha fin)
  function actualizarContadoresDinamicos() {
    try {
      // Buscar todas las tarjetas de áreas que tengan contadores dinámicos
      const areaCards = document.querySelectorAll('.tracking-area-card');
      
      areaCards.forEach(card => {
        const areaElement = card.querySelector('.tracking-area-name');
        if (!areaElement) return;
        
        const area = areaElement.textContent.trim();
        const totalDiasElement = card.querySelector('.tracking-total-dias');
        const duracionAreaElement = card.querySelector('.tracking-duracion-area');
        
        if (!totalDiasElement || !duracionAreaElement) return;
        
        // Obtener datos del proceso (desde data attributes o recalcular)
        const processData = orderState.getCurrentPrenda()?.seguimientos_por_area?.[area];
        if (!processData) return;
        
        // Recalcular días dinámicamente
        const ini = toDateObject(processData.fecha_inicio);
        if (!ini) return;
        
        // Si no hay fecha fin/completado, contar hasta hoy
        if (!processData.fecha_fin && !processData.fecha_completado) {
          const diasHabiles = calcularDiasHabilesSync(ini, new Date());
          const diasText = diasHabiles === 0 ? '0 días' : `${diasHabiles} día${diasHabiles !== 1 ? 's' : ''}`;
          
          // Actualizar visualización
          if (totalDiasElement.textContent.includes('día')) {
            totalDiasElement.textContent = diasText;
          }
          if (duracionAreaElement.textContent.includes('día')) {
            duracionAreaElement.textContent = diasText;
          }
        }
      });
      
      console.log('[actualizarContadoresDinamicos] Contadores actualizados');
    } catch (error) {
      console.error('[actualizarContadoresDinamicos] Error:', error);
    }
  }

  // Iniciar timer para actualización automática de contadores
  function iniciarTimerContadores() {
    // Detener timer existente
    if (contadorTimer) {
      clearInterval(contadorTimer);
    }
    
    // Actualizar inmediatamente
    actualizarContadoresDinamicos();
    
    // Configurar timer para actualizar cada día a medianoche
    const ahora = new Date();
    const manana = new Date(ahora);
    manana.setDate(manana.getDate() + 1);
    manana.setHours(0, 0, 0, 0);
    
    const msHastaManana = manana.getTime() - ahora.getTime();
    
    // Primer actualización a medianoche
    setTimeout(() => {
      actualizarContadoresDinamicos();
      
      // Luego actualizar cada 24 horas
      contadorTimer = setInterval(actualizarContadoresDinamicos, 24 * 60 * 60 * 1000);
    }, msHastaManana);
    
    console.log('[iniciarTimerContadores] Timer configurado para actualizar diariamente');
  }

  // Detener timer de contadores
  function detenerTimerContadores() {
    if (contadorTimer) {
      clearInterval(contadorTimer);
      contadorTimer = null;
      console.log('[detenerTimerContadores] Timer detenido');
    }
  }

  /**
   * Guardar selección de día de entrega desde el modal de seguimiento
   * Refactorizado FASE 2: Usa OrderApiService para calcular fecha
   */
  async function saveDiaEntregaSelection() {
    try {
      // Obtener datos del estado (sin globales)
      const diasSeleccionados = orderState.getSelectedDays();
      const order = orderState.getOrder();

      // Validar precondiciones
      if (!order) {
        console.warn('[saveDiaEntregaSelection] No hay orden cargada');
        return;
      }

      if (diasSeleccionados === null || diasSeleccionados === undefined) {
        console.warn('[saveDiaEntregaSelection] No hay días seleccionados');
        return;
      }

      console.log('[saveDiaEntregaSelection] Calculando fecha de entrega:', {
        pedido_id: order.id,
        dias_estimados: diasSeleccionados
      });

      // Calcular fecha via OrderApiService (con validación y error handling centralizado)
      const result = await OrderApiService.calculateDeliveryDate(order.id, diasSeleccionados);

      // Actualizar la UI con la fecha estimada
      if (result.fecha_estimada) {
        const fechaFormateada = DateFormatter.format(result.fecha_estimada);
        QueryUtils.setText('trackingEstimatedDate', fechaFormateada);
        QueryUtils.setText('selectorOrderEstimatedDate', fechaFormateada);
      }

      // Mostrar notificación de éxito
      if (typeof showSuccess === 'function') {
        const diasText = `${diasSeleccionados} día${diasSeleccionados !== 1 ? 's' : ''}`;
        showSuccess(`Fecha de entrega calculada: ${diasText}`);
      }

    } catch (error) {
      console.error('[saveDiaEntregaSelection] Error:', error);
      if (typeof showError === 'function') {
        showError('Error al guardar el día de entrega');
      }
    }
  }

  // Función para abrir el modal de agregar proceso
  const closeAddProcesoBtn = document.getElementById('closeAddProcesoModal');
  if (closeAddProcesoBtn) {
    closeAddProcesoBtn.addEventListener('click', closeAddProcesoModal);
  }

  const btnCancelAddProceso = document.getElementById('btnCancelAddProceso');
  if (btnCancelAddProceso) {
    btnCancelAddProceso.addEventListener('click', closeAddProcesoModal);
  }

  // Cerrar modal
  function closeTrackingModal() {
    ModalUtils.close('orderTrackingModal', () => {
      // DDD: Limpiar estado cuando se cierra el modal
      orderState.clear();
    });
  }

  // Función para abrir el modal de agregar proceso
  function openAddProcesoModal() {
    // Si no estamos editando, abrir limpio para agregar una nueva área
    if (!orderState.isEditing()) {
      if (typeof resetFormButton === 'function') {
        resetFormButton();
      }
      if (typeof limpiarFormularioProceso === 'function') {
        limpiarFormularioProceso();
      }
    }

    // Asegurar que los botones de cerrar funcionen
    ModalUtils.openWithForce('addProcesoModal', setupAddProcesoModalListeners);
  }

  // Configurar listeners del modal agregar proceso
  function setupAddProcesoModalListeners() {
    // Abrir modal
    const openBtn = document.getElementById('btnOpenAddProcesoModal');
    if (openBtn) {
      openBtn.onclick = openAddProcesoModal;
      console.log('[setupAddProcesoModalListeners] Botón ABRIR modal configurado');
    } else {
      console.warn('[setupAddProcesoModalListeners] Botón ABRIR modal no encontrado');
    }

    // Cerrar modal
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

    // Configurar selector dinámico para encargado
    setupEncargadoDynamicSelector();
  }

  // Función para configurar el selector dinámico de encargado
  function setupEncargadoDynamicSelector() {
    const procesoArea = document.getElementById('procesoArea');
    if (!procesoArea) return;

    procesoArea.addEventListener('change', async function(e) {
      const area = e.target.value.toLowerCase().trim();
      
      // Buscar el contenedor de encargado de forma más robusta
      const formGroups = document.querySelectorAll('.add-proceso-form-group');
      let procesoEncargadoContainer = null;
      
      formGroups.forEach(group => {
        const label = group.querySelector('label');
        if (label && label.textContent.includes('Encargado')) {
          procesoEncargadoContainer = group;
        }
      });
      
      if (!procesoEncargadoContainer) {
        console.warn('[setupEncargadoDynamicSelector] No se encontró el contenedor de encargado');
        return;
      }
      
      console.log('[setupEncargadoDynamicSelector] Área seleccionada:', area);

      // Áreas que requieren selector dinámico
      if (area === 'corte' || area === 'costura') {
        console.log('[setupEncargadoDynamicSelector] Convertir a selector para:', area);
        convertEncargadoToSelect(area, procesoEncargadoContainer);
      } else {
        console.log('[setupEncargadoDynamicSelector] Convertir a input para:', area);
        convertEncargadoToInput(procesoEncargadoContainer);
      }
    });
  }

  // Convertir campo de encargado a SELECT
  async function convertEncargadoToSelect(area, container) {
    // Remover cualquier input o select anterior
    const existingInput = document.getElementById('procesoEncargado');
    const existingSelect = document.getElementById('procesoEncargadoSelect');
    
    if (existingInput) existingInput.remove();
    if (existingSelect) existingSelect.remove();

    // Crear nuevo select
    const select = document.createElement('select');
    select.id = 'procesoEncargadoSelect';
    select.className = 'add-proceso-select';
    select.innerHTML = '<option value="">Seleccionar encargado...</option>';
    container.appendChild(select);

    try {
      console.log('[convertEncargadoToSelect] Cargando encargados para:', area);
      const encargados = await OrderApiService.loadEncargados(area);
      
      encargados.forEach(encargado => {
        const option = document.createElement('option');
        option.value = encargado.id;
        option.textContent = encargado.nombre;
        select.appendChild(option);
      });
      console.log('[convertEncargadoToSelect] ✓ Encargados cargados:', encargados.length);
    } catch (error) {
      console.error('[convertEncargadoToSelect] Error:', error);
      const option = document.createElement('option');
      option.value = '';
      option.textContent = 'Error al cargar encargados';
      option.disabled = true;
      select.appendChild(option);
    }
  }

  // Convertir campo de encargado a INPUT
  function convertEncargadoToInput(container) {
    // Primero, remover cualquier input o select anterior
    const existingInput = document.getElementById('procesoEncargado');
    const existingSelect = document.getElementById('procesoEncargadoSelect');
    
    if (existingInput) {
      existingInput.remove();
    }
    if (existingSelect) {
      existingSelect.remove();
    }

    // Crear nuevo input
    const input = document.createElement('input');
    input.type = 'text';
    input.id = 'procesoEncargado';
    input.className = 'add-proceso-input';
    input.placeholder = 'Nombre del encargado';
    input.style.textTransform = 'uppercase';
    container.appendChild(input);
    
    console.log('[convertEncargadoToInput] Input de texto creado');
  }

  // Función para cerrar el modal de agregar proceso
  function closeAddProcesoModal() {
    ModalUtils.close('addProcesoModal');
  }

  // Configurar botón volver
  function setupBackButton() {
    const backBtn = document.getElementById('backToPrendasBtn');
    if (backBtn) {
      backBtn.onclick = showPrendasView;
      console.log('[setupBackButton] Botón volver configurado');
    }
    // No mostrar warning si el botón no existe - es una funcionalidad opcional
  }

  // Abrir selector de prendas (overlay)
  window.openOrderTracking = async function(orderId, mostrarSelector = true) {
    try {
      console.log('[openOrderTracking] Abriendo selector de prendas para orden:', orderId, 'mostrarSelector:', mostrarSelector);
      
      // Cargar datos básicos del pedido
      await loadOrderBasicData(orderId);
      
      // Cargar prendas con seguimiento
      await loadPrendasWithTracking(orderId);
      
      // Mostrar overlay de prendas solo si se solicita
      if (mostrarSelector) {
        showPrendasSelector();
      }
      
      console.log('[openOrderTracking] Datos cargados correctamente. orderState:', orderState.getSnapshot());
      
    } catch (error) {
      console.error('[openOrderTracking] Error:', error);
      showError('Error al cargar datos de seguimiento');
    }
  };

  // Compatibilidad con implementación vieja (tracking-modal-script.blade.php)
  // Algunas vistas (ej. supervisor-pedidos) llaman mostrarTrackingModal(pedidoData).
  window.mostrarTrackingModal = function(pedidoData) {
    try {
      const orderId = pedidoData?.id || pedidoData?.pedido_id || pedidoData?.pedido?.id || null;
      if (!orderId) {
        console.error('[mostrarTrackingModal] No se encontró orderId en pedidoData:', pedidoData);
        return;
      }

      // El flujo nuevo carga datos desde /registros/{id}/... y abre el selector de prendas.
      window.openOrderTracking(orderId, true);
    } catch (e) {
      console.error('[mostrarTrackingModal] Error:', e);
    }
  };

  // Cargar datos básicos del pedido
  async function loadOrderBasicData(orderId) {
    try {
      // Application Layer: OrderApiService maneja la API
      const data = await OrderApiService.loadOrderData(orderId);
      
      // Domain Layer: Guardar en estado centralizado
      orderState.setOrder(data);
      
      // Presentation: Actualizar UI
      updateOrderInfo(data);
      
    } catch (error) {
      console.error('[loadOrderBasicData] Error:', error);
      throw error;
    }
  }

  // Actualizar información del pedido en el modal y selector
  function updateOrderInfo(orderData) {
    const numeroPedido = orderData.numero_pedido || '-';
    const cliente = orderData.cliente || '-';
    const estadoDisplay = (orderData.estado || '-').replace(/_/g, ' ').toUpperCase();
    const fechaEstimada = DateFormatter.getOrderEstimatedDate(orderData);
    const fechaInicio = DateFormatter.getOrderStartDate(orderData);

    // Modal principal
    const setText = (id, text) => {
      const el = document.getElementById(id);
      if (el) el.textContent = text;
    };

    setText('trackingOrderNumber', numeroPedido);
    setText('trackingOrderClient', cliente);
    setText('trackingOrderStatus', estadoDisplay);
    setText('trackingEstimatedDate', formatDate(orderData.fecha_estimada_entrega) || '-');
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

  // Cargar prendas con seguimiento
  async function loadPrendasWithTracking(orderId) {
    try {
      // Application Layer: OrderApiService maneja la API
      const prendas = await OrderApiService.loadPrendasWithTracking(orderId);
      
      // Domain Layer: Guardar en estado centralizado
      orderState.setPrendas(prendas);
      
      // Presentation: Renderizar prendas
      renderPrendas(prendas);
      
    } catch (error) {
      console.error('[loadPrendasWithTracking] Error:', error);
      throw error;
    }
  }

  // Renderizar tabla única de prendas en el overlay
  function renderPrendas(prendas) {
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
    const tableHtml = createPrendasTable(prendas);
    container.innerHTML = tableHtml;
    
    // Actualizar fecha estimada de entrega del pedido
    updateEstimatedDeliveryDate();
  }

  // Actualizar fecha estimada de entrega del pedido
  function updateEstimatedDeliveryDate() {
    const fechaEstimadaElement = document.getElementById('selectorOrderEstimatedDate');
    const order = orderState.getOrder();
    
    if (!fechaEstimadaElement || !order) return;

    // DDD: Usar DateFormatter para formateo consistente (sin duplicación)
    const fechaEstimada = DateFormatter.getOrderEstimatedDate(order);

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

  // Crear tabla HTML con todas las prendas
  function createPrendasTable(prendas) {
    let tableHtml = `
      <div class="prendas-table-container">
        <table class="prendas-report-table">
          <thead>
            <tr>
              <th>Prenda</th>
              <th>Cantidad</th>
              <th>Procesos</th>
              <th>Área</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
    `;
    
    prendas.forEach((prenda, index) => {
      // Domain: Preparar datos de la prenda para renderizar
      const datos = orderState.preparePrendaTableData(prenda);

      // Badge de origen de prenda
      const badgeHtml = datos.esDeBodega 
        ? '<span class="bodega-badge">SE SACA DE BODEGA</span>'
        : '<span class="confeciona-badge">SE CONFECCIONA</span>';

      // Botón deshabilitado si es de bodega
      const botonDisabled = datos.esDeBodega ? 'disabled' : '';
      const botonTitle = datos.esDeBodega 
        ? 'Prenda de bodega - no disponible para seguimiento' 
        : 'Ver seguimiento detallado';
      const botonClass = datos.esDeBodega ? 'btn-ver-seguimiento disabled' : 'btn-ver-seguimiento';

      tableHtml += `
        <tr class="prendas-table-row" data-prenda-index="${index}">
          <td class="prendas-table-cell prendas-name-cell">
            <div class="prendas-name">${datos.nombrePrenda}</div>
            ${badgeHtml}
          </td>
          <td class="prendas-table-cell">${datos.cantidad}</td>
          <td class="prendas-table-cell procesos-cell">
            <div class="procesos-info">${datos.procesosInfo}</div>
          </td>
          <td class="prendas-table-cell">${datos.area}</td>
          <td class="prendas-table-cell">
            <span class="estado-badge estado-${datos.estadoPedido.toLowerCase().replace(/_/g, '-')}">${datos.estadoDisplay}</span>
          </td>
          <td class="prendas-table-cell acciones-cell">
            <button class="${botonClass}" ${botonDisabled} onclick="showPrendaTrackingFromTable(${index})" title="${botonTitle}">
              ${SvgIcons.view()}
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

  // Mostrar selector de prendas (overlay)
  function showPrendasSelector() {
    ModalUtils.open('trackingPrendasSelectorOverlay');
  }

  // Cerrar selector de prendas
  window.cerrarSelectorPrendas = function() {
    ModalUtils.close('trackingPrendasSelectorOverlay');
  };

  // Crear tabla simple de prenda (estilo TNS)
  function createPrendaCard(prenda, index) {
    const card = document.createElement('div');
    card.className = 'tracking-prenda-table';
    
    card.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      showPrendaTracking(prenda);
    });

    // Domain: Preparar datos de la prenda
    const datos = orderState.preparePrendaCardData(prenda);
    const nombrePrenda = prenda.nombre_prenda || `Prenda ${index + 1}`;

    // Procesos HTML
    let procesosHtml = '';
    if (datos.procesos.length > 0) {
      procesosHtml = '<tr><td colspan="2"><div class="tracking-procesos-lista">';
      datos.procesos.forEach(p => {
        procesosHtml += `
          <div class="tracking-proceso-item">
            <span class="proceso-nombre">${p.nombre}</span>
            <span class="proceso-estado">${p.estado}</span>
          </div>
        `;
      });
      procesosHtml += '</div></td></tr>';
    }

    // Badge de bodega
    const bodegaBadge = datos.bodega
      ? '<tr><td colspan="2"><div class="tracking-bodega-indicador">Se saca de bodega</div></td></tr>'
      : '';

    // Badges de seguimientos y áreas
    const seguimientosHtml = renderSeguimientosBadges(prenda.seguimientos || {});
    const areasHtml = renderAreasBadges(prenda.seguimientos_por_area || {});

    card.innerHTML = `
      <table class="tracking-table">
        <thead>
          <tr>
            <th colspan="2" class="tracking-table-header">${nombrePrenda}</th>
          </tr>
        </thead>
        <tbody>
          <tr class="tracking-table-row">
            <td class="tracking-table-label">Cantidad:</td>
            <td class="tracking-table-value">${datos.cantidad}</td>
          </tr>
          <tr class="tracking-table-row">
            <td class="tracking-table-label">Procesos:</td>
            <td class="tracking-table-value">${datos.procesos.length}</td>
          </tr>
          ${procesosHtml}
          ${bodegaBadge}
          ${seguimientosHtml ? `<tr><td colspan="2">${seguimientosHtml}</td></tr>` : ''}
          ${areasHtml ? `<tr><td colspan="2">${areasHtml}</td></tr>` : ''}
        </tbody>
      </table>
    `;
    
    return card;
  }

  // Permite editar el área actual incluso si no existe proceso (creación rápida con prefill)
  window.handleCrearProcesoDesdeArea = function(areaName, event, encargadoPrefill = '') {
    try {
      stopEventPropagation(event, true);

      if (typeof openAddProcesoModal !== 'function') {
        console.warn('[handleCrearProcesoDesdeArea] openAddProcesoModal no disponible');
        return;
      }

      // Asegurar que sea modo "agregar" (no edición)
      if (typeof resetFormButton === 'function') {
        resetFormButton();
      } else {
        orderState.setEditingProcessId(null);
      }

      openAddProcesoModal();

      const procesoArea = document.getElementById('procesoArea');
      if (procesoArea) procesoArea.value = areaName || '';

      // En modo "editar área actual" (sin id) prellenar encargado si lo tenemos
      const procesoEncargado = document.getElementById('procesoEncargado');
      const encargadoFallback = orderState.getConsecutivoCosturaData()?.encargado || '';
      const encargadoFinal = String(encargadoPrefill || encargadoFallback || '').trim();
      if (procesoEncargado) {
        procesoEncargado.value = encargadoFinal ? encargadoFinal.toUpperCase() : '';
      }
    } catch (e) {
      console.error('[handleCrearProcesoDesdeArea] Error:', e);
    }
  };

  // Renderizar badges genéricos (seguimientos o áreas)
  function renderBadges(items, containerClass, statusField, textFormatter) {
    if (Object.keys(items).length === 0) {
      return '';
    }
    
    let badgesHtml = `<div class="${containerClass}">`;
    
    Object.entries(items).forEach(([key, data]) => {
      const statusClass = data[statusField] ? 'pendiente' : 'completado';
      const text = textFormatter(key, data);
      
      badgesHtml += `
        <span class="tracking-seguimiento-badge ${statusClass}">
          ${text}
        </span>
      `;
    });
    
    badgesHtml += '</div>';
    return badgesHtml;
  }

  // Renderizar badges de seguimientos por tipo de recibo
  function renderSeguimientosBadges(seguimientos) {
    return renderBadges(
      seguimientos,
      'tracking-prenda-seguimientos',
      'tiene_disponibles',
      (tipo, data) => `${tipo}: ${data.consecutivo_actual}/${data.consecutivo_inicial}`
    );
  }

  // Renderizar badges de áreas/procesos
  function renderAreasBadges(areas) {
    return renderBadges(
      areas,
      'tracking-prenda-areas',
      'esta_activo',
      (area, data) => `${area}: ${data.estado}`
    );
  }

  // Mostrar seguimiento de una prenda específica desde la tabla
  window.showPrendaTrackingFromTable = async function(index) {
    try {
      console.log('[showPrendaTrackingFromTable] INICIO - Índice:', index);
      
      // Obtener la prenda desde el array global
      const prenda = orderState.getPrendas()[index];
      if (!prenda) {
        console.error('[showPrendaTrackingFromTable] Prenda no encontrada en índice:', index);
        return;
      }
      
      console.log('[showPrendaTrackingFromTable] Prenda encontrada:', prenda);
      
      // Llamar a la función original con el objeto prenda
      await showPrendaTracking(prenda);
      
    } catch (error) {
      console.error('[showPrendaTrackingFromTable] Error:', error);
    }
  };

  // Mostrar seguimiento de una prenda específica
  window.showPrendaTracking = async function(prenda) {
    try {
      // Hidratar prenda si no tiene seguimientos pero existen en prendasData
      const tieneSeguimiento = prenda && (
        (prenda.seguimientos_por_area && Object.keys(prenda.seguimientos_por_area).length > 0) ||
        (prenda.seguimientos && Object.keys(prenda.seguimientos).length > 0) ||
        (prenda.ultimo_recibo_numero && prenda.ultimo_recibo_numero !== '-')
      );

      if (!tieneSeguimiento && orderState.hasPrendas()) {
        const prendaId = prenda?.id || prenda?.prenda_pedido_id;
        const prendaEnriquecida = orderState.getPrendas().find(p =>
          String(p?.id) === String(prendaId) || String(p?.prenda_pedido_id) === String(prendaId)
        );
        if (prendaEnriquecida) {
          prenda = Object.assign({}, prendaEnriquecida, prenda);
        }
      }
      
      orderState.setCurrentPrenda(prenda);
      
      // Cerrar overlay de prendas si está abierto
      if (document.getElementById('trackingPrendasSelectorOverlay')) {
        cerrarSelectorPrendas();
      }
      
      // Mostrar modal de seguimiento
      const modal = document.getElementById('orderTrackingModal');
      if (!modal) return;

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

      iniciarTimerContadores();
      setupBackButton();
      
      // Ocultar vista de prendas y mostrar timeline
      document.getElementById('trackingPrendasContainer').parentElement.style.display = 'none';
      document.getElementById('trackingTimelineSection').style.display = 'block';
      
      // Controlar visibilidad botón agregar según readonly
      const btnAgregar = document.getElementById('btnOpenAddProcesoModal');
      if (btnAgregar) {
        btnAgregar.style.display = prenda?.readonly ? 'none' : 'block';
        btnAgregar.disabled = !!prenda?.readonly;
      }
      
      // Nombre de la prenda
      const nombreElement = document.getElementById('trackingPrendaName');
      if (nombreElement) {
        nombreElement.textContent = prenda.nombre_prenda || `Prenda ${prenda.id}`;
      }
      
      // Resolver área y recibo desde dominio
      const excludeOrderArea = window.location.pathname.includes('/recibos-costura');
      const areaActual = orderState.resolveAreaActual(prenda, { excludeOrderArea });
      const numeroRecibo = orderState.resolveReciboForPrenda(prenda);
      
      // Actualizar header del recibo
      const reciboHeaderElement = document.getElementById('trackingPrendaReciboHeader');
      if (reciboHeaderElement) {
        reciboHeaderElement.textContent = areaActual !== '-'
          ? `${numeroRecibo} - ${areaActual}`
          : numeroRecibo;
      }
      
      const reciboElement = document.getElementById('trackingPrendaRecibo');
      if (reciboElement) {
        reciboElement.textContent = numeroRecibo;
      }
      
      // Renderizar timeline
      renderPrendaTrackingTimeline(prenda);
      
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
    console.log('[renderPrendaTrackingTimeline] Seguimientos por área en prenda:', prenda.seguimientos_por_area);

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
    if (Object.keys(seguimientosPorArea).length === 0) {
      return;
    }

    // ──── Sección: Fechas de activación del recibo ────

    const reciboActivo = orderState.findActiveRecibo(prenda);
    const reciboCreatedAt = reciboActivo?.created_at || null;

    const activationSection = document.createElement('div');
    activationSection.className = 'tracking-section tracking-section-activation';

    const activationTitle = document.createElement('div');
    activationTitle.className = 'tracking-section-title';
    activationTitle.textContent = 'Activación del recibo:';
    activationSection.appendChild(activationTitle);

    const fechasWrapper = document.createElement('div');
    fechasWrapper.className = 'tracking-info-row';

    // Helper: crear card de info
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

    const fechaCreacionOrden = orderState.getOrder()?.fecha_de_creacion_de_orden || null;
    fechasWrapper.appendChild(createInfoCard(
      'Fecha creación orden',
      formatDateTime(fechaCreacionOrden) || '-',
      SvgIcons.calendar()
    ));

    fechasWrapper.appendChild(createInfoCard(
      'Fecha activación recibo',
      formatDateTime(reciboCreatedAt) || '-',
      SvgIcons.checkCircle()
    ));

    // Tiempo transcurrido: duración real + días hábiles como referencia
    let tiempoTranscurridoText = '-';
    const fechaCreacionDate = toDateObject(fechaCreacionOrden);
    const reciboActDate = toDateObject(reciboCreatedAt);
    if (fechaCreacionDate && reciboActDate) {
      const diffMs = Math.max(0, reciboActDate.getTime() - fechaCreacionDate.getTime());
      const human = formatDurationHuman(diffMs);
      const diasHabiles = calcularDiasHabilesSync(fechaCreacionDate, reciboActDate);
      tiempoTranscurridoText = diasHabiles > 0
        ? `${human} (${diasHabiles} días hábiles)`
        : human;
    }

    fechasWrapper.appendChild(createInfoCard(
      'Tiempo transcurrido',
      tiempoTranscurridoText,
      SvgIcons.clock()
    ));

    activationSection.appendChild(fechasWrapper);
    container.appendChild(activationSection);

    // ──── Sección: Seguimiento por áreas ────

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

    // ──── Inyectar área virtual Insumos (si es necesario) ────

    const mergedAreas = { ...seguimientosPorArea };
    const hasInsumos = Object.keys(mergedAreas).some(k => String(k || '').toLowerCase() === 'insumos');

    if (!hasInsumos && reciboCreatedAt) {
      // Encontrar la fecha de envío a producción (primer proceso iniciado)
      let fechaEnvioProduccion = null;
      const areaCorteKey = Object.keys(mergedAreas).find(k => String(k || '').toLowerCase().includes('corte'));
      
      if (areaCorteKey) {
        fechaEnvioProduccion = mergedAreas[areaCorteKey]?.fecha_inicio || null;
      } else {
        // Fallback: encontrar la fecha_inicio más temprana (lo más cercano a "envío")
        let bestKey = null;
        let bestDate = null;
        Object.entries(mergedAreas).forEach(([k, v]) => {
          const d = toDateObject(v?.fecha_inicio);
          if (!d) return;
          if (!bestDate || d.getTime() < bestDate.getTime()) {
            bestDate = d;
            bestKey = k;
          }
        });
        if (bestKey) {
          fechaEnvioProduccion = mergedAreas[bestKey]?.fecha_inicio || null;
        }
      }

      const yaEnviadoAProduccion = Boolean(fechaEnvioProduccion);
      const tiempoInsumosMs = fechaEnvioProduccion
        ? Math.max(0, toDateObject(fechaEnvioProduccion)?.getTime() - toDateObject(reciboCreatedAt)?.getTime() || 0)
        : null;

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
        tiempo_transcurrido: tiempoInsumosMs ? formatDurationHuman(tiempoInsumosMs) : null
      };
    }

    // ──── Ordenar y renderizar áreas ────

    const orderedEntries = [];
    if (mergedAreas['Insumos']) {
      orderedEntries.push(['Insumos', mergedAreas['Insumos']]);
    }
    Object.entries(mergedAreas).forEach(([area, data]) => {
      if (String(area || '').toLowerCase() === 'insumos') return;
      orderedEntries.push([area, data]);
    });

    orderedEntries.forEach(([area, data]) => {
      const areaCard = createAreaCard(area, data, prenda?.readonly || false);
      areasSection.appendChild(areaCard);
    });
  }

  // Mostrar mensaje si no hay seguimientos
  function renderNoSeguimiento(container) {
    const noSeguimiento = document.createElement('div');
    noSeguimiento.className = 'tracking-no-seguimiento';

    // Mantener el mensaje original
    noSeguimiento.innerHTML = '<p>No hay seguimientos registrados para esta prenda</p>';
    container.appendChild(noSeguimiento);

    // Usar la UI original del tracking para mostrar el área actual y encargado si se puede
    // (sin inventar una vista nueva). La edición/creación se hace con el botón "Agregar Área".
    const prenda = orderState.getCurrentPrenda() || {};
    const esRecibosCostura = window.location.pathname.includes('/recibos-costura');

    const procesoIdFallback = orderState.getConsecutivoCosturaData()?.proceso_id || null;
    const tieneProcesoReal = Boolean(prenda?.ultimo_proceso_id || procesoIdFallback);

    const areaActual = prenda?.ultimo_proceso_area
      || (prenda?.area && String(prenda.area).trim() !== '' ? prenda.area : null)
      || (!esRecibosCostura && orderState.getOrder()?.area && String(orderState.getOrder().area).trim() !== '' ? orderState.getOrder().area : null)
      || null;

    // Encargado real solo desde procesos_prenda; fallback a /consecutivo-costura si está disponible
    const encargadoActual = prenda?.ultimo_proceso_encargado
      || orderState.getConsecutivoCosturaData()?.encargado
      || null;

    // Si hay algo que mostrar, renderizar una tarjeta estándar de área.
    if (tieneProcesoReal && areaActual && typeof createAreaCard === 'function') {
      const estadoUltimo = prenda?.ultimo_proceso_estado || 'Pendiente';
      const estaActivo = estadoUltimo !== 'Completado';

      const fechaInicioFallback = orderState.getConsecutivoCosturaData()?.fecha_inicio || null;
      const fechaFinFallback = orderState.getConsecutivoCosturaData()?.fecha_fin || null;

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

  // Manejar eliminación de proceso
  window.handleEliminarProceso = async function(procesoId, areaName, event) {
    // Detener propagación para evitar que se cierre el modal
    stopEventPropagation(event);
    
    // Mostrar modal de confirmación
    showConfirmDeleteModal(procesoId, areaName);
  };

  // Mostrar modal de confirmación para eliminar
  function showConfirmDeleteModal(procesoId, areaName) {
    const processNameSpan = document.getElementById('deleteProcessName');
    
    if (processNameSpan) {
      processNameSpan.textContent = areaName;
    }
    
    // Guardar el ID del proceso a eliminar
    orderState.setProcessToDelete({ id: procesoId, name: areaName });
    
    // Configurar listeners
    setupConfirmDeleteModalListeners();
    
    // Mostrar modal con estilos forzados
    ModalUtils.openWithForce('confirmDeleteModal');
  }

  // Configurar listeners del modal de confirmación
  function setupConfirmDeleteModalListeners() {
    // Botón cancelar
    const btnCancel = document.getElementById('btnCancelDelete');
    if (btnCancel) {
      btnCancel.onclick = closeConfirmDeleteModal;
    }
    
    // Botón cerrar (X)
    const btnClose = document.getElementById('closeConfirmDeleteModal');
    if (btnClose) {
      btnClose.onclick = closeConfirmDeleteModal;
    }
    
    // Botón confirmar eliminar
    const btnConfirm = document.getElementById('btnConfirmDelete');
    if (btnConfirm) {
      btnConfirm.onclick = executeDeleteProcess;
    }
    
    // Cerrar al hacer clic en el overlay
    const overlay = document.querySelector('.confirm-delete-overlay');
    if (overlay) {
      overlay.onclick = closeConfirmDeleteModal;
    }
  }

  // Cerrar modal de confirmación
  function closeConfirmDeleteModal() {
    ModalUtils.close('confirmDeleteModal', () => {
      orderState.clearProcessToDelete();
    });
  }

  // Ejecutar la eliminación del proceso
  async function executeDeleteProcess() {
    if (!orderState.getProcessToDelete()) return;
    
    // Mostrar indicador de carga
    setButtonLoading('deleteButtonContent', 'deleteButtonLoading', 'btnConfirmDelete', true);
    
    const { id: procesoId, name: areaName } = orderState.getProcessToDelete();
    
    try {
      // Eliminar proceso via OrderApiService
      const result = await OrderApiService.deleteProceso(procesoId);

      // Cerrar modal de confirmación
      closeConfirmDeleteModal();

      // Recargar seguimientos de la prenda
      await loadPrendasWithTracking(orderState.getOrderId());

      // Refrescar consecutivo/area/encargado/fechas
      try {
        if (window.location.pathname.includes('/recibos-costura') && orderState.getOrderId() && orderState.getCurrentPrenda()?.id) {
          const data = await OrderApiService.loadConsecutivoCostura(orderState.getOrderId(), orderState.getCurrentPrenda().id);
          orderState.setConsecutivoCosturaData(data);
        }
      } catch (e) {
        // Silenciosamente ignorar si no se puede refrescar
      }
      
      // Buscar la prenda actualizada en los datos recargados
      if (orderState.hasPrendas() && orderState.getCurrentPrenda()) {
        const prendaActualizada = orderState.getPrendas().find(p => p.id == orderState.getCurrentPrenda().id);
        if (prendaActualizada) {
          orderState.setCurrentPrenda(prendaActualizada);
        }
      }
      
      // Actualizar vista actual
      if (orderState.getCurrentPrenda()?.id) {
        renderPrendaTrackingTimeline(orderState.getCurrentPrenda());
      } else {
        // Si no hay currentPrendaData, intentar obtener la primera prenda del DOM
        const prendaCards = document.querySelectorAll('.prenda-card');
        if (prendaCards.length > 0) {
          const firstCard = prendaCards[0];
          const prendaId = parseInt(firstCard.dataset.prendaId);
          
          // Buscar en prendasData
          let prendaParaRender = null;
          if (orderState.hasPrendas()) {
            prendaParaRender = orderState.getPrendas().find(p => p.id == prendaId);
          }
          
          if (prendaParaRender) {
            orderState.setCurrentPrenda(prendaParaRender);
            renderPrendaTrackingTimeline(prendaParaRender);
          } else {
            // Fallback: crear objeto con el ID
            const prendaData = {
              id: prendaId,
              nombre_prenda: firstCard.querySelector('.prenda-name')?.textContent,
            };
            renderPrendaTrackingTimeline(prendaData);
          }
        }
      }

      // Mostrar mensaje de éxito
      showSuccess('Proceso eliminado correctamente');
      
      // Actualizar el área en la tabla de recibos-costura si estamos en esa página
      actualizarAreaEnTablaRecibos();

    } catch (error) {
      console.error('[executeDeleteProcess] Error:', error);
      showError('Error al eliminar proceso: ' + error.message);
      closeConfirmDeleteModal();
    } finally {
      // Restaurar estado del botón
      setButtonLoading('deleteButtonContent', 'deleteButtonLoading', 'btnConfirmDelete', false);
    }
  }
  
  // Actualizar el área en la tabla de recibos-costura
  async function actualizarAreaEnTablaRecibos() {
    try {
      console.log('[actualizarAreaEnTablaRecibos] Verificando si estamos en recibos-costura');
      
      // Verificar si estamos en la página de recibos-costura
      if (!window.location.pathname.includes('/recibos-costura')) {
        console.log('[actualizarAreaEnTablaRecibos] No estamos en recibos-costura, omitiendo actualización');
        return;
      }

      const pedidoId = orderState.getOrderId();
      const prendaId = orderState.getCurrentPrenda()?.id || null;
      const numeroRecibo = orderState.getConsecutivoCosturaData()?.consecutivo || null;

      if (!pedidoId || !prendaId || !numeroRecibo) {
        console.warn('[actualizarAreaEnTablaRecibos] Datos insuficientes para refrescar fila', {
          pedidoId,
          prendaId,
          numeroRecibo
        });
        return;
      }

      const row = findReciboCosturaRow(pedidoId, prendaId, numeroRecibo);
      if (!row) {
        console.warn('[actualizarAreaEnTablaRecibos] No se encontró fila a actualizar', {
          pedidoId,
          prendaId,
          numeroRecibo
        });
        return;
      }

      const data = await OrderApiService.loadConsecutivoCostura(pedidoId, prendaId);
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

  function findReciboCosturaRow(pedidoId, prendaId, numeroRecibo) {
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

  // Manejar edición de proceso
  window.handleEditarProceso = function(procesoId, areaName, processData, event) {
    // Detener propagación para evitar que se cierre el modal
    stopEventPropagation(event);
    
    // Abrir el modal primero
    openAddProcesoModal();
    
    // Obtener elementos del formulario
    const elements = getProcessFormElements();
    
    // Establecer datos del formulario
    setProcessFormData(elements, processData);
    
    // Guardar el ID del proceso que se está editando
    orderState.setEditingProcessId(procesoId);
    
    // Cambiar el texto del botón a "Actualizar"
    const btnConfirmar = document.getElementById('btnConfirmAddProceso');
    if (btnConfirmar) {
      btnConfirmar.textContent = 'Actualizar Proceso';
      btnConfirmar.onclick = function() { handleActualizarProceso(procesoId); };
    }
    
    // Esperar a que el selector se cree dinámicamente (si es necesario) antes de establecer el encargado
    setTimeout(() => {
      setEncargadoValue(elements.inputEncargado, elements.selectEncargado, processData.encargado);
    }, 150);
  };

  // Manejar actualización de proceso
  window.handleActualizarProceso = async function(procesoId) {
    try {
      const elements = getProcessFormElements();
      const procesoAreaEl = elements.area;
      const procesoEstadoEl = elements.estado;
      const procesoFechaInicioEl = elements.fechaInicio;
      const procesoObservacionesEl = elements.observaciones;
      const encargado = getEncargadoValue(elements.inputEncargado, elements.selectEncargado);

      if (!procesoAreaEl) {
        throw new Error('No se encontró el campo de área. Por favor recarga la página.');
      }

      const procesoData = collectProcessFormData(elements, encargado);

      // Actualizar proceso via OrderApiService (validación y error handling centralizado)
      const result = await OrderApiService.updateProceso(procesoId, procesoData);

      // Limpiar formulario y resetear botón
      limpiarFormularioProceso();
      resetFormButton();

      // Cerrar modal de agregar/editar proceso
      try {
        closeAddProcesoModal();
      } catch (e) {
        // Silenciosamente ignorar si no se puede cerrar
      }

      // Recargar seguimientos de la prenda
      const orderId = orderState.getOrderId();
      if (orderId) {
        await loadPrendasWithTracking(orderId);
      } else {
        console.warn('[handleActualizarProceso] currentOrderData.id no disponible, no se recargan prendas');
      }
      
      // Actualizar vista actual
      if (orderState.getCurrentPrenda()?.id && Array.isArray(orderState.getPrendas())) {
        const prendaActualizada = orderState.getPrendas().find(p => String(p.id) === String(orderState.getCurrentPrenda().id));
        if (prendaActualizada) {
          orderState.setCurrentPrenda(prendaActualizada);
        }
      }

      if (orderState.getCurrentPrenda()) {
        renderPrendaTrackingTimeline(orderState.getCurrentPrenda());
      }

      // Mostrar mensaje de éxito
      showSuccess('Proceso actualizado correctamente');

      // Actualizar la fila en la tabla de recibos-costura si estamos en esa página
      await actualizarAreaEnTablaRecibos();

    } catch (error) {
      console.error('[handleActualizarProceso] Error:', error);
      showError('Error al actualizar proceso: ' + error.message);
    }
  };

  // Detener propagación de evento (previno bubbling y default)
  function stopEventPropagation(event, preventDefault = false) {
    if (event) {
      if (preventDefault) event.preventDefault();
      event.stopPropagation();
    }
  }

  // Establecer botón en estado de carga
  function setButtonLoading(contentId, loadingId, buttonId, isLoading = true) {
    const content = document.getElementById(contentId);
    const loading = document.getElementById(loadingId);
    const button = document.getElementById(buttonId);
    
    if (content && loading && button) {
      if (isLoading) {
        content.style.display = 'none';
        loading.style.display = 'flex';
        button.disabled = true;
      } else {
        content.style.display = 'flex';
        loading.style.display = 'none';
        button.disabled = false;
      }
    }
  }

  // Obtener todos los elementos del formulario de proceso
  function getProcessFormElements() {
    return {
      area: document.getElementById('procesoArea'),
      estado: document.getElementById('procesoEstado'),
      fechaInicio: document.getElementById('procesoFechaInicio'),
      observaciones: document.getElementById('procesoObservaciones'),
      inputEncargado: document.getElementById('procesoEncargado'),
      selectEncargado: document.getElementById('procesoEncargadoSelect')
    };
  }

  // Obtener valor del encargado (select o input)
  function getEncargadoValue(inputEncargado, selectEncargado) {
    if (selectEncargado && selectEncargado.offsetParent !== null) {
      const selectedOption = selectEncargado.options[selectEncargado.selectedIndex];
      return selectedOption ? selectedOption.text : '';
    }
    return inputEncargado ? inputEncargado.value : '';
  }

  // Recopilar datos del formulario de proceso
  function collectProcessFormData(elements, encargado) {
    const area = elements.area.value;
    const estado = elements.estado ? elements.estado.value : 'Pendiente';
    const fechaInicio = elements.fechaInicio ? elements.fechaInicio.value : '';
    const observaciones = elements.observaciones ? elements.observaciones.value : '';

    return {
      area: area,
      estado: estado,
      fecha_inicio: fechaInicio || null,
      encargado: encargado,
      observaciones: observaciones
    };
  }

  // Establecer datos del formulario (rellenar para edición)
  function setProcessFormData(elements, processData) {
    // Establecer área
    if (elements.area) {
      elements.area.value = processData.area || '';
      const changeEvent = new Event('change', { bubbles: true });
      elements.area.dispatchEvent(changeEvent);
    }

    // Establecer estado
    if (elements.estado) {
      elements.estado.value = processData.estado || 'Pendiente';
    }

    // Establecer fecha de inicio (formato YYYY-MM-DD)
    if (elements.fechaInicio && processData.fecha_inicio) {
      const date = new Date(processData.fecha_inicio);
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      elements.fechaInicio.value = `${year}-${month}-${day}`;
    }

    // Establecer observaciones
    if (elements.observaciones) {
      elements.observaciones.value = processData.observaciones || '';
    }
  }

  // Establecer encargado en select o input (después de que se cree dinámicamente)
  function setEncargadoValue(inputEncargado, selectEncargado, encargado) {
    if (selectEncargado && selectEncargado.offsetParent !== null) {
      // Es un select - buscar opción por nombre
      const options = selectEncargado.options;
      const encargadoValue = encargado || '';
      
      for (let i = 0; i < options.length; i++) {
        if (options[i].text.toLowerCase() === encargadoValue.toLowerCase()) {
          selectEncargado.value = options[i].value;
          break;
        }
      }
    } else if (inputEncargado) {
      // Es un input - establecer el valor directamente
      inputEncargado.value = encargado || '';
    }
  }

  // Limpiar formulario de proceso
  function limpiarFormularioProceso() {
    const procesoArea = document.getElementById('procesoArea');
    if (procesoArea) procesoArea.value = '';

    const procesoEncargado = document.getElementById('procesoEncargado');
    if (procesoEncargado) procesoEncargado.value = '';

    const procesoEncargadoSelect = document.getElementById('procesoEncargadoSelect');
    if (procesoEncargadoSelect) procesoEncargadoSelect.value = '';

    const procesoEstado = document.getElementById('procesoEstado');
    if (procesoEstado) procesoEstado.value = 'Pendiente';

    const procesoFechaInicio = document.getElementById('procesoFechaInicio');
    if (procesoFechaInicio) procesoFechaInicio.value = '';

    const procesoObservaciones = document.getElementById('procesoObservaciones');
    if (procesoObservaciones) procesoObservaciones.value = '';
  }

  // Resetear botón del formulario a su estado original
  function resetFormButton() {
    const btnConfirmar = document.getElementById('btnConfirmAddProceso');
    if (btnConfirmar) {
      btnConfirmar.textContent = 'Agregar Proceso';
      btnConfirmar.onclick = handleAgregarProceso;
    }
    orderState.setEditingProcessId(null);
  }

  // Crear tarjeta de área/proceso
  function createAreaCard(area, data, readonly = false) {
    const card = document.createElement('div');
    const metadata = orderState.resolveAreaMetadata(area);
    const { estadoDisplay, estaActivoDisplay } = orderState.resolveAreaStatus(data, metadata);

    if (readonly) {
      card.classList.add('tracking-readonly-mode');
    }

    const iconSvg = SvgIcons.get(area);

    // ──── Helpers para cálculo de duraciones ────
    
    const formatBadgeDuration = (diffMs) => {
      const ms = Math.max(0, Number(diffMs) || 0);
      const minutes = Math.floor(ms / 60000);
      const hours = Math.floor(ms / 3600000);
      const days = Math.floor(ms / 86400000);
      return days >= 1 ? `${days} ${days === 1 ? 'Día' : 'Días'}` 
           : hours >= 1 ? `${hours}h`
           : minutes >= 1 ? `${minutes}min`
           : '< 1min';
    };

    const extractNumericDays = (text) => {
      if (text === '---' || !text) return 0;
      const match = String(text).match(/(\d+)/);
      return match ? parseInt(match[1], 10) : 0;
    };

    // ──── Cálculos de fechas ────
    
    const fechaCompletadoDisplay = data.fecha_completado || null;
    const fechaFinParaDuracion = fechaCompletadoDisplay || data.fecha_fin || null;
    
    const fechaLlegada = formatDate(data.fecha_inicio) || '---';
    const fechaAsignacion = formatDate(data.fecha_de_asignacion_encargado) || '---';
    
    let fechaFinRaw = null;
    if (metadata.isInsumos) {
      fechaFinRaw = data.fecha_fin || null;
    } else if (metadata.needsEncargado) {
      fechaFinRaw = data.fecha_completado || null;
    } else {
      fechaFinRaw = data.fecha_fin || null;
    }
    
    const fechaFin = formatDate(fechaFinRaw) || (data.esta_activo ? '---' : '---');

    // ──── Cálculos de duraciones ────
    
    const calcularDuracionAsignacion = () => {
      if (!metadata.needsEncargado) return '---';
      const ini = toDateObject(data.fecha_inicio);
      const asg = toDateObject(data.fecha_de_asignacion_encargado);
      if (!ini || !asg) return '---';
      return formatBadgeDuration(asg.getTime() - ini.getTime());
    };

    const calcularDuracionEnArea = () => {
      const ini_asignacion = toDateObject(data.fecha_de_asignacion_encargado);
      const ini = toDateObject(data.fecha_inicio);
      const fin = fechaFinRaw ? toDateObject(fechaFinRaw) : new Date();
      
      if (metadata.needsEncargado) {
        if (!ini_asignacion || !fin) return '---';
        const dias = calcularDiasHabilesSync(ini_asignacion, fin);
        return dias === 0 ? '0 días' : `${dias} día${dias !== 1 ? 's' : ''}`;
      } else {
        if (!ini || !fin) return '---';
        const dias = calcularDiasHabilesSync(ini, fin);
        return dias === 0 ? '0 días' : `${dias} día${dias !== 1 ? 's' : ''}`;
      }
    };

    const calcularTotalDiasDisplay = () => {
      if (!metadata.needsEncargado) {
        const ini = toDateObject(data.fecha_inicio);
        const fin = fechaFinRaw ? toDateObject(fechaFinRaw) : new Date();
        if (!ini || !fin) return '---';
        const dias = calcularDiasHabilesSync(ini, fin);
        return dias === 0 ? '0 días' : `${dias} día${dias !== 1 ? 's' : ''}`;
      }

      if (!fechaFinRaw) {
        const durAsig = calcularDuracionAsignacion();
        const durArea = calcularDuracionEnArea();
        const suma = extractNumericDays(durAsig) + extractNumericDays(durArea);
        return suma === 0 ? '0 días' : `${suma} día${suma !== 1 ? 's' : ''}`;
      }

      const ini = toDateObject(data.fecha_inicio);
      const asg = toDateObject(data.fecha_de_asignacion_encargado);
      const fin = toDateObject(fechaFinRaw);
      if (!ini || !fin) return '---';
      const inicioCalculo = asg || ini;
      const dias = calcularDiasHabilesSync(inicioCalculo, fin);
      return dias === 0 ? '0 días' : `${dias} día${dias !== 1 ? 's' : ''}`;
    };

    const duracionAsignacion = calcularDuracionAsignacion();
    const duracionEnArea = calcularDuracionEnArea();
    const totalDiasAreaDisplay = calcularTotalDiasDisplay();

    // ──── HTML de acciones ────
    
    const accionesHtml = readonly ? '' : `${(data.id || data.can_edit) ? `
            <button class="tracking-edit-btn" onclick="${data.id ? `handleEditarProceso(${data.id}, '${area}', ${JSON.stringify(data).replace(/"/g, '&quot;')}, event)` : `handleCrearProcesoDesdeArea('${area}', event, '${String(data.encargado || '').replace(/'/g, "\\'")}')`}" title="Editar proceso">
              ${SvgIcons.edit()}
            </button>
            ${data.id ? `
            <button class="tracking-delete-btn" onclick="handleEliminarProceso(${data.id}, '${area}', event)" title="Eliminar proceso">
              ${SvgIcons.delete()}
            </button>
            ` : ''}
            ` : ''}`;

    // ──── Construir HTML del card ────

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
    } else {
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
              <div class="tracking-area-v2-pill">${data.encargado || '---'}</div>
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
    }
    
    return card;
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

  // ============================================================
  // ALIASES: Delegación a dateUtils (Infrastructure Layer)
  // Estas funciones antes estaban duplicadas aquí (~180 líneas).
  // Ahora son aliases de una línea que delegan al módulo centralizado.
  // ============================================================
  const formatDate = (dateString) => dateUtils.formatDate(dateString);
  const formatDateTime = (dateString) => dateUtils.formatDateTime(dateString);
  const normalizeConsecutivos = (consecutivos) => dateUtils.normalizeConsecutivos(consecutivos);
  const toDateObject = (value) => dateUtils.toDateObject(value);
  const calcularDiasHabilesSync = (fechaInicio, fechaFin) => dateUtils.calcularDiasHabilesSync(fechaInicio, fechaFin);
  const formatDurationHuman = (diffMs) => dateUtils.formatDurationHuman(diffMs);

  // Mostrar error
  function showError(message) {
    console.error('[showError] ' + message);
    // Usar el sistema global de toasts
    if (window.showToast) {
      window.showToast(message, 'error');
    }
  }

  // Manejar agregar proceso
  async function handleAgregarProceso() {
    try {
      // Mostrar indicador de carga
      const btnContent = document.getElementById('addProcesoButtonContent');
      const btnLoading = document.getElementById('addProcesoButtonLoading');
      const btnConfirm = document.getElementById('btnConfirmAddProceso');
      
      if (btnContent && btnLoading && btnConfirm) {
        btnContent.style.display = 'none';
        btnLoading.style.display = 'flex';
        btnConfirm.disabled = true;
      }

      const area = document.getElementById('procesoArea').value;
      const encargado = document.getElementById('procesoEncargado').value.toUpperCase();

      if (!area) {
        showError('Por favor selecciona un área/proceso');
        // Ocultar indicador de carga
        if (btnContent && btnLoading && btnConfirm) {
          btnContent.style.display = 'flex';
          btnLoading.style.display = 'none';
          btnConfirm.disabled = false;
        }
        return;
      }

      // Validar encargado solo para áreas que lo requieren
      const areaLower = area.toLowerCase();
      const needsEncargado = ['corte', 'costura', 'control de calidad'];
      const areaRequiresEncargado = needsEncargado.some(reqArea => areaLower.includes(reqArea));
      
      if (areaRequiresEncargado && !encargado.trim()) {
        showError('Por favor ingresa el nombre del encargado');
        // Ocultar indicador de carga
        if (btnContent && btnLoading && btnConfirm) {
          btnContent.style.display = 'flex';
          btnLoading.style.display = 'none';
          btnConfirm.disabled = false;
        }
        return;
      }

      if (!orderState.getCurrentPrenda() || !orderState.getOrder()) {
        showError('No hay datos de la prenda o pedido');
        // Ocultar indicador de carga
        if (btnContent && btnLoading && btnConfirm) {
          btnContent.style.display = 'flex';
          btnLoading.style.display = 'none';
          btnConfirm.disabled = false;
        }
        return;
      }

      const currentPrenda = orderState.getCurrentPrenda();
      const currentOrder = orderState.getOrder();

      console.log('[handleAgregarProceso] Agregando proceso:', {
        area,
        encargado,
        prenda_id: currentPrenda.id,
        orderId: currentOrder.id
      });

      if (!currentOrder.numero_pedido) {
        throw new Error('No hay número de pedido');
      }

      // Preparar datos del proceso para enviar
      const procesoData = {
        pedido_produccion_id: currentOrder.numero_pedido,
        prenda_id: currentPrenda.id,
        area: area,
        encargado: encargado,
        estado: 'Pendiente'
      };

      // Guardar proceso via OrderApiService (validación y error handling centralizado)
      const result = await OrderApiService.saveProceso(currentPrenda.id, procesoData);
      console.log('[handleAgregarProceso] Proceso guardado:', result);

      // Limpiar formulario
      limpiarFormularioProceso();

      // Cerrar modal de agregar proceso
      const modal = document.getElementById('addProcesoModal');
      if (modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
      }

      // Actualizar datos de la prenda con la respuesta del backend
      if (result.data && result.data.prenda) {
        orderState.setCurrentPrenda(result.data.prenda);
        console.log('[handleAgregarProceso] Prenda actualizada desde backend:', result.data.prenda);
        
        // Renderizar timeline con los datos actualizados
        renderPrendaTrackingTimeline(orderState.getCurrentPrenda());
      } else {
        // Si no vienen datos de la prenda, recargar desde el endpoint
        console.log('[handleAgregarProceso] Recargando datos desde endpoint...');
        await loadPrendasWithTracking(orderState.getOrderId());
        
        // Buscar la prenda actualizada en los datos cargados
        const updated = orderState.refreshCurrentPrenda();
        if (updated) {
          renderPrendaTrackingTimeline(updated);
        }
      }

      // ✅ Mostrar mensaje diferente según si fue creado o actualizado
      const mensaje = result.action === 'actualizado' 
        ? 'Proceso actualizado correctamente' 
        : 'Proceso agregado correctamente';
      showSuccess(mensaje);

      // Actualizar la fila en la tabla de recibos-costura si estamos en esa página
      await actualizarAreaEnTablaRecibos();

    } catch (error) {
      console.error('[handleAgregarProceso] Error:', error);
      
      // Manejar específicamente errores de JSON
      if (error instanceof SyntaxError && error.message.includes('JSON')) {
        console.error('[handleAgregarProceso] Error de JSON - el servidor devolvió HTML en lugar de JSON');
        showError('Error del servidor: La respuesta no es válida. Posiblemente un error de permisos o validación.');
      } else {
        showError('Error al agregar proceso: ' + error.message);
      }
    } finally {
      // Ocultar indicador de carga
      const btnContent = document.getElementById('addProcesoButtonContent');
      const btnLoading = document.getElementById('addProcesoButtonLoading');
      const btnConfirm = document.getElementById('btnConfirmAddProceso');
      
      if (btnContent && btnLoading && btnConfirm) {
        btnContent.style.display = 'flex';
        btnLoading.style.display = 'none';
        btnConfirm.disabled = false;
      }
    }
  }

  // Mostrar mensaje de éxito
  function showSuccess(message) {
    // Usar el sistema global de toasts
    if (window.showToast) {
      window.showToast(message, 'success');
    }
  }

  // Inicializar cuando el DOM esté listo
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTrackingModalListeners);
  } else {
    initTrackingModalListeners();
  }
