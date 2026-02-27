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
     * Resetea TODOS los estilos que pudieron ser modificados al cerrar
     */
    abrirModal() {
        const overlay = document.getElementById('modal-overlay');
        const modalWrapper = document.getElementById('order-detail-modal-wrapper');
        
        if (overlay) {
            overlay.style.display = 'block';
            overlay.style.opacity = '1';
            overlay.style.visibility = 'visible';
            overlay.style.zIndex = '9997';
            overlay.style.pointerEvents = 'auto';
        }
        if (modalWrapper) {
            modalWrapper.style.display = 'block';
            modalWrapper.style.opacity = '1';
            modalWrapper.style.visibility = 'visible';
            modalWrapper.style.zIndex = '9998';
            modalWrapper.style.pointerEvents = 'none'; // Desactivar mientras carga
        }

        // Asegurar que el card sea visible (pudo quedar oculto por la galería)
        if (modalWrapper) {
            const card = modalWrapper.querySelector('.order-detail-card');
            if (card) card.style.display = 'block';
        }

        // Limpiar galería residual
        const galeriaResidual = document.getElementById('galeria-modal-costura');
        if (galeriaResidual) galeriaResidual.remove();

        // Limpiar botones de cierre residuales
        const btnCerrarInsumos = document.getElementById('btn-cerrar-modal-insumos');
        if (btnCerrarInsumos) btnCerrarInsumos.remove();

        // Mostrar botones flotantes de galería
        const floatingContainer = document.getElementById('floating-buttons-container');
        if (floatingContainer) {
            floatingContainer.style.display = 'flex';
            // Resetear botones: mostrar btn-factura, ocultar btn-galeria
            const btnFactura = document.getElementById('btn-factura');
            const btnGaleria = document.getElementById('btn-galeria');
            if (btnFactura) {
                btnFactura.style.display = 'block';
                btnFactura.style.visibility = 'visible';
                btnFactura.style.zIndex = '10';
                // Icono de galería (indica que al clickear se va a la galería)
                const iconFactura = btnFactura.querySelector('i');
                if (iconFactura) { iconFactura.className = 'fas fa-images'; btnFactura.title = 'Ver galería'; }
            }
            if (btnGaleria) {
                btnGaleria.style.display = 'none';
                btnGaleria.style.visibility = 'hidden';
                btnGaleria.style.zIndex = '-1';
            }
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
        
        if (modalWrapper) {
            modalWrapper.style.display = 'none';
            modalWrapper.style.opacity = '0';
            modalWrapper.style.visibility = 'hidden';
            modalWrapper.style.pointerEvents = 'none';
        }
        if (modalWrapperLogo) {
            modalWrapperLogo.style.display = 'none';
        }
        if (overlay) {
            overlay.style.display = 'none';
            overlay.style.opacity = '0';
            overlay.style.visibility = 'hidden';
            overlay.style.pointerEvents = 'none';
        }

        // Limpiar galería y botones flotantes
        const galeria = document.getElementById('galeria-modal-costura');
        if (galeria) galeria.remove();
        const btnCerrarInsumos = document.getElementById('btn-cerrar-modal-insumos');
        if (btnCerrarInsumos) btnCerrarInsumos.remove();
        
        // Ocultar botones flotantes de galería
        const floatingContainer = document.getElementById('floating-buttons-container');
        if (floatingContainer) floatingContainer.style.display = 'none';
        
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
