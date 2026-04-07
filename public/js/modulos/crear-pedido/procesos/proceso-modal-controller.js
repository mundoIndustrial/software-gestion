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

    global.ProcesoModalController = {
        state,
        modules,
        abrir: global.abrirModalProcesoGenerico,
        cerrar: global.cerrarModalProcesoGenerico,
        guardarProceso: global.agregarProcesoAlPedido,
        imagenes: {
            manejar: global.manejarImagenProceso,
            eliminar: global.eliminarImagenProceso,
            confirmarEliminacion: global.confirmarEliminarImagenProceso,
            limpiar: global.limpiarImagenesProceso
        },
        tallas: {
            guardarSeleccion: global.guardarTallasSeleccionadas,
            actualizarResumen: global.actualizarResumenTallasProceso,
            abrirEditor: global.abrirEditorTallasEspecificas,
            cerrarEditor: global.cerrarEditorTallas
        },
        buffer: {
            aplicar: global.aplicarCambiosProcesosDesdeBuffer,
            obtener: global.obtenerBufferProcesoActual
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
