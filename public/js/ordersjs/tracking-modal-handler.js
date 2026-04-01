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
  ProcessService,
  ProcessDeleteService,
  ProcessFormValidationService,
  FormStateManager,
  DataReloadService,
  PrendaTrackingRenderer,
  AreaCardRenderer,
  BadgeRenderer,
  UpdateRenderer,
  SpecialReceiptsRenderer,
  createContainer,
  ProcessFormManager,
  ModalEventBinder,
  ButtonLoadingManager,
  DaysSelectorManager,
  AreasConfigService,
  ProcessWorkflowService,
  OrderLoaderService,
  DateFormatterFacade
} from '../application/index.js';


const container = createContainer({
  // Domain & Infrastructure
  orderState,
  dateFormatter: DateFormatter,
  svgIcons: SvgIcons,
  modalUtils: ModalUtils,
  dateUtils,
  queryUtils: QueryUtils,
  
  // API & Main Service
  OrderApiService,
  ProcessService,
  
  // Form & State Management Services
  ProcessFormValidationService,
  FormStateManager,
  
  // Data Management Services
  DataReloadService,
  ProcessDeleteService,
  
  // UI Managers
  ProcessFormManager,
  ModalEventBinder,
  ButtonLoadingManager,
  DaysSelectorManager,
  
  // Domain Services
  AreasConfigService,
  ProcessWorkflowService,
  
  // Renderers
  PrendaTrackingRenderer,
  AreaCardRenderer,
  BadgeRenderer,
  UpdateRenderer,
  
  // UI Feedback Callbacks
  showSuccess,
  showError,
  closeConfirmDeleteModal
});

const formManager = container.get('processFormManager');
const areasConfigService = container.get('areasConfigService');
const processWorkflowService = container.get('processWorkflowService');

const orderLoaderService = new OrderLoaderService({
  orderApiService: OrderApiService,
  orderState,
  onOrderLoaded: (orderData) => {
    updateOrderInfo(orderData);
  },
  onPrendasLoaded: (prendas) => {
    renderPrendas(prendas);
  },
  onError: (error) => {
    console.error('[OrderLoaderService] Error:', error);
    showError('Error al cargar datos de seguimiento');
  }
});

const dateFacade = new DateFormatterFacade(dateUtils);

// 5. Servicio principal de procesos (orquestador)
let processService = container.get('processService');

const prendaTrackingRenderer = container.get('prendaTrackingRenderer');
const badgeRenderer = container.get('badgeRenderer');
const updateRenderer = container.get('updateRenderer');

const trackingTimelineController = new TrackingTimelineController({
  orderState,
  svgIcons: SvgIcons,
  updateRenderer,
  formatDate: (value) => dateUtils.formatDate(value),
  showError,
  closePrendasSelector: () => globalThis.cerrarSelectorPrendas?.(),
  setupBackButton
});

// Instancia del renderer de recibos especiales
const specialReceiptsRenderer = new SpecialReceiptsRenderer();

// Instancia del selector de días (será inicializada en setupDaysSelector)
// Se expone globalmente para que otros módulos puedan acceder
let daysSelector = null;
globalThis.trackingDaysSelector = null;
let trackingModalListenersInitialized = false;

/**
 * Reinitialize the days selector when the modal opens
 * This ensures the selector is ready when data is loaded 
 * Call this right after the modal is shown
 */
function ensureDaysSelectorInitialized() {
  console.log('[ensureDaysSelectorInitialized] Verificando selector...');
  
  // Try to reinitialize if not already initialized
  if (!daysSelector || !daysSelector.initialized) {
    console.log('[ensureDaysSelectorInitialized] Reiniciando selector...');
    daysSelector = new DaysSelectorManager('trackingDaysSelector', {
      orderState,
      onSave: saveDiaEntregaSelection
    });
    daysSelector.initialize();
    globalThis.trackingDaysSelector = daysSelector;
    console.log('[ensureDaysSelectorInitialized]  Selector reiniciado');
    return;
  }
  
  // If already initialized but elements not cached, recache them
  if (!daysSelector.isValid()) {
    console.log('[ensureDaysSelectorInitialized] Recacheando elementos...');
    daysSelector.cacheElements();
  }
}

