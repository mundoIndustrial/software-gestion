/**
 * LEGACY FUNCTIONS - Compatibilidad Dual
 * 
 * ⚠️ IMPORTANTE: Estas funciones tienen versiones MEJORADAS en bundle.js
 * 
 * DELEGADAS AL BUNDLE (Si bundle.js carga, estas se SOBRESCRIBEN):
 * ✅ setupTableEventListeners() - lines ~400-500
 * ✅ crearDropdownRecibos() - lines ~600-700  
 * ✅ closeDropdownRecibos() - Se sobrescribe por la del bundle
 * ✅ openOrderDetailModal() - Se sobrescribe por la del bundle
 * ✅ closeModalOverlay() - Se sobrescribe (window.closeModalOverlay)
 * 
 * MANTIENEN SU LÓGICA ORIGINAL (no están en bundle):
 * ✅ openFilterModal()
 * ✅ loadFilterOptions()
 * ✅ getDynamicFilterOptions()
 * ✅ getColumnIndex()
 * ✅ closeFilterModal()
 * ✅ resetFilters()
 * ✅ applyFilters()
 * ✅ verDetallesRecibo()
 * ✅ abrirModalSeguimiento()
 * ✅ abrirModalAgregarProcesoDesdeArea()
 * ✅ handleAgregarProcesoDesdeBadge()
 * ✅ showToast(), showSuccess(), showError()
 * ✅ cargarConteoRecibosCorte()
 * ✅ initializeReciboAprobadoListener()
 * 
 * ESTRATEGIA DE MIGRACIÓN:
 * 1. Bundle.js carga primero y proporciona versiones optimizadas
 * 2. Si bundle.js falla, las funciones legacy del blade funcionan
 * 3. Se pueden eliminar de aquí UNA POR UNA una vez validadas
 */

// ========== FUNCIONES DE FILTRO - DELEGADAS AL FILTERMODULE ==========
// El nuevo FilterModule.js maneja todo el sistema de filtros
// Estas funciones mantienen compatibilidad con código existente

// Compatibilidad global: window.activeFilters vinculado a FilterModule
Object.defineProperty(window, 'activeFilters', {
    get: function() {
        return FilterModule.getInstance().getActiveFilters();
    },
    configurable: true
});

window.openFilterModal = function(filterType) {
    return FilterModule.getInstance().openFilterModal(filterType);
};

function loadFilterOptions(filterType) {
    return FilterModule.getInstance().loadFilterOptions(filterType);
}

function getDynamicFilterOptions(filterType) {
    return FilterModule.getInstance().getDynamicFilterOptions(filterType);
}

function getColumnIndex(filterType) {
    return FilterModule.getInstance().getColumnIndex(filterType);
}

window.closeFilterModal = function() {
    return FilterModule.getInstance().closeFilterModal();
};

window.resetFilters = function() {
    return FilterModule.getInstance().resetFilters();
};

window.applyFilters = function() {
    return FilterModule.getInstance().applyFilters();
};

window.selectAllCheckboxFilters = function(filterType) {
    return FilterModule.getInstance().selectAllCheckboxes(filterType);
};

window.filterCheckboxOptions = function(filterType) {
    return FilterModule.getInstance().filterCheckboxOptions(filterType);
};

