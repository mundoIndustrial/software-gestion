async function openOrderTracking(orderId) {
    try {

        
        // Obtener procesos del API (devuelve un array directamente)
        const procesosData = await ApiClient.getOrderProcesos(orderId);
        
        // Obtener días (opcional)
        const diasData = await ApiClient.getOrderDays(orderId);
        
        // Obtener datos del pedido para el header
        let pedidoData = {};
        try {
            const pedidoResponse = await fetch(`/registros/${orderId}/recibos-datos`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            if (pedidoResponse.ok) {
                const response = await pedidoResponse.json();
                pedidoData = response.data || response;
            }
        } catch (e) {
            // Si falla, continuamos sin estos datos
        }
        
        // Construir objeto con estructura esperada
        const orderData = {
            numero_pedido: pedidoData.numero || pedidoData.numero_pedido || orderId,
            cliente: pedidoData.cliente || '-',
            estado: pedidoData.estado || '-',
            fecha_inicio: pedidoData.fecha_creacion || pedidoData.fecha_de_creacion_de_orden || new Date().toISOString(),
            fecha_estimada_de_entrega: pedidoData.fecha_estimada_de_entrega || null,
            procesos: Array.isArray(procesosData) ? procesosData : (procesosData.procesos || [])
        };
        
        // Agregar información de días si está disponible
        if (diasData) {
            orderData.total_dias_habiles = diasData.total_dias;
        }
        
        // Mostrar tracking
        await displayOrderTrackingWithProcesos(orderData);
        
    } catch (error) {

        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al cargar el seguimiento de la orden',
            confirmButtonColor: '#ef4444'
        });
    }
}

/**
 * Muestra el modal de seguimiento con la nueva estructura de procesos
 */
async function displayOrderTrackingWithProcesos(orderData) {
    const modal = TrackingUI.getModal();
    if (!modal) {

        return;
    }
    
    // Llenar header
    TrackingUI.fillOrderHeader(orderData);
    
    // Obtener festivos
    const festivos = await HolidayManager.obtenerFestivos();
    
    // Procesos del API
    const procesos = Array.isArray(orderData.procesos) ? orderData.procesos : [];
    // Asegurar orden cronológico (CORTE -> COSTURA) por fecha_inicio
    procesos.sort((a, b) => {
        const fa = DateUtils.parseLocalDate(a?.fecha_inicio);
        const fb = DateUtils.parseLocalDate(b?.fecha_inicio);
        const ta = isNaN(fa?.getTime?.()) ? 0 : fa.getTime();
        const tb = isNaN(fb?.getTime?.()) ? 0 : fb.getTime();

        if (ta !== tb) return ta - tb;
        const ia = Number.parseInt(String(a?.id ?? 0), 10) || 0;
        const ib = Number.parseInt(String(b?.id ?? 0), 10) || 0;
        return ia - ib;
    });
    
    if (!procesos || procesos.length === 0) {
        TrackingUI.updateTotalDays(0);
        const container = document.getElementById('trackingTimelineContainer');
        if (container) {
            container.innerHTML = '<p class="text-center text-gray-500">No hay procesos registrados</p>';
        }
        TrackingUI.showModal();
        return;
    }
    
    // Renderizar timeline
    const totalDiasCalculado = TrackingUI.renderProcessTimeline(procesos, orderData, festivos);
    
    // Usar total del backend o el calculado
    let totalDias = orderData.total_dias_habiles || totalDiasCalculado;
    TrackingUI.updateTotalDays(totalDias);
    

    
    // Agregar event listeners a los botones de admin
    attachProcessButtonListeners(procesos);
    
    // Mostrar modal
    TrackingUI.showModal();
}

/**
 * Vincula los event listeners a los botones de admin y tarjetas de proceso
 */
/**
 * Vincula los event listeners a los botones de admin
 */
function attachProcessButtonListeners(procesos) {
    // Usar event delegation en el contenedor del timeline
    const timelineContainer = document.getElementById('trackingTimelineContainer');
    if (!timelineContainer) {

        return;
    }
    
    // Event delegation para botones de editar
    timelineContainer.addEventListener('click', function(e) {
        const editBtn = e.target.closest('.btn-editar-proceso');
        if (!editBtn) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        // Encontrar el proceso asociado al botón
        const card = editBtn.closest('.tracking-area-card');
        const areaNameElement = card.querySelector('.tracking-area-name span:last-child');
        const processName = areaNameElement ? areaNameElement.textContent.trim() : '';
        
        // Buscar el proceso en la lista
        const proceso = procesos.find(p => p.proceso === processName);
        if (proceso) {
            editarProceso(JSON.stringify(proceso));
        }
    }, false);
    
    // Event delegation para botones de eliminar
    timelineContainer.addEventListener('click', function(e) {
        const deleteBtn = e.target.closest('.btn-eliminar-proceso');
        if (!deleteBtn) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        // Encontrar el proceso asociado al botón
        const card = deleteBtn.closest('.tracking-area-card');
        const areaNameElement = card.querySelector('.tracking-area-name span:last-child');
        const processName = areaNameElement ? areaNameElement.textContent.trim() : '';
        
        // Buscar el proceso en la lista
        const proceso = procesos.find(p => p.proceso === processName);
        if (proceso) {
            const procesosDistintos = Array.from(
                new Set((procesos || []).map((p) => String(p.proceso || '').trim()).filter(Boolean))
            );

            if (procesosDistintos.length <= 1) {
                Swal.fire({
                    icon: 'info',
                    title: 'No se puede eliminar',
                    text: 'No se puede eliminar el único proceso de una orden',
                    confirmButtonColor: '#3b82f6',
                    didOpen: (modal) => {
                        const swalContainer = document.querySelector('.swal2-container');
                        if (swalContainer) swalContainer.style.zIndex = '99999';
                        modal.style.zIndex = '99999';
                    }
                });
                return;
            }
            eliminarProceso(JSON.stringify(proceso));
        }
    }, false);
}

