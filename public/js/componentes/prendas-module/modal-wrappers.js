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
        // Resetear select de origen al placeholder
        const origenSelect = document.getElementById('nueva-prenda-origen-select');
        if (origenSelect) origenSelect.value = '';
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
    // Validar que se seleccionó origen antes de proceder
    const origenSelect = document.getElementById('nueva-prenda-origen-select');
    if (origenSelect && !origenSelect.value) {
        _mostrarModalOrigenRequerido();
        return;
    }

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

function _mostrarModalOrigenRequerido() {
    // Resaltar el select con borde rojo
    const select = document.getElementById('nueva-prenda-origen-select');
    if (select) {
        select.style.borderColor = '#ef4444';
        select.style.boxShadow = '0 0 0 3px rgba(239,68,68,0.2)';
        setTimeout(() => {
            select.style.borderColor = '';
            select.style.boxShadow = '';
        }, 3000);
    }

    // Crear overlay del modal
    const overlay = document.createElement('div');
    overlay.id = 'modal-origen-requerido-overlay';
    overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.55);z-index:9999999;display:flex;align-items:center;justify-content:center;';

    overlay.innerHTML = `
        <div style="background:white;border-radius:12px;padding:32px 28px;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.3);text-align:center;">
            <div style="font-size:48px;margin-bottom:12px;">⚠️</div>
            <h3 style="margin:0 0 10px;color:#1f2937;font-size:18px;font-weight:700;">Selecciona el origen de la prenda</h3>
            <p style="margin:0 0 24px;color:#6b7280;font-size:14px;line-height:1.6;">
                Debes indicar si la prenda se <strong>confecciona</strong> o si se <strong>saca de bodega</strong> antes de agregarla.
            </p>
            <button onclick="document.getElementById('modal-origen-requerido-overlay').remove(); document.getElementById('nueva-prenda-origen-select')?.focus();"
                style="background:#3b82f6;color:white;border:none;padding:10px 28px;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;">
                Entendido
            </button>
        </div>
    `;

    document.body.appendChild(overlay);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });
}

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
