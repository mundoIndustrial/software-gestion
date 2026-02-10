/**
 * Print Handler - Gestión centralizada de impresión
 * Oculta elementos flotantes antes de imprimir y los restaura después
 */

(function() {
    'use strict';

    // Elementos a ocultar en impresión
    const HIDE_SELECTORS = [
        '#floating-buttons-container',
        '#floating-buttons-container-logo',
        '#btn-factura',
        '#btn-galeria',
        '#btn-factura-logo',
        '#btn-galeria-logo'
    ];

    // Estado de elementos guardados
    let hiddenElements = [];

    /**
     * Ocultar elementos antes de imprimir
     */
    function hidePrintElements() {
        hiddenElements = [];
        
        HIDE_SELECTORS.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(el => {
                // Guardar estado original
                const state = {
                    element: el,
                    originalDisplay: el.style.display,
                    originalVisibility: el.style.visibility,
                    wasHidden: el.offsetParent === null
                };
                hiddenElements.push(state);
                
                // Ocultar elemento
                el.style.display = 'none !important';
                el.style.visibility = 'hidden !important';
            });
        });
        
        hiddenElements = hiddenElements.concat(Array.from(elements));
    }

    /**
     * Restaurar elementos después de imprimir
     */
    function showPrintElements() {
        hiddenElements.forEach(state => {
            state.element.style.display = state.originalDisplay || '';
            state.element.style.visibility = state.originalVisibility || '';
        });
        
        hiddenElements = [];
    }

    /**
     * Inicializar listeners
     */
    function init() {
        // Event listeners para impresión
        window.addEventListener('beforeprint', hidePrintElements);
        window.addEventListener('afterprint', showPrintElements);
        
        // Para navegadores que no usan beforeprint (fallback)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 'P')) {
                // beforeprint se dispará automáticamente
            }
        });
    }

    // Ejecutar cuando el DOM esté disponible
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Exponer métodos públicos para debugging
    window.PrintHandler = {
        debug: function() {
            // Debug info disponible en consola
        },
        
        testPrint: function() {
            print();
        }
    };

})();

