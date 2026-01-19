/**
 * CloseButtonManager.js
 * Maneja la creación y comportamiento del botón X de cierre
 */

export class CloseButtonManager {
    static BUTTON_ID = 'btn-cerrar-modal-dinamico';
    
    /**
     * Crea el botón X si no existe
     * @param {ModalManager} modalManager - Instancia del gestor de modal
     */
    static crearBotonCierre(modalManager) {
        if (document.getElementById(this.BUTTON_ID)) {
            return; // Ya existe
        }

        const btnCerrar = document.createElement('button');
        btnCerrar.id = this.BUTTON_ID;
        btnCerrar.type = 'button';
        btnCerrar.title = 'Cerrar';
        btnCerrar.innerHTML = '<i class="fas fa-times"></i>';
        
        // Click para cerrar modal
        btnCerrar.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.cerrarModal(modalManager);
            return false;
        });
        
        // Estilos
        btnCerrar.style.cssText = `
            position: fixed;
            right: 10px;
            top: 10px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.95);
            border: none;
            color: #333;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            z-index: 10001;
            font-weight: bold;
        `;

        document.body.appendChild(btnCerrar);
        
        // Observar cambios en el modal para eliminar el botón cuando se cierre
        this.configurarObservador(modalManager);
        
        console.log('[CloseButtonManager] Botón X creado');
    }

    /**
     * Configura un MutationObserver para eliminar el botón cuando el modal se cierra
     */
    static configurarObservador(modalManager) {
        const modalWrapper = modalManager.getModalWrapper();
        if (!modalWrapper) return;

        const observer = new MutationObserver(() => {
            const display = window.getComputedStyle(modalWrapper).display;
            if (display === 'none') {
                this.removerBoton();
                observer.disconnect();
            }
        });

        observer.observe(modalWrapper, { 
            attributes: true, 
            attributeFilter: ['style'] 
        });
    }

    /**
     * Cierra el modal
     */
    static cerrarModal(modalManager) {
        this.removerBoton();
        modalManager.cerrarModal();
    }

    /**
     * Remueve el botón del DOM
     */
    static removerBoton() {
        const btn = document.getElementById(this.BUTTON_ID);
        if (btn) {
            btn.remove();
            console.log('[CloseButtonManager] Botón X removido');
        }
    }

    /**
     * Fuerza el cierre (sin observador)
     */
    static forzarCierre(modalManager) {
        this.cerrarModal(modalManager);
    }
}
