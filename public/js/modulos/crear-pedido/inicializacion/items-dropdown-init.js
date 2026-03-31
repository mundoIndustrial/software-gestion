/**
 * ITEMS DROPDOWN INITIALIZATION
 * ═══════════════════════════════════════════════════════════════
 * Handles initialization of item type selector and items section visibility
 * 
 * Functionality:
 * - Hides loading spinner
 * - Shows type pedido dropdown selector
 * - Displays items section container
 * - Manages section visibility with staggered timing
 * 
 * Global Functions Exposed:
 * - window.InitializeItemsDropdown() - Setup dropdown and items section
 */

(function() {
    'use strict';

    /**
     * Initialize item type selector dropdown
     */
    window.InitializeItemsDropdown = function() {
        console.log('[items-dropdown-init] Inicializando dropdown de tipos de ítem...');
        
        // ========== OCULTAR LOADING Y MOSTRAR SELECT DE TIPO DE PEDIDO ==========
        const tipoPedidoLoading = document.getElementById('tipo-pedido-loading');
        const tipoPedidoSelect = document.getElementById('tipo_pedido_nuevo');
        
        if (tipoPedidoLoading && tipoPedidoSelect) {
            setTimeout(() => {
                tipoPedidoLoading.style.display = 'none';
                tipoPedidoSelect.style.display = 'block';
                tipoPedidoSelect.removeAttribute('disabled');
                console.log('[items-dropdown-init] Select de tipo mostrado ✓');
            }, 500);
        } else {
            console.warn(' Loading spinner o select no encontrados');
        }

        // ========== MOSTRAR SECCIÓN DE ÍTEMS ==========
        const seccionItems = document.getElementById('seccion-items-pedido');
        if (seccionItems) {
            seccionItems.style.display = 'block';
            console.log('[items-dropdown-init] Sección de ítems mostrada ✓');
        } else {
            console.warn(' Sección de ítems no encontrada');
        }
    };

})();
