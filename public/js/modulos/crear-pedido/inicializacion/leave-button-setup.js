/**
 * LEAVE BUTTON SETUP
 * ═══════════════════════════════════════════════════════════════
 * Handles visibility and text setup for action buttons (submit, draft save, cancel)
 * 
 * Functionality:
 * - Shows submit button with proper text and styling
 * - Manages button state transitions
 * - Provides visual feedback for user interactions
 * 
 * Global Functions Exposed:
 * - window.InitializeLeaveButtons() - Setup button visibility and text
 */

(function() {
    'use strict';

    /**
     * Initialize action buttons (submit, draft save) visibility
     */
    window.InitializeLeaveButtons = function() {
        console.log('[leave-button-setup] Inicializando botones de acción...');
        
        const btnSubmit = document.getElementById('btn-submit');
        const btnGuardarBorrador = document.getElementById('btn-guardar-borrador');
        
        if (btnSubmit) {
            btnSubmit.textContent = '✓ Crear Pedido';
            btnSubmit.style.display = 'block';
            console.log('[leave-button-setup] Botón submit inicializado ✓');
        } else {
            console.warn('⚠️ Botón submit no encontrado');
        }
        
        if (btnGuardarBorrador) {
            console.log('[leave-button-setup] Botón guardar borrador disponible ✓');
        }
    };

})();
