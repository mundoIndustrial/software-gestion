/**
 * proceso-modal-controller.js
 * API publica unificada para el modal de procesos.
 */
(function initProcesoModalController(global) {
    const state = global.procesoModalState;
    const modules = global.procesoModalModules;

    if (!state || !modules) {
        console.warn('[ProcesoModalController] State/modules no disponibles. Verifica orden de scripts.');
        return;
    }

    function callGlobal(fnName, ...args) {
        const fn = global[fnName];
        if (typeof fn !== 'function') {
            return undefined;
        }
        return fn(...args);
    }

    global.ProcesoModalController = {
        state,
        modules,
        abrir: (...args) => callGlobal('abrirModalProcesoGenerico', ...args),
        cerrar: (...args) => callGlobal('cerrarModalProcesoGenerico', ...args),
        guardarProceso: (...args) => callGlobal('agregarProcesoAlPedido', ...args),
        imagenes: {
            manejar: (...args) => callGlobal('manejarImagenProceso', ...args),
            eliminar: (...args) => callGlobal('eliminarImagenProceso', ...args),
            confirmarEliminacion: (...args) => callGlobal('confirmarEliminarImagenProceso', ...args),
            limpiar: (...args) => callGlobal('limpiarImagenesProceso', ...args)
        },
        tallas: {
            guardarSeleccion: (...args) => callGlobal('guardarTallasSeleccionadas', ...args),
            actualizarResumen: (...args) => callGlobal('actualizarResumenTallasProceso', ...args),
            abrirEditor: (...args) => callGlobal('abrirEditorTallasEspecificas', ...args),
            cerrarEditor: (...args) => callGlobal('cerrarEditorTallas', ...args)
        },
        buffer: {
            aplicar: (...args) => callGlobal('aplicarCambiosProcesosDesdeBuffer', ...args),
            obtener: (...args) => callGlobal('obtenerBufferProcesoActual', ...args)
        },
        debug: {
            enabled() {
                return global.PROCESO_MODAL_DEBUG;
            },
            setEnabled(value) {
                global.PROCESO_MODAL_DEBUG = Boolean(value);
                return global.PROCESO_MODAL_DEBUG;
            }
        }
    };
})(globalThis);
