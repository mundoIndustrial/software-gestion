/**
 * NotificationManager
 * Responsabilidad: Mostrar notificaciones modernas
 * SOLID: Single Responsibility
 */
const NotificationManager = (() => {
    return {
        show: (message, type = 'info', extraData = null) => {
            let container = document.getElementById('modern-notifications-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'modern-notifications-container';
                container.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 10000;
                    pointer-events: none;
                `;
                document.body.appendChild(container);
            }

            const notification = document.createElement('div');
            const notificationId = 'notification-' + Date.now();
            notification.id = notificationId;
            
            const typeStyles = {
                success: { bg: 'linear-gradient(135deg, #10b981, #059669)', icon: '', border: '#10b981' },
                error: { bg: 'linear-gradient(135deg, #ef4444, #dc2626)', icon: '', border: '#ef4444' },
                warning: { bg: 'linear-gradient(135deg, #f59e0b, #d97706)', icon: '⚠️', border: '#f59e0b' },
                info: { bg: 'linear-gradient(135deg, #3b82f6, #2563eb)', icon: '', border: '#3b82f6' }
            };

            const style = typeStyles[type] || typeStyles.info;
            
            let extraContent = '';
            if (extraData) {
                if (extraData.prendas !== undefined) extraContent += `<div style="font-size: 0.9em; margin-top: 8px; opacity: 0.9;">Prendas afectadas: ${extraData.prendas}</div>`;
                if (extraData.registrosRegenerados) extraContent += `<div style="font-size: 0.9em; margin-top: 8px; opacity: 0.9;">Registros regenerados: ${extraData.registrosRegenerados}</div>`;
            }

            notification.style.cssText = `
                background: ${style.bg};
                color: white;
                padding: 16px 20px;
                border-radius: 12px;
                box-shadow: 0 8px 32px rgba(0,0,0,0.2);
                margin-bottom: 12px;
                max-width: 400px;
                pointer-events: auto;
                cursor: pointer;
                transform: translateX(100%);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                border-left: 4px solid ${style.border};
                backdrop-filter: blur(10px);
            `;

            notification.innerHTML = `
                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <span style="font-size: 1.2em; flex-shrink: 0;">${style.icon}</span>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; line-height: 1.4; white-space: pre-line;">${message}</div>
                        ${extraContent}
                    </div>
                    <button style="
                        background: none; 
                        border: none; 
                        color: white; 
                        font-size: 1.2em; 
                        cursor: pointer; 
                        opacity: 0.7;
                        padding: 0;
                        margin-left: 8px;
                        flex-shrink: 0;
                    " onclick="this.parentElement.parentElement.remove()">×</button>
                </div>
            `;

            container.appendChild(notification);

            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 10);

            const autoRemoveTime = type === 'error' ? 8000 : (message.length > 100 ? 6000 : 4000);
            setTimeout(() => {
                if (document.getElementById(notificationId)) {
                    notification.style.transform = 'translateX(100%)';
                    notification.style.opacity = '0';
                    setTimeout(() => notification.remove(), 300);
                }
            }, autoRemoveTime);

            notification.addEventListener('click', () => {
                notification.style.transform = 'translateX(100%)';
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            });
        }
    };
})();

globalThis.NotificationManager = NotificationManager;
