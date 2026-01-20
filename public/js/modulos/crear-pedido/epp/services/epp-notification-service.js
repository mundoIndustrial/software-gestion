/**
 * EppNotificationService - Gestiona notificaciones y modales de feedback
 * Patrón: Service Layer
 * Responsabilidad: Mostrar/actualizar modales de éxito, error y cargando
 */

class EppNotificationService {
    constructor() {
        this.animationStyles = null;
    }

    /**
     * Mostrar modal de cargando
     */
    mostrarCargando(titulo, mensaje) {
        const backdrop = document.createElement('div');
        backdrop.id = 'modal-cargando-backdrop';
        backdrop.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        `;

        const modal = document.createElement('div');
        modal.id = 'modal-cargando';
        modal.style.cssText = `
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 400px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            text-align: center;
            animation: slideUp 0.3s ease;
        `;

        modal.innerHTML = `
            <div style="margin-bottom: 1.5rem;">
                <div style="width: 60px; height: 60px; background: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; margin-bottom: 1rem;">
                    <div style="width: 40px; height: 40px; border: 4px solid rgba(59, 130, 246, 0.3); border-top-color: #3b82f6; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                </div>
            </div>
            <h3 style="margin: 0 0 0.75rem 0; color: #1f2937; font-size: 1.25rem; font-weight: 600;" id="modal-cargando-titulo">${titulo}</h3>
            <p style="margin: 0; color: #6b7280; font-size: 0.95rem; line-height: 1.5;" id="modal-cargando-mensaje">${mensaje}</p>
        `;

        backdrop.appendChild(modal);
        document.body.appendChild(backdrop);

        this._agregarAnimaciones();
    }

    /**
     * Actualizar modal a éxito
     */
    actualizarAExito(titulo, mensaje) {
        const backdrop = document.getElementById('modal-cargando-backdrop');
        const modal = document.getElementById('modal-cargando');
        
        if (modal && backdrop) {
            const contenedor = modal.querySelector('div:first-child');
            contenedor.innerHTML = `
                <div style="width: 60px; height: 60px; background: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; margin-bottom: 1rem; animation: scaleIn 0.5s ease;">
                    <span class="material-symbols-rounded" style="color: white; font-size: 32px;">check</span>
                </div>
            `;
            
            document.getElementById('modal-cargando-titulo').textContent = titulo;
            document.getElementById('modal-cargando-mensaje').textContent = mensaje;
            
            const btn = document.createElement('button');
            btn.textContent = 'Entendido';
            btn.onclick = () => this.cerrarModal();
            btn.style.cssText = `
                margin-top: 1.5rem;
                padding: 0.75rem 1.5rem;
                background: #10b981;
                color: white;
                border: none;
                border-radius: 6px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s ease;
            `;
            btn.onmouseover = function() { this.style.background = '#059669'; };
            btn.onmouseout = function() { this.style.background = '#10b981'; };
            
            modal.appendChild(btn);
        }
    }

    /**
     * Actualizar modal a error
     */
    actualizarAError(titulo, mensaje) {
        const backdrop = document.getElementById('modal-cargando-backdrop');
        const modal = document.getElementById('modal-cargando');
        
        if (modal && backdrop) {
            const contenedor = modal.querySelector('div:first-child');
            contenedor.innerHTML = `
                <div style="width: 60px; height: 60px; background: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; margin-bottom: 1rem; animation: scaleIn 0.5s ease;">
                    <span class="material-symbols-rounded" style="color: white; font-size: 32px;">close</span>
                </div>
            `;
            
            document.getElementById('modal-cargando-titulo').textContent = titulo;
            document.getElementById('modal-cargando-mensaje').textContent = mensaje;
            
            const btn = document.createElement('button');
            btn.textContent = 'Cerrar';
            btn.onclick = () => this.cerrarModal();
            btn.style.cssText = `
                margin-top: 1.5rem;
                padding: 0.75rem 1.5rem;
                background: #ef4444;
                color: white;
                border: none;
                border-radius: 6px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s ease;
            `;
            btn.onmouseover = function() { this.style.background = '#dc2626'; };
            btn.onmouseout = function() { this.style.background = '#ef4444'; };
            
            modal.appendChild(btn);
        }
    }

    /**
     * Cerrar modal
     */
    cerrarModal() {
        const backdrop = document.getElementById('modal-cargando-backdrop');
        if (backdrop) {
            backdrop.style.animation = 'slideDown 0.3s ease';
            setTimeout(() => {
                backdrop.remove();
            }, 300);
        }
    }

    /**
     * Agregar animaciones CSS si no existen
     */
    _agregarAnimaciones() {
        if (!document.getElementById('epp-notification-animations')) {
            const style = document.createElement('style');
            style.id = 'epp-notification-animations';
            style.textContent = `
                @keyframes slideUp {
                    from {
                        opacity: 0;
                        transform: translateY(30px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                @keyframes slideDown {
                    from {
                        opacity: 1;
                        transform: translateY(0);
                    }
                    to {
                        opacity: 0;
                        transform: translateY(30px);
                    }
                }
                @keyframes spin {
                    to { transform: rotate(360deg); }
                }
                @keyframes scaleIn {
                    from {
                        opacity: 0;
                        transform: scale(0.8);
                    }
                    to {
                        opacity: 1;
                        transform: scale(1);
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }
}

// Exportar instancia global
window.eppNotificationService = new EppNotificationService();
