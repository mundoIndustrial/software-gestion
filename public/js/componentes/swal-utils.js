/**
 * Utilidades compartidas de SweetAlert para overlays por encima de modales.
 * Expone: window.PedidosSwalUtils
 */
(function() {
    'use strict';

    function ensureOverlayStyle(styleId, containerClass, zIndex) {
        const z = Number.isFinite(zIndex) ? zIndex : 2000000;
        let styleEl = document.getElementById(styleId);
        if (!styleEl) {
            styleEl = document.createElement('style');
            styleEl.id = styleId;
            document.head.appendChild(styleEl);
        }

        styleEl.textContent = `
            .${containerClass} {
                z-index: ${z} !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 100% !important;
            }
            .${containerClass} .swal2-popup {
                margin: auto !important;
            }
        `;
    }

    function centerOverlay(modal, zIndex) {
        const container = modal?.closest?.('.swal2-container');
        if (!container) return;
        container.style.display = 'flex';
        container.style.alignItems = 'center';
        container.style.justifyContent = 'center';
        container.style.height = '100vh';
        container.style.zIndex = String(Number.isFinite(zIndex) ? zIndex : 2000000);
    }

    window.PedidosSwalUtils = {
        ensureOverlayStyle,
        centerOverlay
    };
})();

