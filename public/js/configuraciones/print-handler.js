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
        console.log('[PRINT] beforeprint: ocultando elementos flotantes');
        
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
        
        console.log('[PRINT] ' + hiddenElements.length + ' elementos ocultados');
    }

    /**
     * Restaurar elementos después de imprimir
     */
    function showPrintElements() {
        console.log('[PRINT] afterprint: restaurando elementos');
        
        hiddenElements.forEach(state => {
            state.element.style.display = state.originalDisplay || '';
            state.element.style.visibility = state.originalVisibility || '';
        });
        
        hiddenElements = [];
        console.log('[PRINT] elementos restaurados');
    }

    /**
     * Inicializar listeners
     */
    function init() {
        console.log('[PRINT] Print Handler inicializado');
        
        // Event listeners para impresión
        window.addEventListener('beforeprint', hidePrintElements);
        window.addEventListener('afterprint', showPrintElements);
        
        // Para navegadores que no usan beforeprint (fallback)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 'P')) {
                console.log('[PRINT] Ctrl+P detectado');
                // beforeprint se dispará automáticamente
            }
        });
        
        console.log('[PRINT] Event listeners registrados');
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
            console.log('[PRINT] Debug info:');
            console.log('  - Floating buttons container:', document.getElementById('floating-buttons-container'));
            console.log('  - Floating buttons container logo:', document.getElementById('floating-buttons-container-logo'));
            console.log('  - All hidden selectors:');
            HIDE_SELECTORS.forEach(sel => {
                const els = document.querySelectorAll(sel);
                console.log('    ' + sel + ':', els.length + ' encontrados');
            });
        },
        
        testPrint: function() {
            console.log('[PRINT] Ejecutando print()...');
            print();
        }
    };

})();