/**
 * Actualizar selector de días con reintentos
 * Espera a que el selector esté disponible antes de actualizar
 * 
 * @param {number} dias - Número de días a establecer (1-35)
 * @param {number} intento - Intento actual (máx 5)
 */
function updateDaysSelectorWithRetry(dias, intento = 0) {
  if (!dias) {
    console.log('[updateDaysSelectorWithRetry] No hay dias_de_entrega, saltando');
    return;
  }

  // Try to ensure selector is initialized first
  if (!globalThis.trackingDaysSelector) {
    ensureDaysSelectorInitialized();
  }

  if (globalThis.trackingDaysSelector && typeof globalThis.trackingDaysSelector.setValue === 'function') {
    console.log('[updateDaysSelectorWithRetry] Selector disponible, actualizando con:', dias);
    try {
      globalThis.trackingDaysSelector.setValue(dias);
      console.log('[updateDaysSelectorWithRetry]  Selector actualizado exitosamente');
    } catch (error) {
      console.error('[updateDaysSelectorWithRetry] Error al actualizar:', error);
    }
  } else if (intento < 10) {
    // Increase retries to 10 (1000ms total) to allow more time for initialization
    console.log(`[updateDaysSelectorWithRetry] Selector no disponible, reintentando (${intento + 1}/10)...`);
    setTimeout(() => updateDaysSelectorWithRetry(dias, intento + 1), 100);
  } else {
    console.warn('[updateDaysSelectorWithRetry] Selector no disponible después de 10 intentos');
  }
}