// ========== INICIALIZACIÓN AL CARGAR PÁGINA ==========
// Cargar nombres de prendas al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    console.log('[DOMContentLoaded] 📄 Cargando nombres de prendas en recibos-costura');
    
    // Diagnóstico del sistema de agregar proceso
    console.log('[DIAGNÓSTICO] Verificando sistema de agregar proceso desde badge...');
    console.log('[DIAGNÓSTICO] Elementos disponibles:', {
        modalAddProceso: !!document.getElementById('addProcesoModal'),
        btnConfirmAddProceso: !!document.getElementById('btnConfirmAddProceso'),
        procesoArea: !!document.getElementById('procesoArea'),
        procesoEncargado: !!document.getElementById('procesoEncargado'),
        'typeof handleAgregarProceso': typeof handleAgregarProceso,
        'typeof verificarDatosAntesDeGuardar': typeof verificarDatosAntesDeGuardar
    });
    
    // Obtener todas las filas de recibos
    const filasRecibos = document.querySelectorAll('#tablaRecibosBody tr[data-orden-id]');
    
    filasRecibos.forEach(fila => {
        const reciboId = fila.getAttribute('data-orden-id');
        const descripcionElemento = fila.querySelector('.descripcion-prenda-texto');
        
        if (descripcionElemento) {
            // Buscar el enlace del pedido para obtener el pedido_produccion_id
            const enlacePedido = fila.querySelector('a[href*="/registros/"]');
            let pedidoProduccionId = null;
            
            if (enlacePedido) {
                const href = enlacePedido.getAttribute('href');
                const match = href.match(/\/registros\/(\d+)/);
                if (match) {
                    pedidoProduccionId = match[1];
                }
            }
            
            if (pedidoProduccionId) {
                // Obtener el nombre de la primera prenda del pedido
                fetch(`/api/pedidos/${pedidoProduccionId}/prendas`)
                    .then(response => response.json())
                    .then(datos => {
                        if (datos.data && typeof datos.data === 'object') {
                            datos = datos.data;
                        }
                        
                        if (datos.prendas && Array.isArray(datos.prendas) && datos.prendas.length > 0) {
                            const primeraPrenda = datos.prendas[0];
                            const nombrePrenda = primeraPrenda.nombre || primeraPrenda.nombre_prenda || 'Sin nombre';
                            
                            // Actualizar el texto de la descripción
                            descripcionElemento.textContent = nombrePrenda;
                            console.log(`[CargarNombres] ✅ Prenda actualizada para recibo ${reciboId}: ${nombrePrenda}`);
                        } else {
                            descripcionElemento.textContent = 'Sin prendas';
                        }
                    })
                    .catch(error => {
                        console.error(`[CargarNombres] Error cargando prenda para recibo ${reciboId}:`, error);
                        descripcionElemento.textContent = 'Error';
                    });
            } else {
                descripcionElemento.textContent = 'Sin pedido';
            }
        }
    });
    
    // Verificar badges clickeables
    const badgesArea = document.querySelectorAll('.area-badge-clickable');
    console.log(`[DIAGNÓSTICO] Encontrados ${badgesArea.length} badges de área clickeables`);
    
    badgesArea.forEach((badge, index) => {
        const onclick = badge.getAttribute('onclick');
    
    });
});

// ========== TRACKING MODAL - DELEGADO AL CONTROLADOR ==========
// El nuevo TrackingModalController.js maneja todo el sistema de seguimiento modal
// Estas funciones mantienen compatibilidad con código existente que las llamaba

function verDetallesRecibo(reciboId) {
    return TrackingModalController.getInstance().viewDetails(reciboId);
}

// Función para abrir el modal de seguimiento
function abrirModalSeguimiento(pedidoId, prendaIdTarget) {
    return TrackingModalController.getInstance().open(pedidoId, prendaIdTarget);
}

// Función para abrir el modal de seguimiento directamente sin selector
function abrirModalSeguimientoDirecto(pedidoId, prendaIdTarget) {
    return TrackingModalController.getInstance()._openTrackingModal(pedidoId, prendaIdTarget);
}

// ========== ADD PROCESS MODAL - DELEGADO AL CONTROLADOR ==========
// El nuevo AddProcessModalController.js maneja todo el sistema de agregar procesos
// Estas funciones mantienen compatibilidad con código existente que las llamaba

// Función para abrir el modal de agregar proceso desde el badge del área
window.abrirModalAgregarProcesoDesdeArea = function(areaSeleccionada, pedidoId, prendaId) {
    return AddProcessModalController.getInstance().openFromBadge(areaSeleccionada, pedidoId, prendaId);
};

