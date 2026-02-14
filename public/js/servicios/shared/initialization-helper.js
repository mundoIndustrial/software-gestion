/**
 *  Helper para inicialización de servicios compartidos
 * Facilita la integración en diferentes contextos
 */

class PrendasEditorHelper {
    static async inicializar() {
        try {
            const container = window.prendasServiceContainer;
            if (!container) {
                throw new Error('PrendaServiceContainer no cargado');
            }

            await container.initialize();
            window.editorPrendas = container.getService('editor');
            console.log(' Servicios de edición de prendas inicializados');
            
            return window.editorPrendas;
        } catch (error) {
            console.error(' Error inicializando servicios:', error);
            throw error;
        }
    }

    /**
     * Abrir editor para crear-nuevo
     */
    static async abrirCrearNueva(opcionesExtra = {}) {
        const editor = window.editorPrendas;
        if (!editor) {
            throw new Error('Editor no inicializado. Llama a PrendasEditorHelper.inicializar() primero');
        }

        return editor.abrirEditor({
            modo: 'crear',
            contexto: 'crear-nuevo',
            prendaLocal: undefined,
            ...opcionesExtra
        });
    }

    /**
     * Abrir editor para editar un pedido existente
     */
    static async abrirEditar(prendaId, opcionesExtra = {}) {
        const editor = window.editorPrendas;
        if (!editor) {
            throw new Error('Editor no inicializado');
        }

        return editor.abrirEditor({
            modo: 'editar',
            contexto: 'pedidos-editable',
            prendaId,
            ...opcionesExtra
        });
    }

    /**
     * Abrir editor para crear desde cotización
     * IMPORTANTE: datosPrenda debe ser una COPIA, no referencia
     */
    static async abrirDesdeCotizacion(
        cotizacionId, 
        prendaCotizacionId,
        datosPrenda,
        opcionesExtra = {}
    ) {
        const editor = window.editorPrendas;
        if (!editor) {
            throw new Error('Editor no inicializado');
        }

        // Verificar que es una copia
        if (!datosPrenda || typeof datosPrenda !== 'object') {
            throw new Error('datosPrenda debe ser un objeto con datos de prenda');
        }

        return editor.abrirEditor({
            modo: 'crear',
            contexto: 'crear-desde-cotizacion',
            cotizacionId,
            prendaCotizacionId,
            prendaLocal: datosPrenda,  // DEBE ser copia
            origenCotizacion: {
                id: cotizacionId,
                numero: window.cotizacionActual?.numero,
                cliente: window.cotizacionActual?.cliente
            },
            ...opcionesExtra
        });
    }

    /**
     * Guardar la prenda actualmente en edición
     */
    static async guardar() {
        const editor = window.editorPrendas;
        if (!editor) {
            throw new Error('Editor no inicializado');
        }

        return editor.guardarCambios();
    }

    /**
     * Cancelar edición actual
     */
    static cancelar() {
        const editor = window.editorPrendas;
        if (!editor) {
            throw new Error('Editor no inicializado');
        }

        return editor.cancelarEdicion();
    }

    /**
     * Obtener estado actual del editor
     */
    static getEstado() {
        const editor = window.editorPrendas;
        if (!editor) {
            return { inicializado: false };
        }

        return {
            inicializado: true,
            estado: editor.getEstado()
        };
    }

    /**
     * Verificar si hay cambios pendientes
     */
    static hayChangios() {
        const editor = window.editorPrendas;
        if (!editor) return false;

        return editor.hayChangios();
    }

    /**
     * Suscribirse a eventos del editor
     */
    static on(eventName, callback) {
        const container = window.prendasServiceContainer;
        if (!container) {
            console.warn('Service container no disponible');
            return;
        }

        const eventBus = container.getService('eventBus');
        if (eventBus) {
            eventBus.on(eventName, callback);
        }
    }

    /**
     * Suscribirse a evento una sola vez
     */
    static once(eventName, callback) {
        const container = window.prendasServiceContainer;
        if (!container) {
            console.warn('Service container no disponible');
            return;
        }

        const eventBus = container.getService('eventBus');
        if (eventBus) {
            eventBus.once(eventName, callback);
        }
    }

    /**
     * Habilitar/deshabilitar debug
     */
    static setDebug(enabled = true) {
        const container = window.prendasServiceContainer;
        if (!container) {
            console.warn('Service container no disponible');
            return;
        }

        if (typeof container.setDebug === 'function') {
            container.setDebug(enabled);
        }
    }

    /**
     * Obtener estadísticas del servicio
     */
    static getStats() {
        const container = window.prendasServiceContainer;
        if (!container) {
            return null;
        }

        return container.getEstadisticas();
    }
}

// Exportar globalmente
window.PrendasEditorHelper = PrendasEditorHelper;

console.log('[PrendasEditorHelper] 🆗 Cargado y disponible globalmente');
