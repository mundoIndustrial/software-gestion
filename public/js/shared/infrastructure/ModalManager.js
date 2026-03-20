/**
 * =====================================================
 * SHARED INFRASTRUCTURE - MODAL MANAGER
 * =====================================================
 * Centraliza apertura/cierre de modales estilo display:flex.
 * Elimina duplicación de `style.display = 'flex'` en ~40 archivos.
 *
 * Uso:
 *   SharedModal.open('modalAnulacion');
 *   SharedModal.close('modalAnulacion');
 *   SharedModal.setupOverlayClose('modalNovedades');
 */

const SharedModal = (() => {
    'use strict';

    function open(id) {
        const el = typeof id === 'string' ? document.getElementById(id) : id;
        if (!el) return;
        el.style.display = 'flex';
        el.style.alignItems = 'center';
        el.style.justifyContent = 'center';
    }

    function close(id) {
        const el = typeof id === 'string' ? document.getElementById(id) : id;
        if (!el) return;
        el.style.display = 'none';
    }

    function toggle(id) {
        const el = typeof id === 'string' ? document.getElementById(id) : id;
        if (!el) return;
        if (el.style.display === 'none' || el.style.display === '') {
            open(el);
        } else {
            close(el);
        }
    }

    function isOpen(id) {
        const el = typeof id === 'string' ? document.getElementById(id) : id;
        return el ? el.style.display !== 'none' && el.style.display !== '' : false;
    }

    /**
     * Cierra el modal al hacer click en el overlay (el propio modal-wrapper).
     */
    function setupOverlayClose(id) {
        const el = typeof id === 'string' ? document.getElementById(id) : id;
        if (!el) return;
        el.addEventListener('click', function(e) {
            if (e.target === el) close(el);
        });
    }

    return { open, close, toggle, isOpen, setupOverlayClose };
})();

window.SharedModal = SharedModal;
