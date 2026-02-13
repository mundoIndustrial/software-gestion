/**
 * 🎯 ORQUESTADOR - PrendaEditor (Simplificado)
 * 
 * Responsabilidad: Coordinar flujos de edición
 * - Abrición/cierre de modal
 * - Carga de datos guardados
 * - Delegación a loaders especializados
 */

// ⚠️ Permitir redeclaración (se puede cargar múltiples veces desde diferentes Blades)
if (typeof window.PrendaEditor !== 'undefined') {
    delete window.PrendaEditor;
}

class PrendaEditor {
    constructor(options = {}) {
        this.modalId = options.modalId || 'modal-agregar-prenda-nueva';
        this.prendaEditIndex = null;
        this.cotizacionActual = options.cotizacionActual || null;
    }

    /**
     * 🔓 ABRE MODAL (NEW o EDIT)
     */
    abrirModal(esEdicion = false, prendaIndex = null, cotizacionSeleccionada = null) {
        this.prendaEditIndex = esEdicion && prendaIndex !== null ? prendaIndex : null;
        if (cotizacionSeleccionada) this.cotizacionActual = cotizacionSeleccionada;

        if (typeof PrendaModalManager !== 'undefined') {
            try {
                PrendaModalManager.abrir(this.modalId);
                PrendaModalManager.actualizarTitulo(esEdicion, this.modalId);
            } catch (error) {
                console.error('[🔓 abrirModal] Error:', error);
            }
        }
    }

    /**
     * 🔙 CIERRA MODAL
     */
    cerrarModal() {
        if (typeof PrendaModalManager !== 'undefined') {
            try {
                PrendaModalManager.cerrar(this.modalId);
                PrendaModalManager.limpiar(this.modalId);
            } catch (error) {
                console.error('[🔙 cerrarModal] Error:', error);
            }
        }
    }

    /**
     * 📋 CARGA PRENDA EN MODAL
     */
    async cargarPrendaEnModal(prenda, prendaIndex) {
        console.log('📋 [PrendaEditor] Cargando prenda:', prenda.nombre_prenda || prenda.nombre);

        try {
            // 1️⃣ Guardar en global
            window.prendaActual = prenda;
            this.prendaEditIndex = prendaIndex;

            // 2️⃣ Abrir modal
            this.abrirModal(true, prendaIndex);

            // 3️⃣ Esperar a que sea visible
            if (typeof PrendaModalManager !== 'undefined') {
                await PrendaModalManager.esperarVisible(this.modalId);
            } else {
                await new Promise(resolve => setTimeout(resolve, 150));
            }

            // 4️⃣ Obtener datos completos
            let prendaCompleta = prenda;
            if (typeof PrendaEditorService !== 'undefined') {
                prendaCompleta = await PrendaEditorService.obtenerConFallback(prenda);
            }

            // 5️⃣ Normalizar telas si es necesario
            prendaCompleta = this._normalizarTelas(prendaCompleta);

            // 6️⃣ Cargar datos en formulario
            this._cargarDatosEnFormulario(prendaCompleta);

            // 7️⃣ Cambiar botón
            if (typeof PrendaModalManager !== 'undefined') {
                PrendaModalManager.cambiarBotonAGuardarCambios(this.modalId);
            }

            console.log('✅ [PrendaEditor] Prenda cargada');
        } catch (error) {
            console.error('❌ [PrendaEditor]', error);
            if (typeof PrendaModalManager !== 'undefined') {
                PrendaModalManager.mostrarError(`Error: ${error.message}`);
            }
        }
    }

    /**
     * 📊 NORMALIZAR TELAS
     * @private
     */
    _normalizarTelas(prenda) {
        if (!prenda) return {};

        if (prenda.telasAgregadas) {
            if (Array.isArray(prenda.telasAgregadas)) {
                return prenda;
            }
            if (typeof prenda.telasAgregadas === 'object') {
                prenda.telasAgregadas = Object.values(prenda.telasAgregadas);
                return prenda;
            }
        }

        if (Array.isArray(prenda.telas)) {
            prenda.telasAgregadas = prenda.telas;
            return prenda;
        }

        prenda.telasAgregadas = [];
        return prenda;
    }

    /**
     * 🔄 CARGAR DATOS EN FORMULARIO
     * Llama a cada loader para cargar su parte
     * @private
     */
    _cargarDatosEnFormulario(prenda) {
        console.log('[🔄 Carga] Cargando datos en formulario...');

        // Basicos
        if (typeof PrendaEditorBasicos !== 'undefined') {
            PrendaEditorBasicos.cargar(prenda);
        }

        // Imágenes
        if (typeof PrendaEditorImagenes !== 'undefined') {
            PrendaEditorImagenes.cargar(prenda);
        }

        // Telas
        if (typeof PrendaEditorTelas !== 'undefined') {
            PrendaEditorTelas.cargar(prenda);
            // Replicar a global para edicion
            if (prenda.telasAgregadas && Array.isArray(prenda.telasAgregadas)) {
                window.telasCreacion = JSON.parse(JSON.stringify(prenda.telasAgregadas));
            }
        }

        // Variaciones
        if (typeof PrendaEditorVariaciones !== 'undefined') {
            PrendaEditorVariaciones.cargar(prenda);
        }

        // Tallas
        if (typeof PrendaEditorTallas !== 'undefined') {
            PrendaEditorTallas.cargar(prenda);
            PrendaEditorTallas.marcarGeneros(prenda);
            // Replicar a global para edicion
            if (prenda.cantidad_talla || prenda.tallasRelacionales) {
                const tallas = prenda.cantidad_talla || prenda.tallasRelacionales;
                window.tallasRelacionales = JSON.parse(JSON.stringify(tallas));
            }
        }

        // Colores
        if (typeof PrendaEditorColores !== 'undefined') {
            PrendaEditorColores.cargar(prenda);
        }

        // Procesos
        if (typeof PrendaEditorProcesos !== 'undefined') {
            PrendaEditorProcesos.cargar(prenda);
        }

        console.log('✅ [🔄 Carga] Datos cargados en formulario');
    }

    resetearEdicion() {
        this.prendaEditIndex = null;
        this.cerrarModal();
    }
}

// Asignar al window para que esté disponible globalmente
window.PrendaEditor = PrendaEditor;

// Exportar para módulos (si aplica)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PrendaEditor;
}
