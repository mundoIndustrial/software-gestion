# FASE 1: Ejemplos Prácticos de Reorganización
## Tracking Modal Handler - Refactor Conservador

---

## 📋 ¿QUÉ VAMOS A HACER?

Solo **reorganizar las funciones** dentro del MISMO archivo:
- ✅ Agrupar con comentarios descriptivos
- ✅ Documentar con JSDoc
- ✅ Mejorar nombres de secciones
- ✅ CERO cambios en funcionalidad
- ✅ CERO cambios en HTML
- ✅ CERO cambios en API

**Resultado**: Código EXACTAMENTE equal, pero mucho más legible

---

## 🔧 ESTRUCTURA FINAL PROPUESTA

```
SECCIÓN 1: DEPENDENCIES & LOGGING       (Líneas 1-20)
  └─ Logger centralizado
  └─ Comentarios de dependencias

SECCIÓN 2: STATE MANAGEMENT             (Líneas 21-50)
  └─ Documentación de variables globales
  └─ Funciones creadas en siguiente fase

SECCIÓN 3: INITIALIZATION                (Líneas 51-100)
  └─ initTrackingModalListeners()
  └─ setupBackButton()
  └─ Listeners de eventos

SECCIÓN 4: DATA LOADING                  (Líneas 101-250)
  └─ window.openOrderTracking()
  └─ loadOrderBasicData()
  └─ loadPrendasWithTracking()
  └─ Todas operaciones async de datos

SECCIÓN 5: RENDERING - PRENDAS           (Líneas 251-450)
  └─ renderPrendas()
  └─ createPrendasTable()
  └─ showPrendasSelector()
  └─ createPrendaCard()
  └─ Badge renderers

SECCIÓN 6: RENDERING - TRACKING          (Líneas 451-650)
  └─ window.showPrendaTracking()
  └─ renderPrendaTrackingTimeline()
  └─ renderSeguimientosPorArea()
  └─ renderNoSeguimiento()
  └─ createAreaCard()
  └─ createSeguimientoCard()

SECCIÓN 7: PROCESS OPERATIONS            (Líneas 651-850)
  └─ window.handleAgregarProceso()
  └─ window.handleEditarProceso()
  └─ window.handleActualizarProceso()
  └─ window.handleEliminarProceso()
  └─ executeDeleteProcess()
  └─ showConfirmDeleteModal()
  └─ Form helpers

SECCIÓN 8: MODAL MANAGEMENT              (Líneas 851-950)
  └─ closeTrackingModal()
  └─ openAddProcesoModal()
  └─ closeAddProcesoModal()
  └─ setupModalListeners()
  └─ Oyentes de overlay

SECCIÓN 9: TABLE & AREA UPDATES          (Líneas 951-1000)
  └─ actualizarAreaEnTablaRecibos()
  └─ findReciboCosturaRow()

SECCIÓN 10: UTILITIES                    (Líneas 1001-1050)
  └─ formatDate()
  └─ showError()
  └─ showSuccess()
  └─ updateOrderInfo()
  └─ updateEstimatedDeliveryDate()

SECCIÓN 11: INITIALIZATION & DOM READY   (Líneas 1051-1087)
  └─ DOMContentLoaded listener
  └─ Ejecución final
```

---

## 💡 EJEMPLO: ANTES vs DESPUÉS

### ANTES (Líneas 1-95 del actual)
```javascript
(function() {
  'use strict';
  
  // Logger centralizado
  const log = (fn, msg, data) => console.log(`[${fn}] ${msg}`, data || '');
  const err = (fn, msg, e) => console.error(`[${fn}] ${msg}`, e);

  function initTrackingModalListeners() {
    DOMManipulator.addEventListeners([
      { elementId: 'trackingModalOverlay', eventName: 'click', handler: closeTrackingModal },
      { elementId: 'btnOpenAddProcesoModal', eventName: 'click', handler: openAddProcesoModal },
      // ... más listeners
    ]);

    const btnConfirmAddProceso = DOMManipulator.getElementById('btnConfirmAddProceso', false);
    if (btnConfirmAddProceso) {
      // ... clonación de botón
    }
    // ... mezclado con otra lógica
  }

  function closeTrackingModal() { ... }
  function openAddProcesoModal() { ... }
  // ... todo sin orden ni documentación
})();
```

