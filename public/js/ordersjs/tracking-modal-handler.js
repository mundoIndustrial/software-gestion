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
import { TrackingTimelineController } from './presentation/TrackingTimelineController.js';

// ============================================================
// IMPORTS: Application Layer
// ============================================================
import {
  OrderApiService,
  ProcessDeleteService,
  ProcessFormValidationService,
  FormStateManager,
  DataReloadService,
  ProcessService,
  PrendaTrackingRenderer,
  AreaCardRenderer,
  BadgeRenderer,
  UpdateRenderer,
  createContainer
} from './application/index.js';

// Festivos now handled by backend CalculadorDiasService
// No need to preload client-side

// ============================================================
// DEPENDENCY INJECTION SETUP: DIContainer (Phase 11 - DIP)
// ============================================================

// Configure container with all dependencies
const container = createContainer({
  // Domain Layer
  orderState,
  dateFormatter: DateFormatter,
  
  // Infrastructure Layer
  svgIcons: SvgIcons,
  modalUtils: ModalUtils,
  dateUtils,
  queryUtils: QueryUtils,
  
  // API Service
  OrderApiService,
  
  // Callback services
  showSuccess,
  showError,
  closeConfirmDeleteModal,
  
  // Service classes (for lazy instantiation)
  ProcessDeleteService,
  ProcessFormValidationService,
  FormStateManager,
  DataReloadService,
  ProcessService,
  
  // Renderer classes (for lazy instantiation)
  PrendaTrackingRenderer,
  AreaCardRenderer,
  BadgeRenderer,
  UpdateRenderer
});

// Services obtained from DIContainer (lazy loading + singleton)
const formValidationService = container.get('formValidationService');
const formStateManager = container.get('formStateManager');


const dataReloadService = container.get('dataReloadService');

const processDeleteService = container.get('processDeleteService');

// 5. Servicio principal de procesos (orquestador)
let processService = container.get('processService');

// ============================================================
// RENDERER INSTANCES: from DIContainer (lazy loading + singleton)
// ============================================================

const prendaTrackingRenderer = container.get('prendaTrackingRenderer');
const areaCardRenderer = container.get('areaCardRenderer');
const badgeRenderer = container.get('badgeRenderer');
const updateRenderer = container.get('updateRenderer');

const trackingTimelineController = new TrackingTimelineController({
  orderState,
  svgIcons: SvgIcons,
  updateRenderer,
  formatDate: (value) => dateUtils.formatDate(value),
  showError,
  closePrendasSelector: () => window.cerrarSelectorPrendas?.(),
  setupBackButton,
  startCountersTimer: iniciarTimerContadores,
});

