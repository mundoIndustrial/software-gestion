/**
 * Servicio para orquestar apertura/cierre del modal de prenda.
 * Encapsula FSM, listeners y limpieza de UI para reducir complejidad en GestionItemsUI.
 */
class PrendaModalService {
    _inicializarFsm(fsm) {
        if (!fsm) return;

        fsm.cambiarEstado('OPENING', { origen: 'abrirModalAgregarPrendaNueva' });

        clearTimeout(fsm._openingTimeout);
        fsm._openingTimeout = setTimeout(() => {
            if (fsm.obtenerEstado() === 'OPENING') {
                console.warn('[abrirModal] Timeout: FSM stuck en OPENING, forzando CLOSED');
                fsm.estado = 'CLOSED';
            }
        }, 5000);
    }

    _registrarListenerModal(fsm, options = {}) {
        const modalSelector = options.modalSelector || '#modal-agregar-prenda-nueva';
        const onModalShown = options.onModalShown || (() => {});

        const modal = document.querySelector(modalSelector);
        if (!modal) return;

        modal.addEventListener('shown.bs.modal', () => {
            onModalShown();
            if (fsm) {
                fsm.cambiarEstado('OPEN', { origen: 'shown.bs.modal' });
            }
            debugLog('[abrirModal]  Modal OPEN — DragDrop inicializado');
        }, { once: true });
    }

    async abrirModal(options = {}) {
        const {
            fsm = null,
            puedeAbrir = () => true,
            cargarCatalogos = async () => {},
            actualizarBotonGuardar = () => {},
            esEdicion = false,
            cargarModoEdicion = () => {},
            cargarModoCreacion = () => {},
            onModalShown = () => {},
            onError = null
        } = options;

        if (fsm && !puedeAbrir()) {
            console.warn('[abrirModal] Bloqueado por FSM (estado:', fsm.obtenerEstado(), ')');
            return;
        }

        try {
            this._inicializarFsm(fsm);
            await cargarCatalogos();
            this._registrarListenerModal(fsm, { onModalShown });
            actualizarBotonGuardar(esEdicion);

            if (esEdicion) {
                cargarModoEdicion();
            } else {
                cargarModoCreacion();
            }
        } catch (error) {
            console.error('[abrirModalAgregarPrendaNueva] ERROR:', error);
            if (fsm) {
                fsm.cambiarEstado('CLOSED', { error: error.message });
            }
            if (typeof onError === 'function') {
                onError(error);
            }
        }
    }

    _gestionarEstadoFsm(fsm) {
        if (!fsm) return;

        clearTimeout(fsm._openingTimeout);

        const estadoActual = fsm.obtenerEstado();
        if (estadoActual === 'CLOSED') return;

        if (estadoActual === 'OPEN') {
            fsm.cambiarEstado('CLOSING', { origen: 'cerrarModalAgregarPrendaNueva' });
        } else {
            fsm.estado = 'CLOSED';
            debugLog('[cerrarModal] FSM forzada a CLOSED desde:', estadoActual);
        }
    }

    cerrarModal(options = {}) {
        const {
            fsm = null,
            limpiarEditorFlags = () => {},
            limpiarModalUI = () => {},
            limpiarComponentes = () => {}
        } = options;

        try {
            this._gestionarEstadoFsm(fsm);
            limpiarEditorFlags();
            limpiarModalUI();
            limpiarComponentes();

            if (fsm) {
                fsm.cambiarEstado('CLOSED', { origen: 'cerrarModalAgregarPrendaNueva' });
            }
        } catch (error) {
            console.error('[cerrarModal] ERROR:', error);
            if (fsm) {
                fsm.cambiarEstado('CLOSED', { error: error.message });
            }
        }
    }
}

