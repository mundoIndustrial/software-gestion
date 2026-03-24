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
  PrendaTrackingRenderer,
  BadgeRenderer,
  UpdateRenderer,
  createContainer,
  ProcessFormManager,
  ModalEventBinder,
  ButtonLoadingManager,
  DaysSelectorManager,
  AreasConfigService,
  ProcessWorkflowService,
  OrderLoaderService,
  DateFormatterFacade
} from './application/index.js';


const container = createContainer({
  orderState,
  dateFormatter: DateFormatter,
  
  svgIcons: SvgIcons,
  modalUtils: ModalUtils,
  dateUtils,
  queryUtils: QueryUtils,
  
  OrderApiService,
  
  showSuccess,
  showError,
  closeConfirmDeleteModal,
  
  ProcessService,

  ProcessFormManager,
  ModalEventBinder,
  ButtonLoadingManager,
  
  AreasConfigService,
  ProcessWorkflowService,
  
  PrendaTrackingRenderer,
  BadgeRenderer,
  UpdateRenderer
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
  closePrendasSelector: () => window.cerrarSelectorPrendas?.(),
  setupBackButton
});

// Inicializar listeners del modal
function initTrackingModalListeners() {
  const binder = new ModalEventBinder('orderTrackingModal');
  
  binder.bindCloseButtons({
    closeButtonId: null,
    cancelButtonId: null,
    overlaySelector: '#trackingModalOverlay',
    callback: closeTrackingModal
  });

  const closeBtn = document.querySelector('.tracking-modal-close');
  if (closeBtn) {
    closeBtn.addEventListener('click', closeTrackingModal);
  }

  setupAddProcesoModalListeners();

  setupConfirmDeleteModalListeners();

  setupBackButton();

  setupDaysSelector();

}

  function setupDaysSelector() {
    const daysSelector = new DaysSelectorManager('trackingDaysSelector', {
      orderState,
      onSave: saveDiaEntregaSelection
    });
    daysSelector.initialize();
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
    closeAddProcesoBtn.addEventListener('click', closeAddProcesoModal);
  }

  const btnCancelAddProceso = document.getElementById('btnCancelAddProceso');
  if (btnCancelAddProceso) {
    btnCancelAddProceso.addEventListener('click', closeAddProcesoModal);
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

  // Abrir selector de prendas (overlay)
  window.openOrderTracking = async function(orderId, mostrarSelector = true) {
    try {
      console.log('[openOrderTracking] Abriendo selector de prendas para orden:', orderId, 'mostrarSelector:', mostrarSelector);
      
      await orderLoaderService.loadCompleteOrder(orderId);
      
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



  // Actualizar información del pedido en el modal y selector
  function updateOrderInfo(orderData) {
    updateRenderer.updateOrderInfo(orderData, orderState, DateFormatter);
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
  window.cerrarSelectorPrendas = function() {
    ModalUtils.close('trackingPrendasSelectorOverlay');
  };



  // Permite editar el área actual incluso si no existe proceso (creación rápida con prefill)
  window.handleCrearProcesoDesdeArea = function(areaName, event, encargadoPrefill = '') {
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





  window.showPrendaTrackingFromTable = async function(index) {
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

  window.showPrendaTracking = async function(prenda) {
    await trackingTimelineController.showPrendaTracking(prenda);
  };

  function renderPrendaTrackingTimeline(prenda) {
    trackingTimelineController.renderPrendaTrackingTimeline(prenda);
  }



  window.handleEliminarProceso = async function(procesoId, areaName, event) {
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
    
    try {
      await buttonMgr.executeAsync(async () => {
        const result = await processService.deleteProcess(procesoId, {
          areaName,
          orderId: orderState.getOrderId(),
          prendaId: orderState.getCurrentPrenda()?.id
        });

        if (!result.success) {
          throw result.error || new Error('Error desconocido');
        }

        orderState.clearProcessToDelete();
      });
    } catch (error) {
      console.error('[executeDeleteProcess] Error:', error);
    }
  }
    async function actualizarAreaEnTablaRecibos() {
    try {
      console.log('[actualizarAreaEnTablaRecibos] Verificando si estamos en recibos-costura');
      
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
  window.handleEditarProceso = async function(procesoId, areaName, processData, event) {
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

  window.handleActualizarProceso = async function(procesoId) {
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
    if (window.showToast) {
      window.showToast(message, 'error');
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
    if (window.showToast) {
      window.showToast(message, 'success');
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTrackingModalListeners);
  } else {
    initTrackingModalListeners();
  }
