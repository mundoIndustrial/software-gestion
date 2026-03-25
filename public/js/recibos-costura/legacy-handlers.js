/**
 * LEGACY HANDLERS - Puente de Compatibilidad
 * 
 * Archivo minimalista que proporciona wrappers globales para delegar
 * funcionalidad a módulos modernos (bundle.js, servicios, controladores)
 * 
 * Este archivo es necesario porque:
 * - El HTML tiene llamadas onclick="openFilterModal()" que necesitan funciones globales
 * - Los módulos están encapsulados en bundle.js
 * - Este archivo actúa como puente entre HTML y módulos
 */

// ========== WRAPPERS DE FILTERMODULE ==========
Object.defineProperty(window, 'activeFilters', {
    get: () => FilterModule.getInstance().getActiveFilters(),
    configurable: true
});

window.openFilterModal = (filterType) => FilterModule.getInstance().openFilterModal(filterType);
window.closeFilterModal = () => FilterModule.getInstance().closeFilterModal();
window.resetFilters = () => FilterModule.getInstance().resetFilters();
window.applyFilters = () => FilterModule.getInstance().applyFilters();
window.selectAllCheckboxFilters = (filterType) => FilterModule.getInstance().selectAllCheckboxes(filterType);
window.filterCheckboxOptions = (filterType) => FilterModule.getInstance().filterCheckboxOptions(filterType);

function loadFilterOptions(filterType) {
    return FilterModule.getInstance().loadFilterOptions(filterType);
}

function getDynamicFilterOptions(filterType) {
    return FilterModule.getInstance().getDynamicFilterOptions(filterType);
}

function getColumnIndex(filterType) {
    return FilterModule.getInstance().getColumnIndex(filterType);
}

// ========== WRAPPERS DE TRACKINGMODALCONTROLLER ==========
function verDetallesRecibo(reciboId) {
    return TrackingModalController.getInstance().viewDetails(reciboId);
}

function abrirModalSeguimiento(pedidoId, prendaIdTarget) {
    return TrackingModalController.getInstance().open(pedidoId, prendaIdTarget);
}

function abrirModalSeguimientoDirecto(pedidoId, prendaIdTarget) {
    return TrackingModalController.getInstance()._openTrackingModal(pedidoId, prendaIdTarget);
}

// ========== WRAPPERS DE ADDPROCESSMODALCONTROLLER ==========
window.abrirModalAgregarProcesoDesdeArea = (areaSeleccionada, pedidoId, prendaId) => 
    AddProcessModalController.getInstance().openFromBadge(areaSeleccionada, pedidoId, prendaId);

function verificarDatosAntesDeGuardar(event) {
    return AddProcessModalController.getInstance().verifyAndSave(event);
}

async function handleAgregarProcesoDesdeBadge() {
    return AddProcessModalController.getInstance().save();
}

function limpiarFormularioProceso() {
    return AddProcessModalController.getInstance().clearForm();
}

// ========== WRAPPERS DE TOASTNOTIFICATIONSERVICE ==========
function showSuccess(message, title = 'Éxito') {
    return ToastNotificationService.getInstance().success(message, title);
}

function showError(message, title = 'Error') {
    return ToastNotificationService.getInstance().error(message, title);
}

function showToast(message, type = 'info', title = '') {
    return ToastNotificationService.getInstance().show(message, type, title);
}

function removeToast(toastId) {
    return ToastNotificationService.getInstance().remove(toastId);
}

function clearAllToasts() {
    return ToastNotificationService.getInstance().clearAll();
}

// ========== WRAPPERS DE DROPDOWNSERVICE ==========
window.closeDropdownRecibos = () => DropdownService.getInstance().closeAll();

function extraerDataDelBoton(button) {
    return DropdownService.getInstance().extractButtonData(button);
}

function construirBotonesDropdown(data) {
    return DropdownService.getInstance().buildDropdownButtons(data);
}

function posicionarDropdown(dropdown, button) {
    return DropdownService.getInstance().positionDropdown(dropdown, button);
}

function crearDropdownRecibos(button) {
    return DropdownService.getInstance().createOrGetDropdown(button);
}

// ========== WRAPPERS DE COSTURANOTIFICATIONBELLSERVICE ==========
async function cargarConteoRecibosCorte() {
    return CosturaNotificationBellService.getInstance().loadCosturaCount();
}

async function marcarReciboVisto(reciboId, itemElement) {
    return CosturaNotificationBellService.getInstance().markAsViewed(reciboId, itemElement);
}

