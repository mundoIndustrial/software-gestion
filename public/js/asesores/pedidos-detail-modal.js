/**
 * Order Detail Modal Management for Pedidos
 * Handles opening, closing, and overlay management for the order detail modal
 */

console.log('📄 [MODAL] Cargando pedidos-detail-modal.js');

/**
 * Abre el modal de detalle de la orden y carga los datos
 * @param {number} numeroPedido - Número del pedido
 */
window.verFactura = async function verFactura(numeroPedido) {
    console.log('🔵 [MODAL] Abriendo modal de factura para pedido:', numeroPedido);
    
    try {
        // ✅ HACER FETCH a la API para obtener datos del pedido
        // Intentar primero con /registros (para asesores), luego con /orders (para órdenes)
        console.log('🔵 [MODAL] Haciendo fetch a /registros/' + numeroPedido);
        let response = await fetch(`/registros/${numeroPedido}`);
        
        // Si no encuentra en /registros, intentar con /orders
        if (!response.ok) {
            console.log('🔵 [MODAL] No encontrado en /registros, intentando /orders/' + numeroPedido);
            response = await fetch(`/orders/${numeroPedido}`);
        }
        
        if (!response.ok) {
            console.error('❌ [MODAL] Error en respuesta:', response.status, response.statusText);
            throw new Error('Error fetching order: ' + response.status);
        }
        const order = await response.json();
        
        console.log('✅ [MODAL] Datos del pedido obtenidos:', order);
        console.log('✅ [MODAL] Campos disponibles:', Object.keys(order));
        console.log('✅ [MODAL] prendas:', order.prendas);
        console.log('✅ [MODAL] es_cotizacion:', order.es_cotizacion);
        
        // Disparar evento para que order-detail-modal-manager.js maneje la apertura
        console.log('🔵 [MODAL] Disparando evento load-order-detail');
        const loadEvent = new CustomEvent('load-order-detail', { 
            detail: order 
        });
        window.dispatchEvent(loadEvent);
        
    } catch (error) {
        console.error('❌ Error al cargar datos del pedido:', error);
        alert('Error al cargar los datos del pedido. Intenta nuevamente.');
    }
}

/**
 * Abre el modal de seguimiento del pedido (ASESORAS - VERSIÓN SIMPLIFICADA)
 * @param {number} numeroPedido - Número del pedido
 */
window.verSeguimiento = function verSeguimiento(numeroPedido) {
    console.log('🔵 [ASESORAS] Abriendo modal de seguimiento simplificado para pedido:', numeroPedido);
    
    // Usar la función simplificada para asesoras
    if (typeof openAsesorasTrackingModal === 'function') {
        openAsesorasTrackingModal(numeroPedido);
        console.log('✅ [ASESORAS] Modal de seguimiento abierto');
    } else {
        console.error('❌ [ASESORAS] Función openAsesorasTrackingModal no disponible');
        alert('Error: No se puede abrir el seguimiento. Intenta nuevamente.');
    }
}

/**
 * Cierra el modal de detalle y el overlay
 */
window.closeModalOverlay = function closeModalOverlay() {
    const overlay = document.getElementById('modal-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
    
    const modalWrapper = document.getElementById('order-detail-modal-wrapper');
    if (modalWrapper) {
        modalWrapper.style.display = 'none';
    }
    
    // ✅ Recargar filtros desde localStorage al cerrar modal
    if (typeof loadFiltersFromLocalStorage === 'function') {
        loadFiltersFromLocalStorage();
        console.log('✅ Filtros recargados después de cerrar modal');
        if (typeof applyTableFilters === 'function') {
            applyTableFilters();
            console.log('✅ Filtros reaplicados a la tabla');
        }
    }
    
    // Notificar que el modal se cerró (sin causar recursión)
    const closeEvent = new CustomEvent('modal-closed', { detail: 'order-detail' });
    window.dispatchEvent(closeEvent);
}

// Cerrar modal al presionar Escape
document.addEventListener('keydown', function(keyEvent) {
    if (keyEvent.key === 'Escape') {
        const overlay = document.getElementById('modal-overlay');
        if (overlay && overlay.style.display === 'block') {
            window.closeModalOverlay();
        }
    }
});

// Cerrar modal al hacer clic fuera (en el overlay)
document.addEventListener('click', function(clickEvent) {
    const overlay = document.getElementById('modal-overlay');
    const modalContainer = document.querySelector('div[style*="max-width: 672px"]');
    
    // Si se hace clic en el overlay y no en el modal
    if (overlay && overlay.style.display === 'block' && clickEvent.target === overlay) {
        window.closeModalOverlay();
    }
});

