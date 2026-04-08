/**
 * proceso-modal-state.js
 * Estado y namespaces compartidos del modal de procesos.
 */
(function initProcesoModalState(global) {
    if (!global.procesoModalState) {
        global.procesoModalState = {
            procesoActual: null,
            modoActual: 'crear',
            cambiosProceso: null
        };
    }

    if (typeof global.PROCESO_MODAL_DEBUG !== 'boolean') {
        global.PROCESO_MODAL_DEBUG = false;
    }

    if (typeof global.procesoModalDebug !== 'function') {
        global.procesoModalDebug = function procesoModalDebug(...args) {
            if (!global.PROCESO_MODAL_DEBUG) {
                return;
            }
            console.info(...args);
        };
    }

    if (!global.procesoModalModules) {
        global.procesoModalModules = {
            ui: {},
            imagenes: {},
            persistencia: {},
            tallas: {}
        };
    }
})(globalThis);
