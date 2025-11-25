/**
 * Order Detail Modal Management for Pedidos
 * Handles opening, closing, and overlay management for the order detail modal
 */

/**
 * Abre el modal de detalle de la orden
 * @param {number} numeroPedido - Número del pedido
 */
function verFactura(numeroPedido) {
    console.log('Abriendo modal de detalle para pedido:', numeroPedido);
    
    // Mostrar overlay
    const overlay = document.getElementById('modal-overlay');
    if (overlay) {
        overlay.style.display = 'block';
    }
    
    // Disparar evento para abrir el modal usando Alpine.js
    const event = new CustomEvent('open-modal', { detail: 'order-detail' });
    window.dispatchEvent(event);
}

/**
 * Cierra el modal de detalle y el overlay
 */
function closeModalOverlay() {
    const overlay = document.getElementById('modal-overlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
    
    // Cerrar el modal
    const closeEvent = new CustomEvent('close-modal', { detail: 'order-detail' });
    window.dispatchEvent(closeEvent);
}

// Escuchar evento de cierre del modal desde Alpine.js
window.addEventListener('close-modal', function(event) {
    if (event.detail === 'order-detail') {
        closeModalOverlay();
    }
});

// Cerrar modal al presionar Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const overlay = document.getElementById('modal-overlay');
        if (overlay && overlay.style.display === 'block') {
            closeModalOverlay();
        }
    }
});

// Cerrar modal al hacer clic fuera (en el overlay)
document.addEventListener('click', function(event) {
    const overlay = document.getElementById('modal-overlay');
    const modalContainer = document.querySelector('div[style*="max-width: 672px"]');
    
    // Si se hace clic en el overlay y no en el modal
    if (overlay && overlay.style.display === 'block' && event.target === overlay) {
        closeModalOverlay();
    }
});
