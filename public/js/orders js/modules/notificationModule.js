/**
 * MÓDULO: notificationModule.js
 * Responsabilidad: Gestionar notificaciones y auto-recarga
 * Principios SOLID: SRP (Single Responsibility)
 */

console.log(' Cargando NotificationModule...');

const NotificationModule = {
    /**
     * Mostrar notificación de auto-recarga
     */
    showAutoReload(message, duration) {
        // Remover notificaciones existentes
        document.querySelectorAll('.auto-reload-notification').forEach(n => n.remove());
        
        const notification = document.createElement('div');
        notification.className = 'auto-reload-notification';
        notification.innerHTML = `
            <div class="auto-reload-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
            <div class="auto-reload-content">
                <div class="auto-reload-title">Recargando página</div>
                <div class="auto-reload-message">${message}</div>
                <div class="auto-reload-progress">
                    <div class="auto-reload-progress-bar" style="animation-duration: ${duration}ms"></div>
                </div>
            </div>
        `;
        
        this._ensureStyles();
        document.body.appendChild(notification);
        console.log(`🔄 Notificación mostrada: ${message}`);
    },

    /**
     * Mostrar notificación de error
     */
    showError(message, duration = 5000) {
        document.querySelectorAll('.delete-notification').forEach(n => n.remove());
        
        const notification = document.createElement('div');
        notification.className = 'delete-notification delete-notification-error';
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'notificationSlideOut 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }
        }, duration);
    },

    /**
     * Mostrar notificación de éxito
     */
    showSuccess(message, duration = 3000) {
        document.querySelectorAll('.delete-notification').forEach(n => n.remove());
        
        const notification = document.createElement('div');
        notification.className = 'delete-notification delete-notification-success';
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'notificationSlideOut 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }
        }, duration);
    },

    /**
     * Asegurar que los estilos estén presentes
     */
    _ensureStyles() {
        if (!document.getElementById('auto-reload-styles')) {
            const style = document.createElement('style');
            style.id = 'auto-reload-styles';
            style.textContent = `
                .auto-reload-notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                    color: white;
                    padding: 16px 20px;
                    border-radius: 12px;
                    box-shadow: 0 10px 40px rgba(239, 68, 68, 0.4);
                    z-index: 10000;
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    min-width: 320px;
                    animation: slideInRight 0.3s ease-out;
                }
                
                .auto-reload-icon {
                    flex-shrink: 0;
                    width: 32px;
                    height: 32px;
                    animation: spin 1s linear infinite;
                }
                
                .auto-reload-icon svg {
                    width: 100%;
                    height: 100%;
                }
                
                .auto-reload-content {
                    flex: 1;
                }
                
                .auto-reload-title {
                    font-weight: 700;
                    font-size: 14px;
                    margin-bottom: 4px;
                }
                
                .auto-reload-message {
                    font-size: 12px;
                    opacity: 0.9;
                    margin-bottom: 8px;
                }
                
                .auto-reload-progress {
                    width: 100%;
                    height: 4px;
                    background: rgba(255, 255, 255, 0.3);
                    border-radius: 2px;
                    overflow: hidden;
                }
                
                .auto-reload-progress-bar {
                    height: 100%;
                    background: white;
                    border-radius: 2px;
                    animation: progressBar linear forwards;
                }
                
                @keyframes slideInRight {
                    from { transform: translateX(400px); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
                
                @keyframes progressBar {
                    from { width: 100%; }
                    to { width: 0%; }
                }
            `;
            document.head.appendChild(style);
        }
    }
};

// Exponer módulo globalmente
window.NotificationModule = NotificationModule;
globalThis.NotificationModule = NotificationModule;
