/**
 * NotificationService - Servicio de Notificaciones
 * 
 * Responsabilidad única: Mostrar notificaciones al usuario
 * 
 * Principios SOLID aplicados:
 * - SRP: Solo gestiona notificaciones
 * - DIP: Puede ser inyectado como dependencia
 */
class NotificationService {
    constructor(options = {}) {
        this.templates = options.templates || {
            success: 'alert alert-success',
            error: 'alert alert-danger',
            info: 'alert alert-info',
            warning: 'alert alert-warning'
        };
        this.styles = options.styles || this.getDefaultStyles();
        this.duration = options.duration || 3000;
    }

    /**
     * Mostrar notificación
     * @param {string} mensaje - Mensaje a mostrar
     * @param {string} tipo - Tipo: 'success', 'error', 'info', 'warning'
     */
    mostrar(mensaje, tipo = 'info') {
        const clase = this.templates[tipo] || this.templates.info;
        
        const notificacion = document.createElement('div');
        notificacion.className = `alert ${clase}`;
        notificacion.style.cssText = this.styles.contenedor;
        notificacion.style.animation = this.styles.animacionEntrada;
        notificacion.textContent = mensaje;

        document.body.appendChild(notificacion);

        setTimeout(() => {
            notificacion.style.animation = this.styles.animacionSalida;
            setTimeout(() => notificacion.remove(), 300);
        }, this.duration);
    }

    /**
     * Mostrar notificación de éxito
     */
    exito(mensaje) {
        this.mostrar(mensaje, 'success');
    }

    /**
     * Mostrar notificación de error
     */
    error(mensaje) {
        this.mostrar(mensaje, 'error');
    }

    /**
     * Mostrar notificación de información
     */
    info(mensaje) {
        this.mostrar(mensaje, 'info');
    }

    /**
     * Mostrar notificación de advertencia
     */
    advertencia(mensaje) {
        this.mostrar(mensaje, 'warning');
    }

    /**
     * Estilos por defecto para notificaciones
     */
    getDefaultStyles() {
        return {
            contenedor: `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 4px;
                z-index: 2147483648;
                font-size: 14px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.15);
                max-width: 400px;
            `,
            animacionEntrada: 'slideIn 0.3s ease-out',
            animacionSalida: 'slideOut 0.3s ease-in'
        };
    }
}

// Inyectar estilos de animación si no existen
if (!document.getElementById('notification-animations')) {
    const style = document.createElement('style');
    style.id = 'notification-animations';
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}

// Exportar como global si es necesario
window.NotificationService = NotificationService;