// Nota: dataReloadService y renderers serán actualizados cuando se definan las funciones de renderizado
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

  // Actualizar contadores de días dinámicos (DEPRECADO: backend pre-computa duraciones)
  // Mantener como stub para compatibilidad, pero no hace nada
  function actualizarContadoresDinamicos() {
    console.log('[actualizarContadoresDinamicos] DEPRECATED - Backend ya pre-computa duraciones');
  }

  // Iniciar timer para actualización automática de contadores (DEPRECADO)
  // Backend pre-computa duraciones, no necesita actualización dinámica
  function iniciarTimerContadores() {
    console.log('[iniciarTimerContadores] DEPRECATED - Backend pre-computa duraciones');
  }

  // Detener timer de contadores (DEPRECADO)
  function detenerTimerContadores() {
    console.log('[detenerTimerContadores] DEPRECATED - Backend pre-computa duraciones');
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

      // Áreas que requieren selector dinámico (desde configuración del backend)
      const areasConfig = orderState.getAreasConfig();
      const areasConSelectorDinamico = areasConfig?.areas_con_selector_dinamico || ['corte', 'costura'];
      
      if (areasConSelectorDinamico.some(a => area.includes(a))) {
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
    // Phase 10: Usar UpdateRenderer para separar lógica de presentación
    updateRenderer.updateOrderInfo(orderData, orderState, DateFormatter);
  }

  // Cargar prendas con seguimiento
  async function loadPrendasWithTracking(orderId) {
    try {
      // Application Layer: OrderApiService maneja la API
      const { prendas, areasConfig, pedido } = await OrderApiService.loadPrendasWithTracking(orderId);
      
      // Domain Layer: Guardar en estado centralizado
      orderState.setPrendas(prendas);
      orderState.setAreasConfig(areasConfig);

      // Enriquecer orden con recibo_principal del endpoint de seguimiento
      const order = orderState.getOrder();
      if (order && pedido.recibo_principal) {
        order.recibo_principal = pedido.recibo_principal;
      }
      
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
    
    // Phase 10: Usar PrendaTrackingRenderer para separar lógica de presentación
    prendaTrackingRenderer.renderPrendasTable(container, prendas, SvgIcons, orderState);
    
    // Actualizar fecha estimada de entrega del pedido
    updateEstimatedDeliveryDate();
  }

  // Actualizar fecha estimada de entrega del pedido
  function updateEstimatedDeliveryDate() {
    // Phase 10: Usar UpdateRenderer para separar lógica de presentación
    updateRenderer.updateEstimatedDeliveryDate(orderState, DateFormatter);
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
    // Phase 10: Usar BadgeRenderer para separar lógica de presentación
    return badgeRenderer.renderBadges(items, containerClass, statusField, textFormatter);
  }

  // Renderizar badges de seguimientos por tipo de recibo
  function renderSeguimientosBadges(seguimientos) {
    // Phase 10: Usar BadgeRenderer para separar lógica de presentación
    return badgeRenderer.renderSeguimientosBadges(seguimientos);
  }

  // Renderizar badges de áreas/procesos
  function renderAreasBadges(areas) {
    // Phase 10: Usar BadgeRenderer para separar lógica de presentación
    return badgeRenderer.renderAreasBadges(areas);
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
    await trackingTimelineController.showPrendaTracking(prenda);
  };

  // Renderizar timeline de seguimiento de prenda
  function renderPrendaTrackingTimeline(prenda) {
    trackingTimelineController.renderPrendaTrackingTimeline(prenda);
  }

  // Renderizar seguimientos por área (procesos)
  function renderSeguimientosPorArea(prenda, container) {
    trackingTimelineController.renderSeguimientosPorArea(prenda, container);
  }

  // Mostrar mensaje si no hay seguimientos
  function renderNoSeguimiento(container) {
    trackingTimelineController.renderNoSeguimiento(container);
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
      // Phase 9: Usar ProcessService para encapsular lógica de eliminación
      // Esto maneja: API + reload de datos + feedback al usuario
      const result = await processService.deleteProcess(procesoId, {
        areaName,
        orderId: orderState.getOrderId(),
        prendaId: orderState.getCurrentPrenda()?.id
      });

      if (!result.success) {
        throw result.error || new Error('Error desconocido');
      }

      // Limpiar estado
      orderState.clearProcessToDelete();
      
    } catch (error) {
      // El feedback es manejado por ProcessService + uiFeedbackService
      // No cerramos modal para permitir reintentos
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
  // Contrato: data DEBE incluir metadata, duraciones y fechas_formateadas (fuente única: backend)
  function createAreaCard(area, data, readonly = false) {
    return trackingTimelineController.createAreaCard(area, data, readonly);
  }

  // ============================================================
  // DEPENDENCY INJECTION: Actualizar servicios con callbacks de render (Phase 9)
  // ============================================================
  dataReloadService.setRenderers({
    renderPrendaTrackingTimeline,
    actualizarAreaEnTablaRecibos
  });

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
      const areasConfig = orderState.getAreasConfig();
      const areasQueRequierenEncargado = areasConfig?.areas_que_requieren_encargado || ['corte', 'costura', 'control de calidad'];
      const areaRequiresEncargado = areasQueRequierenEncargado.some(reqArea => areaLower.includes(reqArea));
      
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
