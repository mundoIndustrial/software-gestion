const hasOrderDetailMobile = () =>
    !!document.getElementById('factura-container-mobile') ||
    !!document.querySelector('.order-detail-modal-container--mobile-full');

if (hasOrderDetailMobile()) {
    Promise.all([
        import('./order-detail-mobile-formatters.js'),
        import('./order-detail-mobile-service.js'),
        import('./order-detail-mobile-gallery.js'),
        import('./order-detail-mobile-dynamic-loader.js'),
        import('./order-detail-mobile-observations.js'),
        import('./order-detail-mobile-receipt.js'),
    ]).catch((error) => {
        console.error('[order-detail-mobile] Error loading modules:', error);
    });
}
