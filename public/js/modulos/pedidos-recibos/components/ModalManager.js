/**
 * ModalManager.js
 * Gestiona la apertura, cierre y estado del modal principal
 */

export class ModalManager {
    constructor() {
        this.state = {
            pedidoId: null,
            prendaId: null,
            tipoProceso: null,
            prendaIndex: null,
            datosCompletos: null,
            procesosActuales: [],
            procesoActualIndice: 0,
            prendaPedidoId: null,
            imagenesActuales: []
        };
    }

    /**
     * Obtiene el estado actual del modal
     */
    getState() {
        return this.state;
    }

    /**
     * Actualiza el estado del modal
     */
    setState(updates) {
        this.state = { ...this.state, ...updates };
    }

    /**
     * Abre el modal (muestra elementos visuales)
     */
    abrirModal() {
        const overlay = document.getElementById('modal-overlay');
        const modalWrapper = document.getElementById('order-detail-modal-wrapper');
        
        if (overlay) overlay.style.display = 'block';
        if (modalWrapper) {
            modalWrapper.style.display = 'block';
            modalWrapper.style.pointerEvents = 'none'; // Desactivar mientras carga
        }
        

    }

    /**
     * Habilita interacción con el modal
     */
    habilitarInteraccion() {
        const modalWrapper = document.getElementById('order-detail-modal-wrapper');
        if (modalWrapper) modalWrapper.style.pointerEvents = 'auto';
    }

    /**
     * Cierra el modal
     */
    cerrarModal() {
        const modalWrapper = document.getElementById('order-detail-modal-wrapper');
        const modalWrapperLogo = document.getElementById('order-detail-modal-wrapper-logo');
        const overlay = document.getElementById('modal-overlay');
        
        if (modalWrapper) modalWrapper.style.display = 'none';
        if (modalWrapperLogo) modalWrapperLogo.style.display = 'none';
        if (overlay) overlay.style.display = 'none';
        
        this.limpiarEstado();

    }

    /**
     * Limpia el estado del modal
     */
    limpiarEstado() {
        this.state = {
            pedidoId: null,
            prendaId: null,
            tipoProceso: null,
            prendaIndex: null,
            datosCompletos: null,
            procesosActuales: [],
            procesoActualIndice: 0,
            prendaPedidoId: null,
            imagenesActuales: []
        };
    }

    /**
     * Obtiene el wrapper del modal
     */
    getModalWrapper() {
        return document.getElementById('order-detail-modal-wrapper');
    }

    /**
     * Obtiene el contenedor principal del modal
     */
    getModalContainer() {
        const wrapper = this.getModalWrapper();
        return wrapper ? wrapper.querySelector('.order-detail-modal-container') : null;
    }

    /**
     * Establece z-index del overlay y modal
     */
    configurarZIndex(zIndexOverlay = 9997, zIndexModal = 9998) {
        const overlay = document.getElementById('modal-overlay');
        const modalWrapper = this.getModalWrapper();
        
        if (overlay) overlay.style.zIndex = zIndexOverlay;
        if (modalWrapper) modalWrapper.style.zIndex = zIndexModal;
    }
}

// Instancia global singleton
window.modalManager = new ModalManager();