### DESPUÉS (ORGANIZED)
```javascript
/**
 * Tracking Modal Handler - Seguimiento por Prenda
 * Maneja la integración del modal de seguimiento con la vista de órdenes.
 * 
 * DEPENDENCIAS REQUERIDAS:
 * - ApiService: Operaciones backend (órdenes, prendas, procesos)
 * - DOMManipulator: Manipulación segura del DOM
 * - ModalHelper: Gestión de modales
 * - DateFormatter: Formateo de fechas
 * - StatusFormatter: Formateo de estados
 * - ValidationService: Validación de datos
 * - NotificationService: Notificaciones (success, error)
 * - LoadingIndicator: Indicador de carga
 * - AreaResolver: Resolución de áreas de procesos
 * - TrackingHelper: Helpers específicos de seguimiento
 * - IconSvgProvider: Iconos SVG
 * 
 * ESTADO GLOBAL (window.*):
 * - currentOrderData: {id, numero_pedido, cliente, estado, etc.}
 * - currentPrendaData: {id, nombre_prenda, cantidad, procesos[], etc.}
 * - currentConsecutivoCosturaData: {consecutivo, encargado, fecha_*, etc.}
 * - prendasData: Array de prendas cargadas
 * - editingProcessId: ID del proceso en edición (null si nuevo)
 * - processToDelete: {id, name} del proceso marcado para eliminar
 */

(function() {
  'use strict';
  
  // ============================================================================
  // SECCIÓN 1: LOGGING CENTRALIZADO
  // ============================================================================
  /**
   * Log de información
   * @param {string} fnName - Nombre de la función que llama
   * @param {string} message - Mensaje a loguear
   * @param {*} data - Datos adicionales opcionales
   */
  const log = (fnName, message, data) => {
    console.log(`[${fnName}] ${message}`, data || '');
  };

  /**
   * Log de error
   * @param {string} fnName - Nombre de la función que llama
   * @param {string} message - Mensaje de error
   * @param {Error} error - Objeto error
   */
  const err = (fnName, message, error) => {
    console.error(`[${fnName}] ${message}`, error);
  };

  // ============================================================================
  // SECCIÓN 2: INICIALIZACIÓN Y LISTENERS PRINCIPALES
  // ============================================================================
  /**
   * Inicializa todos los listeners del modal de tracking
   * Ejecutado al cargar el DOM
   * Responsabilidades:
   * - Configurar listeners de modales principales
   * - Configurar cierre por overlay
   * - Preparar botones reutilizables
   */
  function initTrackingModalListeners() {
    log('initTrackingModalListeners', 'Iniciando listeners');
    
    // Listeners del overlay y botones principales
    DOMManipulator.addEventListeners([
      {
        elementId: 'trackingModalOverlay',
        eventName: 'click',
        handler: closeTrackingModal
      },
      {
        elementId: 'btnOpenAddProcesoModal',
        eventName: 'click',
        handler: openAddProcesoModal
      },
      {
        elementId: 'closeAddProcesoModal',
        eventName: 'click',
        handler: closeAddProcesoModal
      },
      {
        elementId: 'btnCancelAddProceso',
        eventName: 'click',
        handler: closeAddProcesoModal
      },
      {
        elementId: 'addProcesoOverlay',
        eventName: 'click',
        handler: closeAddProcesoModal
      }
    ]);

    // Clonar botón de confirmación para evitar múltiples listeners
    const btnConfirmAddProceso = DOMManipulator.getElementById('btnConfirmAddProceso', false);
    if (btnConfirmAddProceso) {
      const nuevoBtnConfirm = btnConfirmAddProceso.cloneNode(true);
      btnConfirmAddProceso.parentNode.replaceChild(nuevoBtnConfirm, btnConfirmAddProceso);
      nuevoBtnConfirm.onclick = handleAgregarProceso;
    }

    // Cerrar modal por botón X
    const closeBtn = DOMManipulator.querySelector('.tracking-modal-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', closeTrackingModal);
    }

    // Cerrar modal por tecla ESC
    ModalHelper.setupEscapeListener('orderTrackingModal', closeTrackingModal);
    
    log('initTrackingModalListeners', 'Listeners configurados');
  }

  // ============================================================================
  // SECCIÓN 3: GESTIÓN DE MODALES
  // ============================================================================
  /**
   * Cierra el modal principal de tracking de órdenes
   */
  function closeTrackingModal() {
    ModalHelper.close('orderTrackingModal');
    log('closeTrackingModal', 'Modal cerrado');
  }

  /**
   * Abre el modal para agregar nuevo proceso
   * Limpia el formulario si no estamos en modo edición
   */
  function openAddProcesoModal() {
    log('openAddProcesoModal', 'Abriendo modal de agregar proceso');
    
    if (!window.editingProcessId) {
      if (typeof resetFormButton === 'function') {
        resetFormButton();
      }
      if (typeof limpiarFormularioProceso === 'function') {
        limpiarFormularioProceso();
      }
    }

    ModalHelper.open('addProcesoModal', 10000000);
    setupAddProcesoModalListeners();
  }

  /**
   * Configura los listeners necesarios para el modal de agregar proceso
   */
  function setupAddProcesoModalListeners() {
    ModalHelper.setupOverlayClose('addProcesoOverlay', 'addProcesoModal');
    DOMManipulator.addEventListener('closeAddProcesoModal', 'click', closeAddProcesoModal);
    DOMManipulator.addEventListener('btnCancelAddProceso', 'click', closeAddProcesoModal);
  }

  /**
   * Cierra el modal de agregar/editar proceso
   */
  function closeAddProcesoModal() {
    ModalHelper.close('addProcesoModal');
  }

  /**
   * Configura el botón "Volver a prendas"
   */
  function setupBackButton() {
    const backBtn = document.getElementById('backToPrendasBtn');
    if (backBtn) {
      backBtn.onclick = showPrendasView;
      log('setupBackButton', 'Botón volver configurado');
    } else {
      err('setupBackButton', 'Botón no encontrado', null);
    }
  }

  // ============================================================================
  // SECCIÓN 4: CARGA DE DATOS
  // ============================================================================
  /**
   * API pública: Abre el modal de tracking para una orden
   * @param {number|string} orderId - ID de la orden a visualizar
   * @param {boolean} mostrarSelector - Si mostrar selector de prendas (default: true)
   * @throws {Error} Si no se pueden cargar los datos
   */
  window.openOrderTracking = async function(orderId, mostrarSelector = true) {
    try {
      log('openOrderTracking', 'Abriendo tracking para orden', { orderId });
      await loadOrderBasicData(orderId);
      await loadPrendasWithTracking(orderId);
      if (mostrarSelector) {
        showPrendasSelector();
      }
    } catch (error) {
      err('openOrderTracking', 'Error al cargar datos', error);
      showError('Error al cargar datos de seguimiento');
    }
  };

  /**
   * Carga datos básicos de la orden desde el backend
   * Guarda en: window.currentOrderData
   * @param {number|string} orderId - ID de la orden
   * @throws {Error} Si falla la llamada a API
   */
  async function loadOrderBasicData(orderId) {
    try {
      log('loadOrderBasicData', 'Cargando datos básicos de orden', { orderId });
      const result = await ApiService.ordenes.getDatos(orderId);
      const data = result.data || result;
      window.currentOrderData = data;
      updateOrderInfo(data);
      log('loadOrderBasicData', 'Datos cargados correctamente');
    } catch (error) {
      err('loadOrderBasicData', 'Error al obtener datos', error);
      throw error;
    }
  }

  /**
   * Carga las prendas con su información de seguimiento
   * Guarda en: window.prendasData
   * @param {number|string} orderId - ID de la orden
   * @throws {Error} Si falla la llamada a API
   */
  async function loadPrendasWithTracking(orderId) {
    try {
      log('loadPrendasWithTracking', 'Cargando prendas con tracking', { orderId });
      const data = await ApiService.prendas.getSeguimiento(orderId);
      renderPrendas(data.prendas || []);
      log('loadPrendasWithTracking', 'Prendas cargadas correctamente');
    } catch (error) {
      err('loadPrendasWithTracking', 'Error al obtener prendas', error);
      throw error;
    }
  }

  // ============================================================================
  // SECCIÓN 5: RENDERIZADO DE INFORMACIÓN DE ORDEN
  // ============================================================================
  /**
   * Actualiza la información de la orden en la UI
   * Actualiza: número, cliente, estado, fechas, total de días
   * @param {Object} orderData - Datos de la orden del backend
   */
  function updateOrderInfo(orderData) {
    try {
      log('updateOrderInfo', 'Actualizando información de orden');
      
      const statusDisplay = StatusFormatter.getDisplayStatus(orderData);
      const dateStart = DateFormatter.getFirstValid(
        orderData.fecha_creacion,
        orderData.fecha_de_creacion_de_orden,
        orderData.created_at
      );
      const dateEstimated = DateFormatter.format(orderData.fecha_estimada_entrega);

      // Actualizar elementos de tracking principal
      DOMManipulator.updateMultiple({
        'trackingOrderNumber': orderData.numero_pedido,
        'trackingOrderClient': orderData.cliente,
        'trackingOrderStatus': statusDisplay,
        'trackingOrderDate': dateStart,
        'trackingEstimatedDate': dateEstimated,
        'trackingTotalDays': orderData.total_dias || '0'
      });

      // Actualizar selector de prendas
      DOMManipulator.updateMultiple({
        'selectorOrderNumber': orderData.numero_pedido,
        'selectorOrderClient': orderData.cliente,
        'selectorOrderStatus': statusDisplay,
        'selectorOrderStartDate': dateStart,
        'selectorOrderEstimatedDate': dateEstimated
      });

      // Actualizar recibo de costura si aplica
      const reciboCostura = TrackingHelper.resolveReciboCostura(
        window.currentPrendaData,
        orderData
      );
      DOMManipulator.setText('trackingOrderRecibo', reciboCostura);

      // Aplicar estilos a fecha estimada
      updateEstimatedDateDisplay('selectorOrderEstimatedDate', dateEstimated);
      
      log('updateOrderInfo', 'Información de orden actualizada');
    } catch (error) {
      err('updateOrderInfo', 'Error al actualizar información', error);
    }
  }

  /**
   * Actualiza el display de la fecha estimada (color y peso según validez)
   * Función reutilizable para evitar duplicación
   * @param {string} elementId - ID del elemento a actualizar
   * @param {string} fechaFormateada - Fecha ya formateada
   */
  function updateEstimatedDateDisplay(elementId, fechaFormateada) {
    DOMManipulator.setText(elementId, fechaFormateada);
    
    const isValid = fechaFormateada !== '-';
    DOMManipulator.setStyles(elementId, {
      'color': isValid ? '#1f2937' : '#9ca3af',
      'font-weight': isValid ? '600' : '400'
    });
  }

  /**
   * Actualiza la fecha estimada en el selector de prendas
   * Llamado cada vez que se renderizan las prendas
   */
  function updateEstimatedDeliveryDate() {
    const fechaEstimadaElement = DOMManipulator.getElementById('selectorOrderEstimatedDate');
    if (!fechaEstimadaElement || !window.currentOrderData) return;

    const fechaEstimada = window.currentOrderData.fecha_estimada_de_entrega;
    const fechaFormateada = DateFormatter.format(fechaEstimada);

    updateEstimatedDateDisplay('selectorOrderEstimatedDate', fechaFormateada);
  }

  // ============================================================================
  // SECCIÓN 6: RENDERIZADO DE TABLA DE PRENDAS
  // ============================================================================
  /**
   * Renderiza la lista de prendas en el selector
   * @param {Array} prendas - Array de prendas con información de seguimiento
   */
  function renderPrendas(prendas) {
    const container = document.getElementById('trackingPrendasSelectorContainer');
    if (!container) return;
    
    if (prendas.length === 0) {
      container.innerHTML = '<div class="tracking-no-prendas"><p>No hay prendas registradas</p></div>';
    } else {
      container.innerHTML = createPrendasTable(prendas);
    }
    
    updateEstimatedDeliveryDate();
  }

  /**
   * Crea la tabla HTML de prendas
   * Incluye información sobre cantidad, procesos, área y estado
   * @param {Array} prendas - Array de prendas
   * @returns {string} HTML de la tabla
   */
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
    
    // Guardar datos para acceso posterior
    window.prendasData = prendas;
    
    // Enriquecer prendas con información adicional
    const prendasEnriquecidas = TrackingHelper.enrichPrendas(prendas, window.currentOrderData);
    
    // Renderizar cada fila
    prendasEnriquecidas.forEach((prenda, index) => {
      const displayInfo = TrackingHelper.getPrendaDisplayInfo(prenda);
      const procesosInfo = TrackingHelper.getProcesosInfo(prenda);
      const area = AreaResolver.resolve(
        prenda,
        window.currentOrderData,
        window.location.pathname.includes('/recibos-costura')
      );
      const estadoPedido = window.currentOrderData?.estado || 'Sin estado';
      const estadoFormateado = StatusFormatter.format(estadoPedido);
      const estadoCSSClass = StatusFormatter.getCSSClass(estadoPedido);
      
      const botonDisabled = TrackingHelper.shouldDisableTrackingButton(prenda) ? 'disabled' : '';
      const botonTitle = TrackingHelper.getTrackingButtonTitle(prenda);
      const botonClass = TrackingHelper.shouldDisableTrackingButton(prenda)
        ? 'btn-ver-seguimiento disabled'
        : 'btn-ver-seguimiento';
      
      tableHtml += `
        <tr class="prendas-table-row" data-prenda-index="${index}">
          <td class="prendas-table-cell prendas-name-cell">
            <div class="prendas-name">${displayInfo.nombre}</div>
            <span class="${displayInfo.badgeClass}">${displayInfo.badge}</span>
          </td>
          <td class="prendas-table-cell">${prenda.cantidad || 0}</td>
          <td class="prendas-table-cell procesos-cell">
            <div class="procesos-info">${procesosInfo}</div>
          </td>
          <td class="prendas-table-cell">${area}</td>
          <td class="prendas-table-cell">
            <span class="estado-badge ${estadoCSSClass}">${estadoFormateado}</span>
          </td>
          <td class="prendas-table-cell acciones-cell">
            <button class="${botonClass}" ${botonDisabled} onclick="showPrendaTrackingFromTable(${index})" title="${botonTitle}">
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

  /**
   * Muestra el overlay del selector de prendas
   */
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

  /**
   * API pública: Cierra el selector de prendas
   */
  window.cerrarSelectorPrendas = function() {
    const overlay = document.getElementById('trackingPrendasSelectorOverlay');
    if (overlay) {
      overlay.classList.remove('show');
      overlay.style.display = 'none';
    }
  };

  // ============================================================================
  // SECCIÓN 7: RENDERIZADO DEL SEGUIMIENTO DE PRENDA
  // ============================================================================
  /**
   * API pública: Muestra el seguimiento detallado de una prenda
   * Accede a: window.prendasData[index]
   * @param {number} index - Índice de la prenda en window.prendasData
   */
  window.showPrendaTrackingFromTable = async function(index) {
    try {
      log('showPrendaTrackingFromTable', 'Mostrando seguimiento de prenda', { index });
      
      const prenda = window.prendasData[index];
      if (!prenda) {
        err('showPrendaTrackingFromTable', 'Prenda no encontrada', { index });
        return;
      }
      
      await showPrendaTracking(prenda);
    } catch (error) {
      err('showPrendaTrackingFromTable', 'Error', error);
      showError('Error al cargar seguimiento de la prenda');
    }
  };

  /**
   * Muestra el modal de seguimiento para una prenda específica
   * @param {Object} prenda - Datos de la prenda
   */
  window.showPrendaTracking = async function(prenda) {
    try {
      log('showPrendaTracking', 'Mostrando seguimiento para prenda', { prendaId: prenda.id });

      // Enriquecer datos de prenda
      prenda = TrackingHelper.enrichPrenda(prenda, window.prendasData);
      window.currentPrendaData = prenda;
      
      // Cerrar selector si está abierto
      if (DOMManipulator.exists('trackingPrendasSelectorOverlay')) {
        cerrarSelectorPrendas();
      }
      
      // Abrir modal de tracking
      ModalHelper.applyDialogStyles('orderTrackingModal', 9999999);
      ModalHelper.open('orderTrackingModal', 9999999);
      setupBackButton();
      
      // Mostrar/ocultar secciones
      DOMManipulator.setVisible('trackingPrendasContainer', false);
      DOMManipulator.setVisible('trackingTimelineSection', true, 'block');
      
      // Actualizar información de prenda
      const displayInfo = TrackingHelper.getPrendaDisplayInfo(prenda);
      DOMManipulator.setText('trackingPrendaName', displayInfo.nombre);
      
      // Resolver área y recibo
      const esRecibosCostura = window.location.pathname.includes('/recibos-costura');
      const areaActual = AreaResolver.resolve(prenda, window.currentOrderData, esRecibosCostura);
      const reciboActivo = TrackingHelper.getActiveRecibo(prenda);
      const numeroRecibo = reciboActivo
        ? TrackingHelper.formatReciboDisplay(reciboActivo)
        : 'Sin recibo';
      
      const reciboHeader = AreaResolver.getReciboHeader(numeroRecibo, areaActual);
      DOMManipulator.setText('trackingPrendaReciboHeader', reciboHeader);
      DOMManipulator.setText('trackingPrendaRecibo', numeroRecibo);
      
      // Renderizar los seguimientos en timeline
      renderPrendaTrackingTimeline(prenda);
      log('showPrendaTracking', 'Seguimiento mostrado exitosamente');
      
    } catch (error) {
      err('showPrendaTracking', 'Error', error);
      showError('Error al cargar seguimiento de la prenda');
    }
  };

  /**
   * Renderiza el timeline de seguimiento de una prenda
   * @param {Object} prenda - Datos de la prenda
   */
  function renderPrendaTrackingTimeline(prenda) {
    const container = document.getElementById('trackingTimelineContainer');
    if (!container) return;

    container.innerHTML = '';
    renderSeguimientosPorArea(prenda, container);
    
    if (!prenda.seguimientos_por_area || Object.keys(prenda.seguimientos_por_area).length === 0) {
      renderNoSeguimiento(container);
    }
  }

  /**
   * Renderiza los seguimientos por área/proceso
   * @param {Object} prenda - Datos de la prenda
   * @param {HTMLElement} container - Contenedor donde renderizar
   */
  function renderSeguimientosPorArea(prenda, container) {
    const seguimientosPorArea = prenda.seguimientos_por_area || {};
    if (Object.keys(seguimientosPorArea).length > 0) {
      // Crear header con título y botón
      const headerContainer = document.createElement('div');
      headerContainer.style.display = 'flex';
      headerContainer.style.justifyContent = 'space-between';
      headerContainer.style.alignItems = 'center';
      headerContainer.style.marginTop = '0px';
      headerContainer.style.marginBottom = '16px';
      
      const seguimientosTitle = document.createElement('h4');
      seguimientosTitle.textContent = 'Seguimiento por Áreas/Procesos';
      seguimientosTitle.style.margin = '0';
      seguimientosTitle.style.fontSize = '20px';
      seguimientosTitle.style.fontWeight = '700';
      seguimientosTitle.style.color = '#1f2937';
      
      const originalBtn = document.getElementById('btnOpenAddProcesoModal');

      headerContainer.appendChild(seguimientosTitle);
      if (originalBtn) {
        originalBtn.style.display = '';
        headerContainer.appendChild(originalBtn);
      }
      container.appendChild(headerContainer);

      // Renderizar tarjeta por cada área
      Object.entries(seguimientosPorArea).forEach(([area, data]) => {
        const areaCard = createAreaCard(area, data);
        container.appendChild(areaCard);
      });
    }
  }

  /**
   * Renderiza mensaje cuando no hay seguimientos
   * @param {HTMLElement} container - Contenedor donde renderizar
   */
  function renderNoSeguimiento(container) {
    const noSeguimiento = document.createElement('div');
    noSeguimiento.className = 'tracking-no-seguimiento';
    noSeguimiento.innerHTML = '<p>No hay seguimientos registrados para esta prenda</p>';
    container.appendChild(noSeguimiento);

    // Intentar renderizar datos del último proceso si existen
    const prenda = window.currentPrendaData || {};
    const esRecibosCostura = window.location.pathname.includes('/recibos-costura');

    const procesoIdFallback = window.currentConsecutivoCosturaData?.proceso_id || null;
    const tieneProcesoReal = Boolean(prenda?.ultimo_proceso_id || procesoIdFallback);

    const areaActual = prenda?.ultimo_proceso_area
      || (prenda?.area && String(prenda.area).trim() !== '' ? prenda.area : null)
      || (!esRecibosCostura && window.currentOrderData?.area && String(window.currentOrderData.area).trim() !== ''
        ? window.currentOrderData.area
        : null)
      || null;

    const encargadoActual = prenda?.ultimo_proceso_encargado
      || window.currentConsecutivoCosturaData?.encargado
      || null;

    if (tieneProcesoReal && areaActual && typeof createAreaCard === 'function') {
      const estadoUltimo = prenda?.ultimo_proceso_estado || 'Pendiente';
      const estaActivo = estadoUltimo !== 'Completado';

      const fechaInicioFallback = window.currentConsecutivoCosturaData?.fecha_inicio || null;
      const fechaFinFallback = window.currentConsecutivoCosturaData?.fecha_fin || null;

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
      });
      container.appendChild(card);
    }
  }

  /**
   * Crea una tarjeta de proceso/área
   * @param {string} area - Nombre del área
   * @param {Object} data - Datos del proceso
   * @returns {HTMLElement} Elemento de tarjeta
   */
  function createAreaCard(area, data) {
    const card = document.createElement('div');
    card.className = `tracking-area-card ${data.esta_activo ? 'pending' : 'completed'}`;
    
    const iconSvg = IconSvgProvider.get(data.icono || 'description');
    
    card.innerHTML = `
      <div class="tracking-area-name">
        ${iconSvg}
        ${area}
        <div class="tracking-action-buttons">
          ${(data.id || data.can_edit) ? `
          <button class="tracking-edit-btn" onclick="${data.id ? `handleEditarProceso(${data.id}, '${area}', ${JSON.stringify(data).replace(/"/g, '&quot;')}, event)` : `handleCrearProcesoDesdeArea('${area}', event, '${String(data.encargado || '').replace(/'/g, "\\'")}')`}" title="Editar proceso">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
          </button>
          ${data.id ? `
          <button class="tracking-delete-btn" onclick="handleEliminarProceso(${data.id}, '${area}', event)" title="Eliminar proceso">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14zM10 11v6M14 11v6"/>
            </svg>
          </button>
          ` : ''}
          ` : ''}
        </div>
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
      </div>
    `;
    
    return card;
  }

  // ============================================================================
  // SECCIÓN 8: OPERACIONES DE PROCESOS
  // ============================================================================
  /**
   * API pública: Maneja la adición de un nuevo proceso
   */
  async function handleAgregarProceso() {
    try {
      LoadingIndicator.show('btnConfirmAddProceso');
      const area = document.getElementById('procesoArea').value;
      const inputEncargado = document.getElementById('procesoEncargado');
      const selectEncargado = document.getElementById('procesoEncargadoSelect');
      const encargado = (inputEncargado?.style.display !== 'none'
        ? inputEncargado?.value
        : selectEncargado?.value || '').toUpperCase();

      const validation = ValidationService.proceso.validar(
        area,
        encargado,
        window.currentPrendaData,
        window.currentOrderData
      );
      
      if (!validation.isValid) {
        showError(validation.error);
        LoadingIndicator.hide('btnConfirmAddProceso');
        return;
      }

      const result = await ApiService.proceso.guardar({
        pedido_produccion_id: window.currentOrderData.numero_pedido,
        prenda_id: window.currentPrendaData.id,
        area,
        encargado,
        estado: 'Pendiente'
      });

      limpiarFormularioProceso();
      const modal = document.getElementById('addProcesoModal');
      if (modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
      }

      if (result.data?.prenda) {
        window.currentPrendaData = result.data.prenda;
        renderPrendaTrackingTimeline(window.currentPrendaData);
      } else {
        await loadPrendasWithTracking(window.currentOrderData.id);
        if (window.prendasData?.length > 0) {
          const prendaActualizada = window.prendasData.find(p => p.id == window.currentPrendaData.id);
          if (prendaActualizada) {
            window.currentPrendaData = prendaActualizada;
            renderPrendaTrackingTimeline(window.currentPrendaData);
          }
        }
      }

      showSuccess(result.action === 'actualizado' ? 'Actualizado correctamente' : 'Agregado correctamente');
      await actualizarAreaEnTablaRecibos();
    } catch (error) {
      err('handleAgregarProceso', 'Error', error);
      showError('Error: ' + error.message);
    } finally {
      LoadingIndicator.hide('btnConfirmAddProceso');
    }
  }

  /**
   * API pública: Inicia edición de un proceso
   * @param {number} procesoId - ID del proceso
   * @param {string} areaName - Nombre del área
   * @param {Object} processData - Datos del proceso
   * @param {Event} event - Evento click
   */
  window.handleEditarProceso = function(procesoId, areaName, processData, event) {
    if (event) event.stopPropagation();
    log('handleEditarProceso', 'Editando proceso', { procesoId, areaName });
    
    const elems = {
      area: document.getElementById('procesoArea'),
      estado: document.getElementById('procesoEstado'),
      fechaInicio: document.getElementById('procesoFechaInicio'),
      encargado: document.getElementById('procesoEncargado'),
      observaciones: document.getElementById('procesoObservaciones')
    };
    
    if (elems.area) elems.area.value = processData.area || areaName;
    if (elems.estado) elems.estado.value = processData.estado || 'Pendiente';
    if (elems.fechaInicio && processData.fecha_inicio) {
      const d = new Date(processData.fecha_inicio);
      elems.fechaInicio.value = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    }
    if (elems.encargado) elems.encargado.value = processData.encargado || '';
    if (elems.observaciones) elems.observaciones.value = processData.observaciones || '';
    
    window.editingProcessId = procesoId;
    const btn = document.getElementById('btnConfirmAddProceso');
    if (btn) {
      btn.textContent = 'Actualizar Proceso';
      btn.onclick = () => handleActualizarProceso(procesoId);
    }
    openAddProcesoModal();
  };

  /**
   * API pública: Actualiza un proceso existente
   * @param {number} procesoId - ID del proceso a actualizar
   */
  window.handleActualizarProceso = async function(procesoId) {
    try {
      const procesoAreaEl = document.getElementById('procesoArea');
      const procesoEstadoEl = document.getElementById('procesoEstado');
      const procesoFechaInicioEl = document.getElementById('procesoFechaInicio');
      const procesoEncargadoEl = document.getElementById('procesoEncargado');
      const procesoObservacionesEl = document.getElementById('procesoObservaciones');

      if (!procesoAreaEl || !procesoEncargadoEl) {
        throw new Error('No se encontraron los campos del formulario para actualizar el proceso. Por favor recarga la página.');
      }

      const area = procesoAreaEl.value;
      const estado = procesoEstadoEl ? procesoEstadoEl.value : 'Pendiente';
      const fechaInicio = procesoFechaInicioEl ? procesoFechaInicioEl.value : '';
      const encargado = procesoEncargadoEl.value;
      const observaciones = procesoObservacionesEl ? procesoObservacionesEl.value : '';

      log('handleActualizarProceso', 'Actualizando proceso', { procesoId, area, estado });

      const result = await ApiService.proceso.actualizar(procesoId, {
        area,
        estado,
        fecha_inicio: fechaInicio || null,
        encargado,
        observaciones
      });

      limpiarFormularioProceso();
      resetFormButton();

      try {
        closeAddProcesoModal();
      } catch (e) {
        console.warn('[handleActualizarProceso] No se pudo cerrar addProcesoModal:', e);
      }

      const orderId = window.currentOrderData?.id;
      if (orderId) {
        await loadPrendasWithTracking(orderId);
      }
      
      if (window.currentPrendaData && window.currentPrendaData.id && Array.isArray(window.prendasData)) {
        const prendaActualizada = window.prendasData.find(p => String(p.id) === String(window.currentPrendaData.id));
        if (prendaActualizada) {
          window.currentPrendaData = prendaActualizada;
        }
      }

      if (window.currentPrendaData) {
        renderPrendaTrackingTimeline(window.currentPrendaData);
      }

      showSuccess('Proceso actualizado correctamente');
      await actualizarAreaEnTablaRecibos();
    } catch (error) {
      err('handleActualizarProceso', 'Error', error);
      showError('Error al actualizar proceso: ' + error.message);
    }
  };

  /**
   * API pública: Elimina un proceso (con confirmación)
   * @param {number} procesoId - ID del proceso
   * @param {string} areaName - Nombre del área
   * @param {Event} event - Evento click
   */
  window.handleEliminarProceso = async function(procesoId, areaName, event) {
    if (event) {
      event.stopPropagation();
    }
    
    showConfirmDeleteModal(procesoId, areaName);
  };

  /**
   * Muestra el modal de confirmación para eliminar un proceso
   * @param {number} procesoId - ID del proceso
   * @param {string} areaName - Nombre del área
   */
  function showConfirmDeleteModal(procesoId, areaName) {
    log('showConfirmDeleteModal', 'Mostrando confirmación de eliminación', { procesoId, areaName });
    
    DOMManipulator.setText('deleteProcessName', areaName);
    ModalHelper.open('confirmDeleteModal', 10000001, true);
    window.processToDelete = { id: procesoId, name: areaName };
    setupConfirmDeleteModalListeners();
  }

  /**
   * Configura listeners del modal de confirmación de eliminación
   */
  function setupConfirmDeleteModalListeners() {
    DOMManipulator.addEventListeners([
      {
        elementId: 'btnCancelDelete',
        eventName: 'click',
        handler: closeConfirmDeleteModal
      },
      {
        elementId: 'closeConfirmDeleteModal',
        eventName: 'click',
        handler: closeConfirmDeleteModal
      },
      {
        elementId: 'btnConfirmDelete',
        eventName: 'click',
        handler: executeDeleteProcess
      }
    ]);

    const overlay = DOMManipulator.querySelector('.confirm-delete-overlay');
    if (overlay) {
      overlay.addEventListener('click', closeConfirmDeleteModal);
    }
  }

  /**
   * Cierra el modal de confirmación de eliminación
   */
  function closeConfirmDeleteModal() {
    ModalHelper.close('confirmDeleteModal');
    window.processToDelete = null;
  }

  /**
   * Ejecuta la eliminación del proceso
   */
  async function executeDeleteProcess() {
    if (!window.processToDelete) return;
    
    LoadingIndicator.show('btnConfirmDelete');
    
    const { id: procesoId, name: areaName } = window.processToDelete;
    
    try {
      log('executeDeleteProcess', 'Eliminando proceso', { procesoId, areaName });

      const result = await ApiService.proceso.eliminar(procesoId);
      closeConfirmDeleteModal();
      await loadPrendasWithTracking(window.currentOrderData.id);

      try {
        if (window.location.pathname.includes('/recibos-costura') && window.currentOrderData?.id && window.currentPrendaData?.id) {
          window.currentConsecutivoCosturaData = await ApiService.ordenes.getConsecutivoCostura(
            window.currentOrderData.id,
            window.currentPrendaData.id
          );
        }
      } catch (e) {
        console.warn('[executeDeleteProcess] No se pudo refrescar consecutivo-costura:', e);
      }

      if (window.prendasData && window.prendasData.length > 0 && window.currentPrendaData) {
        const prendaActualizada = window.prendasData.find(p => p.id == window.currentPrendaData.id);
        if (prendaActualizada) {
          window.currentPrendaData = prendaActualizada;
        }
      }
      
      if (window.currentPrendaData && window.currentPrendaData.id) {
        renderPrendaTrackingTimeline(window.currentPrendaData);
      }

      showSuccess('Proceso eliminado correctamente');
      actualizarAreaEnTablaRecibos();
    } catch (error) {
      err('executeDeleteProcess', 'Error', error);
      showError('Error al eliminar proceso: ' + error.message);
      closeConfirmDeleteModal();
    } finally {
      LoadingIndicator.hide('btnConfirmDelete');
    }
  }

  /**
   * API pública: Crea un nuevo proceso desde un área específica
   * @param {string} areaName - Nombre del área
   * @param {Event} event - Evento click
   * @param {string} encargadoPrefill - Encargado pre-cargado
   */
  window.handleCrearProcesoDesdeArea = function(areaName, event, encargadoPrefill = '') {
    try {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
      }

      if (typeof openAddProcesoModal !== 'function') {
        console.warn('[handleCrearProcesoDesdeArea] openAddProcesoModal no disponible');
        return;
      }

      if (typeof resetFormButton === 'function') {
        resetFormButton();
      } else {
        window.editingProcessId = null;
      }

      openAddProcesoModal();

      const procesoArea = document.getElementById('procesoArea');
      if (procesoArea) procesoArea.value = areaName || '';

      const procesoEncargado = document.getElementById('procesoEncargado');
      const encargadoFallback = window.currentConsecutivoCosturaData?.encargado || '';
      const encargadoFinal = String(encargadoPrefill || encargadoFallback || '').trim();
      if (procesoEncargado) {
        procesoEncargado.value = encargadoFinal ? encargadoFinal.toUpperCase() : '';
      }
    } catch (e) {
      err('handleCrearProcesoDesdeArea', 'Error', e);
    }
  };

  /**
   * Limpia todos los campos del formulario de proceso
   */
  function limpiarFormularioProceso() {
    const procesoArea = document.getElementById('procesoArea');
    if (procesoArea) procesoArea.value = '';

    const procesoEncargado = document.getElementById('procesoEncargado');
    if (procesoEncargado) procesoEncargado.value = '';

    const procesoEstado = document.getElementById('procesoEstado');
    if (procesoEstado) procesoEstado.value = 'Pendiente';

    const procesoFechaInicio = document.getElementById('procesoFechaInicio');
    if (procesoFechaInicio) procesoFechaInicio.value = '';

    const procesoObservaciones = document.getElementById('procesoObservaciones');
    if (procesoObservaciones) procesoObservaciones.value = '';
  }

  /**
   * Resetea el botón de confirmación a su estado original
   */
  function resetFormButton() {
    const btnConfirmar = document.getElementById('btnConfirmAddProceso');
    if (btnConfirmar) {
      btnConfirmar.textContent = 'Agregar Proceso';
      btnConfirmar.onclick = handleAgregarProceso;
    }
    window.editingProcessId = null;
  }

  // ============================================================================
  // SECCIÓN 9: ACTUALIZACIÓN DE TABLA DE RECIBOS
  // ============================================================================
  /**
   * Actualiza el área en la tabla de recibos de costura (si aplica)
   * Solo se ejecuta en página /recibos-costura
   */
  async function actualizarAreaEnTablaRecibos() {
    try {
      if (!window.location.pathname.includes('/recibos-costura')) {
        return;
      }

      const pedidoId = window.currentOrderData?.id || null;
      const prendaId = window.currentPrendaData?.id || null;
      const numeroRecibo = window.currentConsecutivoCosturaData?.consecutivo || null;

      if (!pedidoId || !prendaId || !numeroRecibo) {
        log('actualizarAreaEnTablaRecibos', 'Datos insuficientes, no se actualiza');
        return;
      }

      const row = findReciboCosturaRow(pedidoId, prendaId, numeroRecibo);
      if (!row) return;

      const data = await ApiService.ordenes.getConsecutivoCostura(pedidoId, prendaId);
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
      err('actualizarAreaEnTablaRecibos', 'Error general', error);
    }
  }

  /**
   * Encuentra la fila de recibo en la tabla
   * @param {number} pedidoId - ID del pedido
   * @param {number} prendaId - ID de la prenda
   * @param {string} numeroRecibo - Número de recibo
   * @returns {HTMLElement|null} Fila encontrada o null
   */
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

  // ============================================================================
  // SECCIÓN 10: UTILIDADES Y HELPERS
  // ============================================================================
  /**
   * Formatea una fecha de múltiples formatos al formato yyyy-mm-dd
   * Maneja formatos: DD/MM/YYYY, ISO strings, etc.
   * @param {string} dateString - Fecha a formatear
   * @returns {string|null} Fecha formateada o null si inválida
   */
  function formatDate(dateString) {
    if (!dateString) return null;
    
    try {
      if (typeof dateString === 'string' && dateString.includes('/')) {
        const parts = dateString.split('/');
        if (parts.length === 3) {
          const [day, month, year] = parts;
          const isoDate = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
          const date = new Date(isoDate);
          return date.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
          });
        }
      }
      
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

  /**
   * Muestra un mensaje de error al usuario
   * @param {string} message - Mensaje de error
   */
  function showError(message) {
    NotificationService.error(message, { fallbackAlert: false });
  }

  /**
   * Muestra un mensaje de éxito al usuario
   * @param {string} message - Mensaje de éxito
   */
  function showSuccess(message) {
    NotificationService.success(message);
  }

  /**
   * Renderiza badges de seguimientos
   * @param {Object} seguimientos - Objeto de seguimientos
   * @returns {string} HTML de badges
   */
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

  /**
   * Renderiza badges de áreas
   * @param {Object} areas - Objeto de áreas
   * @returns {string} HTML de badges
   */
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

  /**
   * Crea una tarjeta de seguimiento (deprecated - see createSeguimientoCard)
   * @param {string} tipo - Tipo de seguimiento
   * @param {Object} data - Datos del seguimiento
   * @returns {HTMLElement} Elemento de tarjeta
   */
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

  /**
   * Crea una tarjeta de prenda
   * @param {Object} prenda - Datos de la prenda
   * @param {number} index - Índice de la prenda
   * @returns {HTMLElement} Elemento de tarjeta
   */
  function createPrendaCard(prenda, index) {
    const card = document.createElement('div');
    card.className = 'tracking-prenda-table';
    
    card.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      showPrendaTracking(prenda);
    });
    
    const seguimientosHtml = renderSeguimientosBadges(prenda.seguimientos || {});
    const areasHtml = renderAreasBadges(prenda.seguimientos_por_area || {});
    let procesosHtml = '';
    
    if (prenda.procesos && prenda.procesos.length > 0) {
      procesosHtml = '<tr><td colspan="2"><div class="tracking-procesos-lista">';
      prenda.procesos.forEach(proceso => {
        const tipoProceso = proceso.tipo_proceso;
        const procesoNombre = tipoProceso?.nombre || 'Proceso';
        const procesoEstado = proceso.estado || 'PENDIENTE';
        
        procesosHtml += `
          <div class="tracking-proceso-item">
            <span class="proceso-nombre">${procesoNombre}</span>
            <span class="proceso-estado">${procesoEstado}</span>
          </div>
        `;
      });
      procesosHtml += '</div></td></tr>';
    }

    let bodegaBadge = '';
    if (prenda.de_bodega) {
      bodegaBadge = '<tr><td colspan="2"><div class="tracking-bodega-indicador">Se saca de bodega</div></td></tr>';
    }

    card.innerHTML = `
      <table class="tracking-table">
        <thead>
          <tr>
            <th colspan="2" class="tracking-table-header">
              ${prenda.nombre_prenda || `Prenda ${index + 1}`}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr class="tracking-table-row">
            <td class="tracking-table-label">Cantidad:</td>
            <td class="tracking-table-value">${prenda.cantidad || 0}</td>
          </tr>
          <tr class="tracking-table-row">
            <td class="tracking-table-label">Procesos:</td>
            <td class="tracking-table-value">${prenda.total_procesos || 0}</td>
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

  /**
   * Muestra la vista de selector de prendas
   */
  function showPrendasView() {
    closeTrackingModal();
    showPrendasSelector();
  }

  // ============================================================================
  // SECCIÓN 11: INICIALIZACIÓN
  // ============================================================================
  // Ejecutar inicialización cuando el DOM esté listo
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTrackingModalListeners);
  } else {
    initTrackingModalListeners();
  }

})();
```

