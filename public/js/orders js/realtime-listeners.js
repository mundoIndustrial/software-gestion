/**
 * Real-time updates script for orders
 * Handles Echo/WebSocket listeners for live order updates
 */

/**
 * Initialize real-time listeners for orders
 */
function initializeOrdenesRealtimeListeners() {
    console.log('=== ÓRDENES - Inicializando Echo para tiempo real ===');
    console.log('window.Echo disponible:', !!window.Echo);

    if (!window.Echo) {
        console.error('❌ Echo NO está disponible. Reintentando en 500ms...');
        setTimeout(initializeOrdenesRealtimeListeners, 500);
        return;
    }

    console.log('✅ Echo disponible. Suscribiendo al canal "ordenes"...');

    // Canal de Órdenes
    const ordenesChannel = window.Echo.channel('ordenes');

    ordenesChannel.subscribed(() => {
        console.log('✅ Suscrito al canal "ordenes"');
    });

    ordenesChannel.error((error) => {
        console.error('❌ Error en canal "ordenes":', error);
    });

    ordenesChannel.listen('OrdenUpdated', (e) => {
        console.log('🎉 Evento OrdenUpdated recibido!', e);
        
        // Llamar al método de la instancia de modernTable
        if (window.modernTable && typeof window.modernTable.handleOrdenUpdate === 'function') {
            window.modernTable.handleOrdenUpdate(e.orden, e.action);
        } else {
            console.warn('⚠️ modernTable no está disponible o no tiene el método handleOrdenUpdate');
        }
    });

    console.log('✅ Listener de órdenes configurado');
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(initializeOrdenesRealtimeListeners, 100);
    });
} else {
    setTimeout(initializeOrdenesRealtimeListeners, 100);
}
