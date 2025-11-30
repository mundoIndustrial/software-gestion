/**
 * orderTracking-v2.js - Versión SOLID
 * 
 * Arquitectura: 9 módulos especializados
 * - dateUtils.js: Manipulación de fechas
 * - holidayManager.js: Gestión de festivos
 * - areaMapper.js: Mapeos de áreas e iconos
 * - trackingService.js: Lógica de cálculo de recorrido
 * - trackingUI.js: Renderización de interfaz
 * - apiClient.js: Comunicación con servidor
 * - processManager.js: Gestión de procesos (editar/eliminar)
 * - tableManager.js: Actualización de tabla
 * - dropdownManager.js: Gestión de dropdowns
 * 
 * Principios SOLID aplicados:
 * ✅ Single Responsibility: Cada módulo tiene una única responsabilidad
 * ✅ Open/Closed: Fácil de extender sin modificar código existente
 * ✅ Liskov Substitution: Interfaces consistentes
 * ✅ Interface Segregation: Clientes solo conocen lo que necesitan
 * ✅ Dependency Inversion: Dependen de abstracciones, no de implementaciones
 */

console.log('✅ orderTracking-v2.js cargado - Versión SOLID con 9 módulos');

/**
 * Función principal: Abre el modal de seguimiento del pedido
 */
async function openOrderTracking(orderId) {
    try {
        console.log('📍 Abriendo tracking para orden:', orderId);
        
        // Obtener datos del API
        const procesos = await ApiClient.getOrderProcesos(orderId);
        
        // Obtener días (opcional)
        const diasData = await ApiClient.getOrderDays(orderId);
        
        // Agregar información de días si está disponible
        if (diasData) {
            procesos.total_dias_habiles = diasData.total_dias;
        }
        
        // Mostrar tracking
        await displayOrderTrackingWithProcesos(procesos);
        
    } catch (error) {
        console.error('❌ Error al obtener procesos:', error);
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
        console.error('❌ Modal de seguimiento no encontrado');
        return;
    }
    
    // Llenar header
    TrackingUI.fillOrderHeader(orderData);
    
    // Obtener festivos
    const festivos = await HolidayManager.obtenerFestivos();
    
    // Procesos del API
    const procesos = orderData.procesos || [];
    
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
    
    console.log(`✅ Total de días mostrado: ${totalDias}`);
    
    // Agregar event listeners a los botones de admin
    attachProcessButtonListeners(procesos);
    
    // Mostrar modal
    TrackingUI.showModal();
}

/**
 * Vincula los event listeners a los botones de admin
 */
function attachProcessButtonListeners(procesos) {
    // Nota: Los botones se crean dinámicamente en TrackingUI.createAdminButtons
    // Aquí debería haber event listeners, pero por ahora se usan onclick directos
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
        console.error('Error al editar:', error);
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
        console.error('Error al eliminar:', error);
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
        console.error('Modal de seguimiento no encontrado');
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
    console.log('🚀 Inicializando Order Tracking v2 (SOLID)...');
    
    // Inicializar modal
    initializeTrackingModal();
    
    // Actualizar días en tabla
    setTimeout(() => {
        TableManager.updateDaysInTable();
    }, 500);
    
    console.log('✅ Order Tracking v2 inicializado correctamente');
}

/**
 * Inicializa los event listeners del modal
 */
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

/**
 * Inicializar cuando el DOM esté listo
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM listo, inicializando Order Tracking');
    initializeOrderTracking();
});

// Compatibilidad: Mantener acceso a funciones públicas
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
