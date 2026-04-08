/**
 * ITEM TYPE HANDLERS
 * ═══════════════════════════════════════════════════════════════
 * Manages event listeners for item type selection and addition
 * 
 * Functionality:
 * - Handles "Agregar Ítem" button click events
 * - Routes to appropriate modal based on item type (Prenda/EPP)
 * - Provides visual feedback during loading
 * - Validates item type selection before opening modals
 * - Manages type change events and button visibility
 * 
 * Dependencies:
 * - window.abrirModalPrendaNueva() - From prenda modal module
 * - window.abrirModalAgregarEPP() - From EPP modal module
 * 
 * Global Functions Exposed:
 * - window.InitializeItemTypeHandlers() - Setup all event listeners
 * - window.manejarCambiaTipoPedido() - Handler for type change (used inline)
 */

(function() {
    'use strict';

    /**
     * Initialize event handlers for item type selection and addition
     */
    window.InitializeItemTypeHandlers = function() {
        console.log('[item-type-handlers] Inicializando manejadores de tipos de ítem...');
        
        const selectTipoPedidoNuevo = document.getElementById('tipo_pedido_nuevo');
        const btnAgregarItemTipoInline = document.getElementById('btn-agregar-item-tipo-inline');
        
        if (!btnAgregarItemTipoInline) {
            console.warn(' Botón agregar ítem no encontrado');
            return;
        }
        
        // Agregar ítem de tipo nuevo desde el botón inline
        btnAgregarItemTipoInline.addEventListener('click', function(e) {
            e.preventDefault();
            const tipoPedido = selectTipoPedidoNuevo.value;
            
            if (!tipoPedido) {
                Swal.fire({
                    icon: 'warning',
                    title: ' Tipo de Ítem Requerido',
                    text: 'Por favor selecciona un ítem primero',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#0066cc'
                });
                return;
            }
            
            // Feedback visual: deshabilitar y mostrar estado cargando
            btnAgregarItemTipoInline.disabled = true;
            btnAgregarItemTipoInline.style.opacity = '0.6';
            const textoOriginal = btnAgregarItemTipoInline.innerHTML;
            btnAgregarItemTipoInline.innerHTML = '<span class="material-symbols-rounded" style="font-size: 1.25rem; animation: spin 0.8s linear infinite;">refresh</span>';
            
            // Auto-habilitar después de 600ms
            setTimeout(() => {
                btnAgregarItemTipoInline.disabled = false;
                btnAgregarItemTipoInline.style.opacity = '1';
                btnAgregarItemTipoInline.innerHTML = textoOriginal;
            }, 600);
            
            // Manejar diferentes tipos de pedido
            if (tipoPedido === 'P') {
                console.log('[item-type-handlers] Abriendo modal de prenda nueva...');
                window.abrirModalPrendaNueva();
            } else if (tipoPedido === 'EPP') {
                console.log('[item-type-handlers] Abriendo modal de EPP...');
                window.abrirModalAgregarEPP();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: ' Tipo Desconocido',
                    text: 'Tipo de pedido "' + tipoPedido + '" desconocido',
                    confirmButtonText: 'Aceptar',
                    confirmButtonColor: '#ef4444'
                });
            }
        });
        
        console.log('[item-type-handlers] Manejadores de tipos de ítem inicializados ✓');
    };

    /**
     * Handle changes in item type dropdown
     * Shows the add button when a type is selected
     */
    window.manejarCambiaTipoPedido = function() {
        console.log('[item-type-handlers] Tipo de pedido cambiado');
        const selectTipoPedidoNuevo = document.getElementById('tipo_pedido_nuevo');
        const tipoPedido = selectTipoPedidoNuevo.value;
        
        if (!tipoPedido) return;
        
        const btnAgregarTipoInline = document.getElementById('btn-agregar-item-tipo-inline');
        if (btnAgregarTipoInline) {
            btnAgregarTipoInline.style.display = 'flex';
            console.log('[item-type-handlers] Botón agregar mostrado ✓');
        }
    };

})();
