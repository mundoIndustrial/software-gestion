/**
 * ================================================
 * MODAL WRAPPERS
 * ================================================
 * 
 * Funciones proxy que delegan a los módulos especializados (GestionItemsUI, etc.)
 * Mantiene compatibilidad hacia atrás sin duplicar lógica
 * 
 * @module ModalWrappers
 */

// Flag global para debounce - evita múltiples aperturas rápidas
window.__modalPrendaAbriendo = false;

/**
 * WRAPPER: Abre el modal para agregar una prenda nueva
 * Delega a GestionItemsUI.abrirModalAgregarPrendaNueva()
 * 
 * Incluye debouncing para prevenir múltiples clics rápidos
 */
window.abrirModalPrendaNueva = function() {
    // 🛡️ Guard: Evitar múltiples aperturas simultáneas
    if (window.__modalPrendaAbriendo) {
        console.debug('[abrirModalPrendaNueva] 🚫 Debounced - modal ya está abriéndose');
        return;
    }
    
    // Marcar como que está abriendo
    window.__modalPrendaAbriendo = true;
    
    // Auto-reset después de 500ms (tiempo de animación + buffer)
    setTimeout(() => {
        window.__modalPrendaAbriendo = false;
    }, 500);
    
    // Intentar usar GestionItemsUI si existe
    if (window.gestionItemsUI && typeof window.gestionItemsUI.abrirModalAgregarPrendaNueva === 'function') {
        return window.gestionItemsUI.abrirModalAgregarPrendaNueva();
    }
    
    // Fallback: abrir el modal directamente si existe
    const modal = document.getElementById('modal-agregar-prenda-nueva');
    if (modal) {
        //  Asegurar que estamos en modo CREATE (prendaEditIndex = null)
        if (window.gestionItemsUI) {
            window.gestionItemsUI.prendaEditIndex = null;
        }
        window.prendaEditIndex = null;
        
        //  Limpiar telas residuales ANTES de abrir el modal
        if (window.telasAgregadas) {
            window.telasAgregadas = [];
        }
        if (window.telasCreacion) {
            window.telasCreacion = [];
        }
        const tbodyTelas = document.getElementById('tbody-telas');
        if (tbodyTelas) {
            tbodyTelas.innerHTML = '';
        }

        // Usar UIModalService para manejar el scroll del body
        if (window.UI && typeof window.UI.abrirModal === 'function') {
            window.UI.abrirModal('modal-agregar-prenda-nueva', {
                display: 'flex',
                closeOnClickOutside: false,
                closeOnEsc: true,
                preventScroll: true
            });
        } else {
            // Fallback si UIModalService no está disponible
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        // Limpiar formulario
        limpiarFormulario();
    }
};

/**
 * WRAPPER: Cierra el modal de prenda nueva
 * Delega a GestionItemsUI.cerrarModalAgregarPrendaNueva()
 */
window.cerrarModalPrendaNueva = function() {
    //  CRÍTICO: Resetear prendaEditIndex PRIMERO para evitar confundir CREATE con EDIT
    if (window.gestionItemsUI) {
        window.gestionItemsUI.prendaEditIndex = null;
    }
    window.prendaEditIndex = null;
    
    // Intentar usar GestionItemsUI si existe
    if (window.gestionItemsUI && typeof window.gestionItemsUI.cerrarModalAgregarPrendaNueva === 'function') {
        return window.gestionItemsUI.cerrarModalAgregarPrendaNueva();
    }
    
    // Fallback: cerrar el modal directamente si existe
    const modal = document.getElementById('modal-agregar-prenda-nueva');
    if (modal) {
        // Usar UIModalService para manejar el scroll del body
        if (window.UI && typeof window.UI.cerrarModal === 'function') {
            window.UI.cerrarModal('modal-agregar-prenda-nueva', {
                animate: false
            });
        } else {
            // Fallback si UIModalService no está disponible
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    }
};

/**
 * WRAPPER: Agrega una prenda nueva al pedido
 * Delega a GestionItemsUI.agregarPrendaNueva()
 */
window.agregarPrendaNueva = function() {
    // Intentar usar GestionItemsUI si existe
    if (window.gestionItemsUI && typeof window.gestionItemsUI.agregarPrendaNueva === 'function') {
        return window.gestionItemsUI.agregarPrendaNueva();
    }
    
    // Fallback: implementación básica
    console.warn('GestionItemsUI no disponible, usando fallback para agregarPrendaNueva');
    return null;
};

/**
 * WRAPPER: Carga un item en el modal para editar
 * Delega a GestionItemsUI.cargarItemEnModal()
 */
window.cargarItemEnModal = function(item, itemIndex) {
    // Intentar usar GestionItemsUI si existe
    if (window.gestionItemsUI && typeof window.gestionItemsUI.cargarItemEnModal === 'function') {
        return window.gestionItemsUI.cargarItemEnModal(item, itemIndex);
    }
    
    // Fallback: implementación básica
    console.warn('GestionItemsUI no disponible, usando fallback para cargarItemEnModal');
    return null;
};

/**
 * WRAPPER: Abre el selector de archivos para agregar foto a prenda
 */
window.abrirSelectorPrendas = function() {
    const inputFotos = document.getElementById('nueva-prenda-foto-input');
    if (inputFotos) {
        inputFotos.click();
    }
}