function setupCosturaNotifications() {
    return CosturaNotificationBellService.getInstance()._setupEventListeners();
}

// ========== WRAPPERS DE REALTIMERECIBOLISTENER ==========
function initializeReciboAprobadoListener() {
    return RealtimeReciboListener.getInstance().initialize();
}

function showRecibAprobadoNotification(data) {
    return RealtimeReciboListener.getInstance()._showNotification(data);
}

function recargarTablaRecibosEnTiempoReal(data) {
    return RealtimeReciboListener.getInstance()._reloadTable(data);
}

// ========== INICIALIZACIÓN ==========
// Ocultar botón Volver si existe
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        const botonVolver = document.getElementById('backToPrendasBtn');
        if (botonVolver && window.location.pathname.includes('/recibos-costura')) {
            botonVolver.style.display = 'none';
        }
        DropdownService.getInstance().attachEventListeners();
    });
} else {
    const botonVolver = document.getElementById('backToPrendasBtn');
    if (botonVolver && window.location.pathname.includes('/recibos-costura')) {
        botonVolver.style.display = 'none';
    }
    DropdownService.getInstance().attachEventListeners();
}

// Inicializar servicios de campana y realtime
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        CosturaNotificationBellService.getInstance().init();
        initializeReciboAprobadoListener();
    });
} else {
    CosturaNotificationBellService.getInstance().init();
    initializeReciboAprobadoListener();
}

// ========== FUNCIONES DE CARGA DE DATOS (aún no migradas) ==========
/**
 * TODO: Esta función debería migrase a AddProcessModalController
 * Está aquí temporalmente por compatibilidad
 */
async function cargarDatosParaAgregarProceso(pedidoId, prendaId, areaSeleccionada) {
    try {
        if (!prendaId || prendaId === 'null' || prendaId === null) {
            throw new Error('Prenda específica requerida');
        }
        
        const response = await fetch(`/registros/${pedidoId}/recibos-datos`);
        if (!response.ok) throw new Error('Error al cargar datos del pedido');
        
        const result = await response.json();
        const data = result.data || result;
        
        window.currentOrderData = {
            ...data,
            numero_pedido: data.numero_pedido || data.id || pedidoId,
            pedido: data.numero_pedido || data.id || pedidoId
        };
        window.currentPedidoId = pedidoId;
        window.currentPrendaId = prendaId;
        window.currentArea = areaSeleccionada;
        
        if (data.prendas && Array.isArray(data.prendas)) {
            const prendaEncontrada = data.prendas.find(p => 
                String(p.id) === String(prendaId) || 
                String(p.prenda_pedido_id) === String(prendaId)
            );
            
            if (!prendaEncontrada) {
                throw new Error(`Prenda ${prendaId} no encontrada en pedido ${pedidoId}`);
            }
            
            window.currentPrendaData = prendaEncontrada;
        } else {
            throw new Error('El pedido no tiene prendas');
        }
    } catch (error) {
        console.error('[cargarDatosParaAgregarProceso]', error.message);
        throw error;
    }
}

// Cargar nombres de prendas en tabla (inicialización de carga)
document.addEventListener('DOMContentLoaded', function() {
    const filasRecibos = document.querySelectorAll('#tablaRecibosBody tr[data-orden-id]');
    
    filasRecibos.forEach(fila => {
        const reciboId = fila.getAttribute('data-orden-id');
        const descripcionElemento = fila.querySelector('.descripcion-prenda-texto');
        
        if (descripcionElemento) {
            const enlacePedido = fila.querySelector('a[href*="/registros/"]');
            let pedidoProduccionId = null;
            
            if (enlacePedido) {
                const match = enlacePedido.getAttribute('href').match(/\/registros\/(\d+)/);
                if (match) pedidoProduccionId = match[1];
            }
            
            if (pedidoProduccionId) {
                fetch(`/api/pedidos/${pedidoProduccionId}/prendas`)
                    .then(r => r.json())
                    .then(datos => {
                        const data = datos.data && typeof datos.data === 'object' ? datos.data : datos;
                        if (data.prendas && Array.isArray(data.prendas) && data.prendas.length > 0) {
                            const nombrePrenda = data.prendas[0].nombre || data.prendas[0].nombre_prenda || 'Sin nombre';
                            descripcionElemento.textContent = nombrePrenda;
                        }
                    })
                    .catch(e => {
                        console.error(`[CargarNombres] Error para recibo ${reciboId}:`, e);
                        descripcionElemento.textContent = 'Error';
                    });
            } else {
                descripcionElemento.textContent = 'Sin pedido';
            }
        }
    });
});
