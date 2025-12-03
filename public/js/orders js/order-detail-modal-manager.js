/**
 * Order Detail Modal Manager para Registro de Órdenes
 * Maneja la apertura y cierre del modal de detalles de orden
 * SINCRONIZADO CON: pedidos-detail-modal.js (asesores)
 */

console.log('📄 [MODAL] Cargando order-detail-modal-manager.js');

/**
 * Abre el modal de detalle de la orden
 * Compatible con la estructura de asesores
 */
window.openOrderDetailModal = function(orderId) {
    console.log('%c🔵 [MODAL] Abriendo modal para orden: ' + orderId, 'color: blue; font-weight: bold; font-size: 14px;');
    
    // Obtener el overlay
    let overlay = document.getElementById('modal-overlay');
    console.log('🔍 [MODAL] Overlay encontrado:', !!overlay);
    
    if (overlay) {
        // Mover al body si es necesario
        if (overlay.parentElement !== document.body) {
            document.body.appendChild(overlay);
        }
        
        // Mostrar overlay
        overlay.style.display = 'block';
        overlay.style.zIndex = '9997';
        overlay.style.position = 'fixed';
        overlay.style.opacity = '1';
        overlay.style.visibility = 'visible';
        overlay.style.top = '0';
        overlay.style.left = '0';
        overlay.style.width = '100%';
        overlay.style.height = '100%';
        console.log('✅ [MODAL] Overlay mostrado');
        
        // Mostrar el wrapper del modal
        const modalWrapper = document.getElementById('order-detail-modal-wrapper');
        if (modalWrapper) {
            modalWrapper.style.display = 'block';
            modalWrapper.style.zIndex = '9998';
            modalWrapper.style.position = 'fixed';
            modalWrapper.style.top = '60%';
            modalWrapper.style.left = '50%';
            modalWrapper.style.transform = 'translate(-50%, -50%)';
            modalWrapper.style.pointerEvents = 'auto';
            console.log('✅ [MODAL] Wrapper mostrado');
        } else {
            console.error('❌ [MODAL] Wrapper no encontrado');
        }
    }
};

/**
 * Cierra el modal de detalle de la orden
 */
window.closeOrderDetailModal = function() {
    console.log('%c🔵 [MODAL] Cerrando modal', 'color: blue; font-weight: bold; font-size: 14px;');
    
    const overlay = document.getElementById('modal-overlay');
    const modalWrapper = document.getElementById('order-detail-modal-wrapper');
    
    if (overlay) {
        overlay.style.display = 'none';
        console.log('✅ [MODAL] Overlay ocultado');
    }
    
    if (modalWrapper) {
        modalWrapper.style.display = 'none';
        console.log('✅ [MODAL] Wrapper ocultado');
    }
};

/**
 * Cierra el modal al hacer click en el overlay
 */
window.closeModalOverlay = function() {
    console.log('🔵 [MODAL] Click en overlay, cerrando...');
    window.closeOrderDetailModal();
};

/**
 * Escuchar el evento de apertura del modal
 */
document.addEventListener('DOMContentLoaded', function() {
    console.log('%c✅ [MODAL] DOM cargado, registrando listeners', 'color: green; font-weight: bold; font-size: 14px;');
    
    // Listener para abrir el modal
    window.addEventListener('open-modal', function(event) {
        console.log('%c🔔 [MODAL] Evento open-modal recibido', 'color: purple; font-weight: bold; font-size: 14px;');
        console.log('   - detail:', event.detail);
        
        if (event.detail === 'order-detail') {
            console.log('%c✅ [MODAL] Detail es "order-detail", abriendo...', 'color: green; font-weight: bold;');
            window.openOrderDetailModal();
        }
    });
    
    // Listener para cerrar el modal
    window.addEventListener('close-modal', function(event) {
        if (event.detail === 'order-detail') {
            console.log('🔵 [MODAL] Evento close-modal recibido');
            window.closeOrderDetailModal();
        }
    });
    
    // Cerrar modal con ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const overlay = document.getElementById('modal-overlay');
            if (overlay && overlay.style.display !== 'none') {
                console.log('🔵 [MODAL] ESC presionado, cerrando modal');
                window.closeOrderDetailModal();
            }
        }
    });
    
    console.log('✅ [MODAL] Listeners registrados');
});
