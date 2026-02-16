/**
 * Bootstrap Modal Init
 * Inicializa y configura el modal del wizard
 */

(function() {
    'use strict';

    // Esperar a que el DOM esté listo y jQuery cargado
    function initializeModalWizard() {
        // 1. Verificar que el modal existe
        const modalElement = document.getElementById('modal-asignar-colores-por-talla');
        if (!modalElement) {
            return false;
        }

        // 2. Verificar que jQuery está disponible
        if (typeof jQuery === 'undefined') {
            return false;
        }

        // 3. Verificar que Bootstrap está disponible
        if (!jQuery.fn.modal) {
            return false;
        }

        // 4. Verificar que el botón de apertura existe
        const btnAsignarColores = document.getElementById('btn-asignar-colores-tallas');

        // 5. Inicializar el modal con Bootstrap
        try {
            // Crear instancia del modal
            const $modal = jQuery(modalElement);
            
            return true;

        } catch (error) {
            return false;
        }
    }

    // Ejecutar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                initializeModalWizard();
            }, 100);
        });
    } else {
        setTimeout(() => {
            initializeModalWizard();
        }, 100);
    }

    // Exportar función para debugging
    window.initializeModalWizard = initializeModalWizard;
})();
