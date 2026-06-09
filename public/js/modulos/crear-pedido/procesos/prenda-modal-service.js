/**
 * Servicio para orquestar apertura/cierre del modal de prenda.
 * Encapsula FSM, listeners y limpieza de UI para reducir complejidad en GestionItemsUI.
 */
class PrendaModalService {
    _obtenerModal(modalSelector) {
        if (typeof document === 'undefined') return null;
        return document.querySelector(modalSelector || '#modal-agregar-prenda-nueva');
    }

    _estaVisible(modal) {
        if (!modal) return false;

        const style = globalThis.getComputedStyle ? globalThis.getComputedStyle(modal) : null;
        const display = style?.display ?? modal.style?.display ?? '';
        const visibility = style?.visibility ?? modal.style?.visibility ?? '';

        return display !== 'none' && visibility !== 'hidden';
    }

    _recuperarFsmSiDesincronizada(fsm, modalSelector) {
        if (!fsm) return false;

        const estadoActual = fsm.obtenerEstado();
        if (estadoActual !== 'OPENING') {
            return false;
        }

        const modal = this._obtenerModal(modalSelector);
        if (this._estaVisible(modal)) {
            fsm.cambiarEstado('OPEN', { origen: 'recover-visible-modal' });
            return true;
        }

        const aperturaAnterior = fsm.timestamps?.ultimaApertura || 0;
        const tiempoTranscurrido = Date.now() - aperturaAnterior;
        if (tiempoTranscurrido >= 1500) {
            console.warn('[abrirModal] FSM recuperada desde OPENING tras', tiempoTranscurrido, 'ms');
            fsm.estado = 'CLOSED';
            return true;
        }

        return false;
    }

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
            console.log('[abrirModal] shown.bs.modal recibido para:', modalSelector);
            onModalShown();
            if (fsm) {
                fsm.cambiarEstado('OPEN', { origen: 'shown.bs.modal' });
            }
            debugLog('[abrirModal] Modal OPEN - DragDrop inicializado');
        }, { once: true });
    }

    async abrirModal(options = {}) {
        const {
            fsm = null,
            modalSelector = '#modal-agregar-prenda-nueva',
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
            this._recuperarFsmSiDesincronizada(fsm, modalSelector);
        }

        if (fsm && !puedeAbrir()) {
            console.warn('[abrirModal] Bloqueado por FSM (estado:', fsm.obtenerEstado(), ')');
            return;
        }

        try {
            this._inicializarFsm(fsm);
            await cargarCatalogos();
            this._registrarListenerModal(fsm, { modalSelector, onModalShown });
            actualizarBotonGuardar(esEdicion);

            if (esEdicion) {
                cargarModoEdicion();
            } else {
                cargarModoCreacion();
            }

            if (fsm) {
                setTimeout(() => {
                    if (fsm.obtenerEstado() === 'OPENING') {
                        const modal = this._obtenerModal(modalSelector);
                        if (this._estaVisible(modal)) {
                            console.log('[abrirModal] fallback post-open activado para:', modalSelector);
                            fsm.cambiarEstado('OPEN', { origen: 'fallback-post-open' });
                        }
                    }
                }, 120);
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