// Inicializar listeners del modal
function initTrackingModalListeners() {
  if (trackingModalListenersInitialized) {
    return;
  }
  trackingModalListenersInitialized = true;

  const binder = new ModalEventBinder('orderTrackingModal');
  
  binder.bindCloseButtons({
    closeButtonId: null,
    cancelButtonId: null,
    overlaySelector: '#trackingModalOverlay',
    callback: closeTrackingModal
  });

  const closeBtn = document.querySelector('.tracking-modal-close');
  if (closeBtn) {
    closeBtn.onclick = closeTrackingModal;
  }

  setupAddProcesoModalListeners();

  setupConfirmDeleteModalListeners();

  setupBackButton();

  setupDaysSelector();

}

  function setupDaysSelector() {
    daysSelector = new DaysSelectorManager('trackingDaysSelector', {
      orderState,
      onSave: saveDiaEntregaSelection
    });
    daysSelector.initialize();
    
    // Exponer globalmente para que UpdateRenderer pueda acceder
    globalThis.trackingDaysSelector = daysSelector;
    
    console.log('[setupDaysSelector] DaysSelector inicializado y expuesto en globalThis');
  }

  /**
   * Guardar selección de día de entrega desde el modal de seguimiento
   */
  async function saveDiaEntregaSelection() {
    try {
      const diasSeleccionados = orderState.getSelectedDays();
      const order = orderState.getOrder();

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

      const result = await OrderApiService.calculateDeliveryDate(order.id, diasSeleccionados);

      if (result.fecha_estimada) {
        const fechaFormateada = DateFormatter.format(result.fecha_estimada);
        QueryUtils.setText('trackingEstimatedDate', fechaFormateada);
        QueryUtils.setText('selectorOrderEstimatedDate', fechaFormateada);
      }

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

  const closeAddProcesoBtn = document.getElementById('closeAddProcesoModal');
  if (closeAddProcesoBtn) {
    closeAddProcesoBtn.onclick = closeAddProcesoModal;
  }

  const btnCancelAddProceso = document.getElementById('btnCancelAddProceso');
  if (btnCancelAddProceso) {
    btnCancelAddProceso.onclick = closeAddProcesoModal;
  }

  function closeTrackingModal() {
    ModalUtils.close('orderTrackingModal', () => {
      orderState.clear();
    });
  }

  function openAddProcesoModal() {
    if (!orderState.isEditing()) {
      if (typeof resetFormButton === 'function') {
        resetFormButton();
      }
      // Phase 12: Usar formManager.clear() para limpiar el formulario
      formManager.clear();
    }

    // Asegurar que los botones de cerrar funcionen
    ModalUtils.openWithForce('addProcesoModal', setupAddProcesoModalListeners);
  }

  // Configurar listeners del modal agregar proceso
  function setupAddProcesoModalListeners() {
    // Phase 12: Usar ModalEventBinder para patrón reutilizable
    const binder = new ModalEventBinder('addProcesoModal', { logger: console });
    
    binder
      .bindButtons([
        {
          selector: '#btnOpenAddProcesoModal',
          handler: openAddProcesoModal,
          event: 'click'
        }
      ])
      .bindCloseButtons({
        closeButtonId: 'closeAddProcesoModal',
        cancelButtonId: 'btnCancelAddProceso',
        overlaySelector: '#addProcesoOverlay',
        callback: closeAddProcesoModal
      });

    // Configurar selector dinámico para encargado
    setupEncargadoDynamicSelector();
  }

  // Función para configurar el selector dinámico de encargado
  // Phase 12: Refactorizado para usar AreasConfigService + ProcessFormManager
  function setupEncargadoDynamicSelector() {
    const procesoArea = document.getElementById('procesoArea');
    if (!procesoArea) return;
    if (procesoArea.dataset.trackingEncargadoBound === '1') return;
    procesoArea.dataset.trackingEncargadoBound = '1';

    procesoArea.addEventListener('change', async function(e) {
      const area = e.target.value;
      
      // Buscar el contenedor de encargado
      const formGroups = document.querySelectorAll('.add-proceso-form-group');
      let procesoEncargadoContainer = null;
      
      formGroups.forEach(group => {
        const label = group.querySelector('label');
        if (label && label.textContent.includes('Encargado')) {
          procesoEncargadoContainer = group;
        }
      });
      
      if (!procesoEncargadoContainer) {
        console.warn('[setupEncargadoDynamicSelector] Contenedor de encargado no encontrado');
        return;
      }

      // Phase 12: Usar AreasConfigService para determinar tipo de campo
      const fieldType = areasConfigService.getEncargadoFieldType(area);
      
      try {
        if (fieldType === 'select') {
          console.log('[setupEncargadoDynamicSelector] Crear selector para:', area);
          await createEncargadoSelect(area, procesoEncargadoContainer);
        } else {
          console.log('[setupEncargadoDynamicSelector] Crear input para:', area);
          createEncargadoInput(procesoEncargadoContainer);
        }
      } catch (error) {
        console.error('[setupEncargadoDynamicSelector] Error:', error);
      }
    });
  }

  // Convertir campo de encargado a SELECT
  async function createEncargadoSelect(area, container) {
    try {
      console.log('[createEncargadoSelect] Cargando encargados para:', area);
      const encargados = await OrderApiService.loadEncargados(area);
      
      formManager.createEncargadoField(container, 'select', 'procesoEncargado', encargados);
      
      console.log('[createEncargadoSelect] ✓ Encargados cargados:', encargados.length);
    } catch (error) {
      console.error('[createEncargadoSelect] Error:', error);
      createEncargadoInput(container);
    }
  }

  // Convertir campo de encargado a INPUT
  function createEncargadoInput(container) {
    formManager.createEncargadoField(container, 'input', 'procesoEncargado', []);
    console.log('[createEncargadoInput] Input de texto creado');
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
  }

  // Exponer función de reintentos del selector de días para que otros módulos puedan usarla
  globalThis.updateDaysSelectorWithRetry = updateDaysSelectorWithRetry;
  globalThis.ensureDaysSelectorInitialized = ensureDaysSelectorInitialized;

  // Abrir selector de prendas (overlay)
  globalThis.openOrderTracking = async function(orderId, mostrarSelector = true) {
    try {
      console.log('[openOrderTracking] Abriendo selector de prendas para orden:', orderId, 'mostrarSelector:', mostrarSelector);
      
      await orderLoaderService.loadCompleteOrder(orderId);
      
      // Poblar globalThis.currentOrderData con los datos cargados en orderState
      const snapshot = orderState.getSnapshot();
      const prendas = orderState.getPrendas(); // ← Obtener prendas directamente, no desde snapshot
      
      globalThis.currentOrderData = {
        id: snapshot.order?.id,
        numero_pedido: snapshot.order?.numero_pedido,
        cliente: snapshot.order?.cliente,
        estado: snapshot.order?.estado,
        prendas: prendas || []
      };
      
      console.log('[openOrderTracking] globalThis.currentOrderData poblado:', globalThis.currentOrderData);
      console.log('[openOrderTracking] Prendas copiadas a globalThis.currentOrderData:', prendas.length);
      
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
  globalThis.mostrarTrackingModal = function(pedidoData) {
    try {
      const orderId = pedidoData?.id || pedidoData?.pedido_id || pedidoData?.pedido?.id || null;
      if (!orderId) {
        console.error('[mostrarTrackingModal] No se encontró orderId en pedidoData:', pedidoData);
        return;
      }

      // El flujo nuevo carga datos desde /registros/{id}/... y abre el selector de prendas.
      globalThis.openOrderTracking(orderId, true);
    } catch (e) {
      console.error('[mostrarTrackingModal] Error:', e);
    }
  };



  // Actualizar información del pedido en el modal y selector
  function updateOrderInfo(orderData) {
    updateRenderer.updateOrderInfo(orderData, orderState, DateFormatter, daysSelector);
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

  function updateEstimatedDeliveryDate() {
    updateRenderer.updateEstimatedDeliveryDate(orderState, DateFormatter);
  }
  function showPrendasSelector() {
    ModalUtils.open('trackingPrendasSelectorOverlay');
  }
  globalThis.cerrarSelectorPrendas = function() {
    ModalUtils.close('trackingPrendasSelectorOverlay');
  };



  // Permite editar el área actual incluso si no existe proceso (creación rápida con prefill)
  globalThis.handleCrearProcesoDesdeArea = function(areaName, event, encargadoPrefill = '') {
    try {
      stopEventPropagation(event, true);
      resetFormButton();
      
      const encargadoFallback = orderState.getConsecutivoCosturaData()?.encargado || '';
      const encargadoFinal = String(encargadoPrefill || encargadoFallback || '').trim();
      
      formManager.setData({
        area: areaName || '',
        encargado: encargadoFinal ? encargadoFinal.toUpperCase() : ''
      });
      openAddProcesoModal();
      
    } catch (e) {
      console.error('[handleCrearProcesoDesdeArea] Error:', e);
    }
  };





  globalThis.showPrendaTrackingFromTable = async function(index) {
    try {
      console.log('[showPrendaTrackingFromTable] INICIO - Índice:', index);
      
      const prenda = orderState.getPrendas()[index];
      if (!prenda) {
        console.error('[showPrendaTrackingFromTable] Prenda no encontrada en índice:', index);
        return;
      }
      
      console.log('[showPrendaTrackingFromTable] Prenda encontrada:', prenda);
      
      await showPrendaTracking(prenda);
      
    } catch (error) {
      console.error('[showPrendaTrackingFromTable] Error:', error);
    }
  };

  globalThis.showPrendaTracking = async function(prenda) {
    await trackingTimelineController.showPrendaTracking(prenda);
  };

  globalThis.handleVerProcesos = async function(index) {
    try {
      console.log('[handleVerProcesos] Mostrando recibos especiales para prenda en índice:', index);
      
      const prenda = orderState.getPrendas()[index];
      if (!prenda) {
        console.error('[handleVerProcesos] Prenda no encontrada en índice:', index);
        return;
      }
      
      // Contar recibos especiales (BORDADO, ESTAMPADO, DTF, SUBLIMADO, REFLECTIVO)
      const recibosEspeciales = prenda.recibos_especiales || [];
      const procesosCount = Array.isArray(recibosEspeciales) ? recibosEspeciales.length : 0;
      
      if (procesosCount === 0) {
        console.log('[handleVerProcesos] No hay recibos especiales para esta prenda');
        showError('No hay procesos registrados para esta prenda');
        return;
      }
      
      console.log('[handleVerProcesos] Abriendo modal con', procesosCount, 'recibos especiales');
      
      // Mostrar modal de recibos especiales
      specialReceiptsRenderer.showReceipts(prenda);
      
      // Inicializar event listeners si no están inicializados
      specialReceiptsRenderer.initializeEventListeners();
      
    } catch (error) {
      console.error('[handleVerProcesos] Error:', error);
      showError('Error al mostrar procesos: ' + error.message);
    }
  };

  function renderPrendaTrackingTimeline(prenda) {
    trackingTimelineController.renderPrendaTrackingTimeline(prenda);
  }



  globalThis.handleEliminarProceso = async function(procesoId, areaName, event) {
    stopEventPropagation(event);
    showConfirmDeleteModal(procesoId, areaName);
  };

  function showConfirmDeleteModal(procesoId, areaName) {
    const processNameSpan = document.getElementById('deleteProcessName');
    
    if (processNameSpan) {
      processNameSpan.textContent = areaName;
    }
    
    orderState.setProcessToDelete({ id: procesoId, name: areaName });
    
    setupConfirmDeleteModalListeners();
    
    ModalUtils.openWithForce('confirmDeleteModal');
  }

  function setupConfirmDeleteModalListeners() {
    const binder = new ModalEventBinder();
    
    binder.bindCloseButtons({
      closeButtonId: 'closeConfirmDeleteModal',
      cancelButtonId: 'btnCancelDelete',
      overlaySelector: '.confirm-delete-overlay',
      callback: closeConfirmDeleteModal
    });
    
    binder.bindActionButton({
      buttonId: 'btnConfirmDelete',
      callback: executeDeleteProcess,
      loadingConfig: {
        contentId: 'deleteButtonContent',
        loadingId: 'deleteButtonLoading'
      }
    });
  }

  function closeConfirmDeleteModal() {
    ModalUtils.close('confirmDeleteModal', () => {
      orderState.clearProcessToDelete();
    });
  }

  // Ejecutar la eliminación del proceso
  async function executeDeleteProcess() {
    if (!orderState.getProcessToDelete()) return;
    
    const buttonMgr = new ButtonLoadingManager('btnConfirmDelete', {
      contentId: 'deleteButtonContent',
      loadingId: 'deleteButtonLoading'
    });

    const { id: procesoId, name: areaName } = orderState.getProcessToDelete();
    const prendaIdToUpdate = orderState.getCurrentPrenda()?.id;
    
    try {
      await buttonMgr.executeAsync(async () => {
        const result = await processService.deleteProcess(procesoId, {
          areaName,
          orderId: orderState.getOrderId(),
          prendaId: prendaIdToUpdate
        });

        if (!result.success) {
          throw result.error || new Error('Error desconocido');
        }

        orderState.clearProcessToDelete();
        
        // Re-renderizar el timeline después de eliminar exitosamente
        // Obtener la prenda actualizada (la eliminación ya actualizó los datos en orderState)
        const updatedPrenda = orderState.getCurrentPrenda();
        if (updatedPrenda) {
          console.log('[executeDeleteProcess] Re-renderizando timeline después de eliminación');
          renderPrendaTrackingTimeline(updatedPrenda);
        }
        
        // Actualizar la tabla de recibos-costura con el nuevo área
        console.log('[executeDeleteProcess] Actualizando tabla de recibos');
        await actualizarAreaEnTablaRecibos();
      });
    } catch (error) {
      console.error('[executeDeleteProcess] Error:', error);
    }
  }
    async function actualizarAreaEnTablaRecibos() {
    try {
      console.log('[actualizarAreaEnTablaRecibos] Verificando si estamos en recibos-costura');
      
      if (!globalThis.location.pathname.includes('/recibos-costura')) {
        console.log('[actualizarAreaEnTablaRecibos] No estamos en recibos-costura, omitiendo actualización');
        return;
      }

      const pedidoId = orderState.getOrderId();
      const prendaId = orderState.getCurrentPrenda()?.id || null;
      // Intentar obtener numeroRecibo desde múltiples fuentes
      const numeroRecibo = orderState.getConsecutivoCosturaData()?.consecutivo 
                          || orderState.getCurrentPrenda()?.numero_recibo_costura
                          || globalThis.currentOrderData?.prendas?.find(p => p.id === prendaId)?.numero_recibo_costura
                          || null;

      if (!pedidoId || !prendaId || !numeroRecibo) {
        console.log('[actualizarAreaEnTablaRecibos] Datos insuficientes para refrescar fila (esto es normal si no hay recibo de costura)', {
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

      const areaBadge = row.querySelector('td:nth-child(3) .badge');
      if (areaBadge && data.area) {
        areaBadge.textContent = data.area;
      }

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
  globalThis.handleEditarProceso = async function(procesoId, areaName, processData, event) {
    stopEventPropagation(event);
    
    try {
      await processWorkflowService.prepareForEdit(processData);
      
      const btnConfirmar = document.getElementById('btnConfirmAddProceso');
      if (btnConfirmar) {
        btnConfirmar.textContent = 'Actualizar Proceso';
        btnConfirmar.onclick = async function() {
          await handleActualizarProceso(procesoId);
        };
      }
      
      openAddProcesoModal();
      
    } catch (error) {
      console.error('[handleEditarProceso] Error:', error);
      showError('Error al preparar edición: ' + error.message);
    }
  };

  globalThis.handleActualizarProceso = async function(procesoId) {
    const buttonMgr = new ButtonLoadingManager('btnConfirmAddProceso', {
      contentId: 'addProcesoButtonContent',
      loadingId: 'addProcesoButtonLoading'
    });

    try {
      await buttonMgr.executeAsync(async () => {
        // Validar datos del formulario
        const validation = processWorkflowService.validateFormData();
        if (!validation.isValid) {
          throw new Error(validation.errors.join(' | '));
        }

        const processData = processWorkflowService.prepareProcessData();

        console.log('[handleActualizarProceso] Actualizando proceso:', {
          procesoId,
          area: processData.area
        });

        // Llamar a API de actualización
        const result = await OrderApiService.updateProceso(procesoId, processData);

        await processWorkflowService.reloadDataAfterSave(result);

        processWorkflowService.showFeedback({ action: 'actualizado' });

        formManager.clear();
        resetFormButton();
        ModalUtils.close('addProcesoModal');
        renderPrendaTrackingTimeline(orderState.getCurrentPrenda());
        
        await actualizarAreaEnTablaRecibos();
      });
    } catch (error) {
      console.error('[handleActualizarProceso] Error:', error);
      showError('Error al actualizar proceso: ' + error.message);
    }
  };

  function stopEventPropagation(event, preventDefault = false) {
    if (event) {
      if (preventDefault) event.preventDefault();
      event.stopPropagation();
    }
  }

  function resetFormButton() {
    const btnConfirmar = document.getElementById('btnConfirmAddProceso');
    if (btnConfirmar) {
      btnConfirmar.textContent = 'Agregar Proceso';
      btnConfirmar.onclick = handleAgregarProceso;
    }
    orderState.setEditingProcessId(null);
  }





  function showPrendasView() {
    console.log('[showPrendasView] Cerrando modal de seguimiento y volviendo a prendas...');
    
    closeTrackingModal();
    
    showPrendasSelector();
    
    console.log('[showPrendasView] Modal de seguimiento cerrado y selector de prendas mostrado');
  }

  // ============================================================
  // DATE & TIME UTILITIES: Use DateFormatterFacade (Phase 12 - Facade Pattern)
  // Semantic aliases to dateFacade for backward compatibility
  // ============================================================
  
  const formatDate = (dateString) => dateFacade.formatOrderDate(dateString);
  const formatDateTime = (dateString) => dateFacade.formatDeliveryDateTime(dateString);

  // Mostrar error
  function showError(message) {
    console.error('[showError] ' + message);
    // Usar el sistema global de toasts
    if (globalThis.showToast) {
      globalThis.showToast(message, 'error');
    }
  }

  async function handleAgregarProceso() {
    const buttonMgr = new ButtonLoadingManager('btnConfirmAddProceso', {
      contentId: 'addProcesoButtonContent',
      loadingId: 'addProcesoButtonLoading'
    });

    try {
      await buttonMgr.executeAsync(async () => {
        const result = await processWorkflowService.executeCompleteWorkflow({
          onValidationError: (errors) => {
            console.warn('[handleAgregarProceso] Errores de validación:', errors);
          },
          onComplete: async () => {
            formManager.clear();
            ModalUtils.close('addProcesoModal');
            renderPrendaTrackingTimeline(orderState.getCurrentPrenda());
    
            await actualizarAreaEnTablaRecibos();
          }
        });

        if (!result.success) {
          throw result.error || new Error('Error desconocido');
        }
      });
    } catch (error) {
      console.error('[handleAgregarProceso] Error:', error);
      
      if (error instanceof SyntaxError && error.message.includes('JSON')) {
        console.error('[handleAgregarProceso] Error de JSON');
        showError('Error del servidor: La respuesta no es válida. Posiblemente un error de permisos o validación.');
      }
    }
  }

  function showSuccess(message) {
    if (globalThis.showToast) {
      globalThis.showToast(message, 'success');
    }
  }

  // Manejar apertura de recibos especiales (BORDADO, ESTAMPADO, DTF, SUBLIMADO, REFLECTIVO)
  globalThis.handleAbrirReciboEspecial = function(recibosEspeciales, event) {
    stopEventPropagation(event);
    
    if (!recibosEspeciales || recibosEspeciales.length === 0) {
      console.log('[handleAbrirReciboEspecial] No hay recibos especiales');
      return;
    }

    if (recibosEspeciales.length === 1) {
      // Si hay solo uno, abrirlo directamente
      abrirReciboEspecial(recibosEspeciales[0]);
    } else {
      // Si hay múltiples, mostrar selector
      mostrarSelectorRecibosEspeciales(recibosEspeciales);
    }
  };

  function abrirReciboEspecial(recibo) {
    console.log('[abrirReciboEspecial] Abriendo recibo:', recibo);
    
    if (!recibo || !recibo.id) {
      showError('Error: Datos del recibo inválidos');
      return;
    }
    
    // Llamar a openReceiptModal para renderizar con formato costura
    openReceiptModal(recibo.id, recibo.tipo_recibo, recibo.consecutivo);
  }

  function mostrarSelectorRecibosEspeciales(recibosEspeciales) {
    console.log('[mostrarSelectorRecibosEspeciales] Mostrando selector con', recibosEspeciales.length, 'recibos');
    
    // Crear HTML do selector
    const selectorHtml = `
      <div class="recibos-especiales-selector-overlay">
        <div class="recibos-especiales-selector-modal">
          <div class="recibos-especiales-header">
            <h3>Seleccionar Recibo</h3>
            <button class="recibos-especiales-close" onclick="cerrarSelectorRecibosEspeciales()">&times;</button>
          </div>
          <div class="recibos-especiales-list">
            ${recibosEspeciales.map((recibo, idx) => `
              <button class="recibos-especiales-item" onclick="abrirReciboEspecial(${JSON.stringify(recibo).replace(/"/g, '&quot;')})">
                <span class="recibos-especiales-tipo">${recibo.tipo_recibo}</span>
                <span class="recibos-especiales-numero">#${recibo.consecutivo}</span>
                <span class="recibos-especiales-area">${recibo.area || '-'}</span>
              </button>
            `).join('')}
          </div>
        </div>
      </div>
    `;
    
    // Insertar en el DOM
    const container = document.body;
    const tempDiv = document.createElement('div');
    tempDiv.id = 'recibosEspecialesSelector';
    tempDiv.innerHTML = selectorHtml;
    container.appendChild(tempDiv);
  }

  /**
   * Abre modal con detalles del recibo especial
   * Renderiza como lo hace el visualizador de costura
   */
  globalThis.openReceiptModal = async function(receiptId, tipoRecibo, numeroRecibo) {
    try {
      // Obtener la orden actual del estado global
      const currentOrderData = globalThis.currentOrderData;
      if (!currentOrderData) {
        console.error('[openReceiptModal] No hay datos de orden cargados');
        showError('Error: Datos de orden no disponibles');
        return;
      }

      // Disparar evento para que abra el modal con formato costura
      // El evento load-order-detail es escuchado por order-detail-modal-manager.js
      const event = new CustomEvent('load-order-detail', {
        detail: currentOrderData
      });
      window.dispatchEvent(event);

      console.log('[openReceiptModal] Modal abierto con formato costura para recibo:', {
        tipoRecibo,
        numeroRecibo
      });

    } catch (error) {
      console.error('[openReceiptModal] Error:', error);
      showError('Error al abrir recibo: ' + error.message);
    }
  };

  globalThis.cerrarSelectorRecibosEspeciales = function() {
    const selector = document.getElementById('recibosEspecialesSelector');
    if (selector) {
      selector.remove();
    }
  };

  globalThis.abrirReciboEspecial = abrirReciboEspecial;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTrackingModalListeners, { once: true });
  } else {
    initTrackingModalListeners();
  }
