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
globalThis.__modalPrendaAbriendo = false;

/**
 * WRAPPER: Abre el modal para agregar una prenda nueva
 * Delega a GestionItemsUI.abrirModalAgregarPrendaNueva()
 * 
 * Incluye debouncing para prevenir múltiples clics rápidos
 */
globalThis.abrirModalPrendaNueva = function() {
    // 🛡️ Guard: Evitar múltiples aperturas simultáneas
    if (globalThis.__modalPrendaAbriendo) {
        console.debug('[abrirModalPrendaNueva]  Debounced - modal ya está abriéndose');
        return;
    }
    
    // Marcar como que está abriendo
    globalThis.__modalPrendaAbriendo = true;
    
    // Auto-reset después de 500ms (tiempo de animación + buffer)
    setTimeout(() => {
        globalThis.__modalPrendaAbriendo = false;
    }, 500);
    
    // Intentar usar GestionItemsUI si existe
    if (globalThis.gestionItemsUI && typeof globalThis.gestionItemsUI.abrirModalAgregarPrendaNueva === 'function') {
        return globalThis.gestionItemsUI.abrirModalAgregarPrendaNueva();
    }
    
    // Fallback: abrir el modal directamente si existe
    const modal = document.getElementById('modal-agregar-prenda-nueva');
    if (modal) {
        //  Asegurar que estamos en modo CREATE (prendaEditIndex = null)
        if (globalThis.gestionItemsUI) {
            globalThis.gestionItemsUI.prendaEditIndex = null;
        }
        globalThis.prendaEditIndex = null;
        
        //  Limpiar telas residuales ANTES de abrir el modal
        if (globalThis.telasAgregadas) {
            globalThis.telasAgregadas = [];
        }
        if (globalThis.telasCreacion) {
            globalThis.telasCreacion = [];
        }
        const tbodyTelas = document.getElementById('tbody-telas');
        if (tbodyTelas) {
            // Preservar fila de inputs para agregar nuevas telas
            const filaInputs = tbodyTelas.querySelector('#nueva-prenda-tela')?.closest('tr');
            const filas = tbodyTelas.querySelectorAll('tr');
            filas.forEach(fila => {
                if (fila !== filaInputs) fila.remove();
            });
        }

        modal.style.display = 'flex';
        // Limpiar formulario
        limpiarFormulario();
    }
};

/**
 * WRAPPER: Cierra el modal de prenda nueva
 * Delega a GestionItemsUI.cerrarModalAgregarPrendaNueva()
 */
globalThis.cerrarModalPrendaNueva = function() {
    //  CRÍTICO: Resetear prendaEditIndex PRIMERO para evitar confundir CREATE con EDIT
    if (globalThis.gestionItemsUI) {
        globalThis.gestionItemsUI.prendaEditIndex = null;
    }
    globalThis.prendaEditIndex = null;
    
    // Intentar usar GestionItemsUI si existe
    if (globalThis.gestionItemsUI && typeof globalThis.gestionItemsUI.cerrarModalAgregarPrendaNueva === 'function') {
        return globalThis.gestionItemsUI.cerrarModalAgregarPrendaNueva();
    }
    
    // Fallback: cerrar el modal directamente si existe
    const modal = document.getElementById('modal-agregar-prenda-nueva');
    if (modal) {
        modal.style.display = 'none';
    }
};

/**
 * WRAPPER: Agrega una prenda nueva al pedido
 * Delega a GestionItemsUI.agregarPrendaNueva()
 */
globalThis.agregarPrendaNueva = function() {
    if (globalThis.__guardandoPrendaEnCurso) {
        console.debug('[agregarPrendaNueva] Bloqueado: ya hay un guardado en curso');
        return;
    }

    globalThis.__guardandoPrendaEnCurso = true;
    const btnGuardar = document.getElementById('btn-guardar-prenda');
    if (btnGuardar) {
        btnGuardar.disabled = true;
        btnGuardar.dataset.loading = 'true';
    }

    const liberarGuardado = () => {
        globalThis.__guardandoPrendaEnCurso = false;
        if (btnGuardar && btnGuardar.dataset.loading === 'true') {
            btnGuardar.disabled = false;
            delete btnGuardar.dataset.loading;
        }
    };

    // Intentar usar GestionItemsUI si existe
    if (globalThis.gestionItemsUI && typeof globalThis.gestionItemsUI.agregarPrendaNueva === 'function') {
        try {
            const resultado = globalThis.gestionItemsUI.agregarPrendaNueva();
            return Promise.resolve(resultado).finally(() => {
                // Pequeño delay para amortiguar dobles clicks muy rápidos
                setTimeout(liberarGuardado, 120);
            });
        } catch (error) {
            liberarGuardado();
            throw error;
        }
    }
    
    // Fallback: implementación básica
    console.warn('GestionItemsUI no disponible, usando fallback para agregarPrendaNueva');
    liberarGuardado();
    return null;
};

/**
 * WRAPPER: Carga un item en el modal para editar
 * Delega a GestionItemsUI.cargarItemEnModal()
 */
globalThis.cargarItemEnModal = function(item, itemIndex) {
    // Intentar usar GestionItemsUI si existe
    if (globalThis.gestionItemsUI && typeof globalThis.gestionItemsUI.cargarItemEnModal === 'function') {
        return globalThis.gestionItemsUI.cargarItemEnModal(item, itemIndex);
    }
    
    // Fallback: implementación básica
    console.warn('GestionItemsUI no disponible, usando fallback para cargarItemEnModal');
    return null;
};

/**
 * WRAPPER: Abre el selector de archivos para agregar foto a prenda
 */
globalThis.abrirSelectorPrendas = function() {
    const inputFotos = document.getElementById('nueva-prenda-foto-input');
    if (inputFotos) {
        inputFotos.click();
    }
}