/**
 * Cierra el modal de seguimiento
 */
function closeOrderTracking() {
    TrackingUI.hideModal();
}

/**
 * Abre el modal de edición (función wrapper)
 */
function editarProceso(procesoJsonStr) {
    try {
        const proceso = JSON.parse(procesoJsonStr);
        ProcessManager.openEditModal(proceso);
    } catch (error) {

        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al abrir el formulario de edición',
            confirmButtonColor: '#ef4444'
        });
    }
}

/**
 * Elimina un proceso (función wrapper)
 */
function eliminarProceso(procesoJsonStr) {
    try {
        const proceso = JSON.parse(procesoJsonStr);
        ProcessManager.deleteProcess(proceso);
    } catch (error) {

        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error al eliminar el proceso',
            confirmButtonColor: '#ef4444'
        });
    }
}

/**
 * Cierra el dropdown (función wrapper)
 */
function closeViewDropdown(orderId) {
    ViewDropdownManager.closeViewDropdown(orderId);
}

/**
 * Crea el dropdown del botón Ver (función wrapper)
 */
function createViewButtonDropdown(orderId) {
    ViewDropdownManager.createViewButtonDropdown(orderId);
}

/**
 * Obtiene el recorrido del pedido (compatibilidad)
 */
async function getOrderTrackingPath(order) {
    return TrackingService.getOrderTrackingPath(order);
}

/**
 * Muestra el modal de seguimiento (compatibilidad con código antiguo)
 */
async function displayOrderTracking(order) {
    const modal = TrackingUI.getModal();
    if (!modal) {

        return;
    }
    
    // Llenar información básica
    TrackingUI.fillOrderHeader({
        numero_pedido: order.numero_pedido || order.pedido,
        cliente: order.cliente || '-',
        fecha_inicio: order.fecha_de_creacion_de_orden,
        fecha_estimada_de_entrega: order.fecha_estimada_de_entrega
    });
    
    // Obtener recorrido
    const trackingPath = await TrackingService.getOrderTrackingPath(order);
    
    // Calcular total de días
    let totalDiasReal = 0;
    trackingPath.forEach(item => {
        totalDiasReal += item.daysInArea;
    });
    
    // Actualizar UI (nota: esta función es simplificada, la versión completa está en el original)
    TrackingUI.updateTotalDays(totalDiasReal);
    
    // Mostrar modal
    TrackingUI.showModal();
}

/**
 * Actualiza los días en la tabla
 */
function actualizarDiasTabla() {
    TableManager.updateDaysInTable();
}

/**
 * Hook para actualizar días cuando cambia de página
 */
function actualizarDiasAlCambiarPagina() {
    TableManager.updateDaysOnPageChange();
}

/**
 * Inicializa el módulo de tracking
 */
function initializeOrderTracking() {

    
    // Inicializar modal
    initializeTrackingModal();
    
    setTimeout(() => {
        TableManager.updateDaysInTable();
    }, 500);
    

}

function initializeTrackingModal() {
    const modal = TrackingUI.getModal();
    const overlay = document.getElementById('trackingModalOverlay');
    const closeBtn = document.getElementById('closeTrackingModal');
    const closeFooterBtn = document.getElementById('closeTrackingModalBtn');
    
    if (!modal) return;
    
    if (closeBtn) {
        closeBtn.addEventListener('click', closeOrderTracking);
    }
    
    if (closeFooterBtn) {
        closeFooterBtn.addEventListener('click', closeOrderTracking);
    }
    
    if (overlay) {
        overlay.addEventListener('click', closeOrderTracking);
    }
    
    const modalContent = document.querySelector('.tracking-modal-content');
    if (modalContent) {
        modalContent.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }
}

document.addEventListener('DOMContentLoaded', function() {

    initializeOrderTracking();
});


window.openOrderTracking = openOrderTracking;
window.closeOrderTracking = closeOrderTracking;
window.displayOrderTracking = displayOrderTracking;
window.displayOrderTrackingWithProcesos = displayOrderTrackingWithProcesos;
window.editarProceso = editarProceso;
window.eliminarProceso = eliminarProceso;
window.createViewButtonDropdown = createViewButtonDropdown;
window.closeViewDropdown = closeViewDropdown;
window.actualizarDiasTabla = actualizarDiasTabla;
window.actualizarDiasAlCambiarPagina = actualizarDiasAlCambiarPagina;