---

## 📊 BENEFICIOS DESPUÉS DE FASE 1

| Métrica | Antes | Después |
|---------|-------|---------|
| Secciones claras | 0 (caos) | 11 secciones |
| Funciones documentadas | ~5% | 100% |
| Claridad de flujo | Baja | Alta |
| Facilidad de encontrar código | Difícil | Fácil |
| Legibilidad | ~40% | ~80% |
| Facilidad para bugs | Alta | Reducida |

---

## ✅ CHECKLIST IMPLEMENTACIÓN FASE 1

```
TAREAS:
- [ ] Crear branch: git checkout -b refactor/tracking-phase-1
- [ ] Hacer backup: cp tracking-modal-handler.js tracking-modal-handler.js.backup
- [ ] Restructurar: Agrupar secciones como se muestra
- [ ] Agregar JSDoc: Documentar cada función
- [ ] Agregar comentarios: De sección clara
- [ ] Probar en navegador: Sin errores en consola
- [ ] Verificar funcionalidad: Todo igual que antes
- [ ] Git commit: "refactor: Phase 1 - Organization and documentation"

TESTING:
- [ ] Cargar orden: Datos correctos
- [ ] Abrir tracking: Modal se muestra
- [ ] Ver prendas: Tabla se renderiza
- [ ] Agregar proceso: Funciona igual
- [ ] Editar proceso: Funciona igual
- [ ] Eliminar proceso: Confirmación aparece
- [ ] Cerrar modales: ESC y overlay funcionan
- [ ] Consola: Sin warnings/errors
```