// Función de verificación antes de guardar
function verificarDatosAntesDeGuardar(event) {
    return AddProcessModalController.getInstance().verifyAndSave(event);
}

// Función específica para agregar proceso desde badge en recibos-costura
async function handleAgregarProcesoDesdeBadge() {
    return AddProcessModalController.getInstance().save();
}

// Función para limpiar formulario de proceso
function limpiarFormularioProceso() {
    return AddProcessModalController.getInstance().clearForm();
}

// ========== TOAST NOTIFICATIONS - DELEGADAS AL SERVICIO ==========
// Las funciones de toast ahora usan ToastNotificationService para mantener
// compatibilidad con código existente que usa showSuccess(), showError(), etc.

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

// Función para cargar los datos necesarios para agregar proceso
async function cargarDatosParaAgregarProceso(pedidoId, prendaId, areaSeleccionada) {
    console.log('[cargarDatosParaAgregarProceso] Cargando datos para pedido:', pedidoId, 'prenda:', prendaId);
    
    try {
        // ⚠️ VALIDAR QUE SE PROPORCIONE UNA PRENDA ESPECÍFICA
        if (!prendaId || prendaId === 'null' || prendaId === null) {
            throw new Error('CRÍTICO: No se proporcionó una prenda específica. No se puede asignar encargado sin prenda definida.');
        }
        
        // Cargar datos básicos del pedido
        const response = await fetch(`/registros/${pedidoId}/recibos-datos`);
        if (!response.ok) throw new Error('Error al cargar datos del pedido');
        
        const result = await response.json();
        const data = result.data || result;
        
        console.log('[cargarDatosParaAgregarProceso] Datos recibidos del endpoint:', data);
        
        // Asegurar que la estructura de datos sea compatible con handleAgregarProceso
        // El endpoint /seguimiento-proceso/guardar espera:
        // - pedido_produccion_id: el ID del pedido (numero_pedido)
        // - prenda_id: el ID de la prenda
        const orderData = {
            ...data,
            numero_pedido: data.numero_pedido || data.id || pedidoId,
            pedido: data.numero_pedido || data.id || pedidoId
        };
        
        // Establecer variables globales para que handleAgregarProceso funcione
        window.currentOrderData = orderData;
        window.currentPedidoId = pedidoId;
        window.currentPrendaId = prendaId;
        window.currentArea = areaSeleccionada;
        
        // Buscar la prenda específica en los datos del pedido
        if (data.prendas && Array.isArray(data.prendas)) {
            let prendaEncontrada = null;
            
            // 🔒 SER ESTRICTO: Buscar EXACTAMENTE la prenda especificada, SIN FALLBACK
            prendaEncontrada = data.prendas.find(p => 
                String(p.id) === String(prendaId) || 
                String(p.prenda_pedido_id) === String(prendaId)
            );
            
            if (prendaEncontrada) {
                window.currentPrendaData = prendaEncontrada;
                console.log('[cargarDatosParaAgregarProceso] ✅ Prenda encontrada:', prendaEncontrada.nombre_prenda || prendaEncontrada.nombre, 'ID:', prendaEncontrada.id);
            } else {
                // 🛑 SIN FALLBACK: Si no se encuentra la prenda específica, lanzar error
                throw new Error(`Prenda con ID ${prendaId} no encontrada en pedido ${pedidoId}. No se puede asignar encargado a una prenda desconocida.`);
            }
        } else {
            throw new Error('El pedido no tiene prendas asociadas');
        }
        
        console.log('[cargarDatosParaAgregarProceso] ✅ Datos cargados correctamente');
        console.log('[cargarDatosParaAgregarProceso] currentOrderData:', window.currentOrderData);
        console.log('[cargarDatosParaAgregarProceso] currentPrendaData:', window.currentPrendaData);
        console.log('[cargarDatosParaAgregarProceso] Verificación final:', {
            hasOrderData: !!window.currentOrderData,
            hasPrendaData: !!window.currentPrendaData,
            orderNumero: window.currentOrderData?.numero_pedido,
            prendaId: window.currentPrendaData?.id,
            'pedido_produccion_id_para_endpoint': window.currentOrderData?.numero_pedido
        });
        
    } catch (error) {
        console.error('[cargarDatosParaAgregarProceso] Error:', error);
        throw error;
    }
}

