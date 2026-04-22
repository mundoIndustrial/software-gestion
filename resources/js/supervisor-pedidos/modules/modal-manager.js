/**
 * Modal Manager - Gestor centralizado de modales
 *
 * Responsabilidades:
 * - Crear y gestionar modales genéricos
 * - Manejar eventos de apertura/cierre
 * - Stack de modales (soporte para múltiples)
 * - Accesibilidad (focus trap, escape key)
 */

export class ModalManager {
    constructor() {
        this.stack = [];
        this.backdrop = null;
        this.init();
    }

    init() {
        // Event listeners globales
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.stack.length > 0) {
                this.close();
            }
        });

        console.log('[ModalManager] ✅ Initialized');
    }

    /**
     * Crear y abrir modal
     */
    open(options = {}) {
        const {
            title = '',
            content = '',
            size = 'medium', // small, medium, large
            buttons = [],
            onClose = null,
            closeButton = true,
        } = options;

        const modalId = `modal-${Date.now()}`;

        // Crear backdrop si es el primer modal
        if (this.stack.length === 0) {
            this.backdrop = document.createElement('div');
            this.backdrop.className = 'modal-backdrop';
            this.backdrop.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 9998;
                animation: fadeIn 0.2s ease-in;
            `;
            this.backdrop.addEventListener('click', () => {
                if (this.stack.length > 0) {
                    this.close();
                }
            });
            document.body.appendChild(this.backdrop);
        }

        // Crear modal
        const modal = document.createElement('div');
        modal.id = modalId;
        modal.className = `modal modal-${size}`;
        modal.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            z-index: 9999;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            animation: modalSlideUp 0.3s ease-out;
            ${this.getSizeStyles(size)}
        `;

        // Header
        const header = document.createElement('div');
        header.className = 'modal-header';
        header.style.cssText = `
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        `;

        const titleEl = document.createElement('h2');
        titleEl.style.cssText = 'margin: 0; font-size: 1.25rem; font-weight: 600;';
        titleEl.textContent = title;
        header.appendChild(titleEl);

        if (closeButton) {
            const closeBtn = document.createElement('button');
            closeBtn.innerHTML = '&times;';
            closeBtn.style.cssText = `
                background: none;
                border: none;
                font-size: 1.5rem;
                cursor: pointer;
                color: #6b7280;
                padding: 0;
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
            `;
            closeBtn.addEventListener('click', () => this.close());
            header.appendChild(closeBtn);
        }

        modal.appendChild(header);

        // Content
        const contentDiv = document.createElement('div');
        contentDiv.className = 'modal-content';
        contentDiv.style.cssText = `
            padding: 1.5rem;
            overflow-y: auto;
            flex: 1;
        `;
        contentDiv.innerHTML = content;
        modal.appendChild(contentDiv);

        // Footer con botones
        if (buttons.length > 0) {
            const footer = document.createElement('div');
            footer.className = 'modal-footer';
            footer.style.cssText = `
                padding: 1rem 1.5rem;
                border-top: 1px solid #e5e7eb;
                display: flex;
                gap: 0.75rem;
                justify-content: flex-end;
                flex-shrink: 0;
            `;

            buttons.forEach(btn => {
                const button = document.createElement('button');
                button.textContent = btn.label;
                button.style.cssText = `
                    padding: 0.625rem 1rem;
                    border: 1px solid #d1d5db;
                    border-radius: 4px;
                    background: ${btn.variant === 'primary' ? '#3b82f6' : 'white'};
                    color: ${btn.variant === 'primary' ? 'white' : '#374151'};
                    cursor: pointer;
                    font-weight: 500;
                    transition: all 0.2s;
                `;
                button.addEventListener('click', () => {
                    if (btn.onClick) btn.onClick();
                    if (btn.closeOnClick !== false) {
                        this.close();
                    }
                });
                footer.appendChild(button);
            });

            modal.appendChild(footer);
        }

        // Agregar estilos de animación
        if (!document.getElementById('modal-styles')) {
            const style = document.createElement('style');
            style.id = 'modal-styles';
            style.innerHTML = `
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes modalSlideUp {
                    from {
                        opacity: 0;
                        transform: translate(-50%, -40%);
                    }
                    to {
                        opacity: 1;
                        transform: translate(-50%, -50%);
                    }
                }
            `;
            document.head.appendChild(style);
        }

        document.body.appendChild(modal);

        this.stack.push({
            id: modalId,
            element: modal,
            onClose,
        });

        return modalId;
    }

    /**
     * Cerrar modal
     */
    close(modalId = null) {
        const target = modalId ? this.stack.find(m => m.id === modalId) : this.stack[this.stack.length - 1];

        if (!target) return;

        // Remover del stack
        const index = this.stack.indexOf(target);
        if (index > -1) {
            this.stack.splice(index, 1);
        }

        // Remover elemento
        target.element.remove();

        // Ejecutar callback
        if (target.onClose) {
            target.onClose();
        }

        // Remover backdrop si no hay más modales
        if (this.stack.length === 0 && this.backdrop) {
            this.backdrop.remove();
            this.backdrop = null;
        }
    }

    /**
     * Cerrar todos los modales
     */
    closeAll() {
        while (this.stack.length > 0) {
            this.close();
        }
    }

    /**
     * Obtener estilos según tamaño
     */
    getSizeStyles(size) {
        const sizes = {
            small: 'width: 100%; max-width: 400px;',
            medium: 'width: 100%; max-width: 600px;',
            large: 'width: 100%; max-width: 800px;',
        };
        return sizes[size] || sizes.medium;
    }
}

/**
 * Singleton global
 */
let modalManagerInstance = null;

export function getModalManager() {
    if (!modalManagerInstance) {
        modalManagerInstance = new ModalManager();
    }
    return modalManagerInstance;
}

/**
 * Helper para crear modales simples
 */
export function openModal(options) {
    const manager = getModalManager();
    return manager.open(options);
}

export function closeModal(modalId) {
    const manager = getModalManager();
    manager.close(modalId);
}

export function closeAllModals() {
    const manager = getModalManager();
    manager.closeAll();
}