// ⚠️ closeModalOverlay() eliminada - versión en bundle.js (fallback puro)
// ⚠️ closeDropdownRecibos() original eliminada - versión mejorada a continuación

// ========== FUNCIONES DE DROPDOWN - DELEGADAS AL DROPDOWNSERVICE ==========
// El nuevo DropdownService.js maneja todo el sistema de dropdowns
// Estas funciones mantienen compatibilidad con código existente

window.closeDropdownRecibos = function() {
    return DropdownService.getInstance().closeAll();
};

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

// ========== INICIALIZACIÓN DE DROPDOWNS ==========
document.addEventListener('DOMContentLoaded', function() {
    console.log('[DOMContentLoaded] 📄 Inicializando dropdowns - delegados a DropdownService');

    // Ocultar botón Volver
    if (window.location.pathname.includes('/recibos-costura')) {
        const botonVolver = document.getElementById('backToPrendasBtn');
        if (botonVolver) {
            botonVolver.style.display = 'none';
            console.log('[DOMContentLoaded] Botón Volver ocultado');
        }
    }

    // Inicializar DropdownService (adjunta event listeners internamente)
    DropdownService.getInstance().attachEventListeners();
});

// ======= CAMPANA DE NOTIFICACIONES PARA RECIBOS DE COSTURA =======
console.log('[🔔 CAMPANA COSTURA] Sistema iniciado');

// ========== CAMPANA DE COSTURA - DELEGADAS AL SERVICIO ==========
// El nuevo CosturaNotificationBellService.js maneja todo el sistema de notificaciones
// Estas funciones mantienen compatibilidad con código existente que las llamaba

async function cargarConteoRecibosCorte() {
    return CosturaNotificationBellService.getInstance().loadCosturaCount();
}

async function marcarReciboVisto(reciboId, itemElement) {
    return CosturaNotificationBellService.getInstance().markAsViewed(reciboId, itemElement);
}

function setupCosturaNotifications() {
    return CosturaNotificationBellService.getInstance()._setupEventListeners();
}

// Inicialización del servicio de notificaciones de campana de costura
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        // Usar servicio para manejar notificaciones de costura (con polling automático)
        CosturaNotificationBellService.getInstance().init();
        
        // 🔴 LISTENER EN TIEMPO REAL PARA RECIBOS APROBADOS
        initializeReciboAprobadoListener();
    });
} else {
    // Usar servicio para manejar notificaciones de costura (con polling automático)
    CosturaNotificationBellService.getInstance().init();
    
    // 🔴 LISTENER EN TIEMPO REAL PARA RECIBOS APROBADOS
    initializeReciboAprobadoListener();
}

/**
 * 🔴 LISTENER EN TIEMPO REAL - Escucha cuando se aprueban insumos
 * Se conecta al evento 'recibo.aprobado' del canal 'recibos-costura'
 */
// ========== REALTIME LISTENER - DELEGADO AL SERVICIO ==========
// El nuevo RealtimeReciboListener.js maneja todo el sistema de escucha en tiempo real
// Estas funciones mantienen compatibilidad con código existente que las llamaba

function initializeReciboAprobadoListener() {
    return RealtimeReciboListener.getInstance().initialize();
}

/**
 * 🔴 Mostrar notificación visual cuando se aprueba un recibo
 */
function showRecibAprobadoNotification(data) {
    return RealtimeReciboListener.getInstance()._showNotification(data);
}

/**
 * 🔴 Recargar la tabla dinámicamente cuando se aprueba un recibo
 */
function recargarTablaRecibosEnTiempoReal(data) {
    return RealtimeReciboListener.getInstance()._reloadTable(data);
}
