/**
 * Gestor de Notificaciones
 * Maneja la visualización de mensajes y alertas al usuario
 */

class NotificationManager {
    constructor() {
        this.init();
    }

    init() {
        // Hacer métodos disponibles globalmente para compatibilidad
        window.mostrarErrorNotificacion = this.mostrarError.bind(this);
        window.mostrarExitoNotificacion = this.mostrarExito.bind(this);
        window.mostrarInfoNotificacion = this.mostrarInfo.bind(this);
        window.mostrarAdvertenciaNotificacion = this.mostrarAdvertencia.bind(this);
    }

    /**
     * Muestra una notificación de error
     */
    mostrarError(titulo, mensaje, duracion = 5000) {
        this.mostrarNotificacion(titulo, mensaje, 'error', duracion);
    }

    /**
     * Muestra una notificación de éxito
     */
    mostrarExito(titulo, mensaje, duracion = 3000) {
        this.mostrarNotificacion(titulo, mensaje, 'exito', duracion);
    }

    /**
     * Muestra una notificación de información
     */
    mostrarInfo(titulo, mensaje, duracion = 4000) {
        this.mostrarNotificacion(titulo, mensaje, 'info', duracion);
    }

    /**
     * Muestra una notificación de advertencia
     */
    mostrarAdvertencia(titulo, mensaje, duracion = 4000) {
        this.mostrarNotificacion(titulo, mensaje, 'advertencia', duracion);
    }

    /**
     * Muestra una notificación genérica
     */
    mostrarNotificacion(titulo, mensaje, tipo = 'info', duracion = 4000) {
        // Crear elemento de notificación
        const notif = document.createElement('div');
        notif.className = `notification notification-${tipo}`;
        
        // Aplicar estilos según el tipo
        const estilos = this.getEstilosTipo(tipo);
        notif.style.cssText = estilos.base;
        
        // Agregar estilos específicos del tipo
        Object.assign(notif.style, estilos.especificos);
        
        // Crear contenido
        notif.innerHTML = this.crearContenidoNotificacion(titulo, mensaje, tipo);
        
        // Agregar al DOM
        document.body.appendChild(notif);
        
        // Animación de entrada
        setTimeout(() => {
            notif.style.animation = 'slideIn 0.3s ease forwards';
        }, 10);
        
        // Auto-eliminar después de la duración
        setTimeout(() => {
            this.cerrarNotificacion(notif);
        }, duracion);
        
        // Agregar evento de clic para cerrar manualmente
        notif.addEventListener('click', () => {
            this.cerrarNotificacion(notif);
        });
        
        console.log(`[NotificationManager] Notificación ${tipo} mostrada:`, { titulo, mensaje });
    }

    /**
     * Obtiene los estilos según el tipo de notificación
     */
    getEstilosTipo(tipo) {
        const estilosBase = {
            position: 'fixed',
            top: '20px',
            right: '20px',
            padding: '16px 20px',
            borderRadius: '6px',
            boxShadow: '0 4px 12px rgba(0, 0, 0, 0.15)',
            zIndex: '10001',
            maxWidth: '400px',
            cursor: 'pointer',
            transition: 'all 0.3s ease',
            transform: 'translateX(100%)',
            opacity: '0',
            fontSize: '14px',
            lineHeight: '1.4'
        };

        const estilosEspecificos = {
            error: {
                background: '#fee2e2',
                borderLeft: '4px solid #dc2626',
                color: '#991b1b'
            },
            exito: {
                background: '#f0fdf4',
                borderLeft: '4px solid #16a34a',
                color: '#166534'
            },
            info: {
                background: '#eff6ff',
                borderLeft: '4px solid #2563eb',
                color: '#1e40af'
            },
            advertencia: {
                background: '#fffbeb',
                borderLeft: '4px solid #d97706',
                color: '#92400e'
            }
        };

        return {
            base: estilosBase,
            especificos: estilosEspecificos[tipo] || estilosEspecificos.info
        };
    }

    /**
     * Crea el contenido HTML de la notificación
     */
    crearContenidoNotificacion(titulo, mensaje, tipo) {
        const icono = this.getIconoTipo(tipo);
        
        return `
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <div style="font-size: 20px; line-height: 1; margin-top: 2px;">
                    ${icono}
                </div>
                <div style="flex: 1;">
                    <h4 style="margin: 0 0 4px 0; font-weight: 600; font-size: 14px; line-height: 1.2;">
                        ${titulo}
                    </h4>
                    ${mensaje ? `<p style="margin: 0; font-size: 13px; line-height: 1.3;">${mensaje}</p>` : ''}
                </div>
                <button onclick="event.stopPropagation(); this.parentElement.parentElement.remove();" 
                        style="background: none; border: none; font-size: 18px; cursor: pointer; opacity: 0.6; padding: 0; margin: 0; line-height: 1;">
                    ×
                </button>
            </div>
        `;
    }

    /**
     * Obtiene el icono según el tipo de notificación
     */
    getIconoTipo(tipo) {
        const iconos = {
            error: '❌',
            exito: '✅',
            info: 'ℹ️',
            advertencia: '⚠️'
        };
        
        return iconos[tipo] || iconos.info;
    }

    /**
     * Cierra una notificación específica
     */
    cerrarNotificacion(notif) {
        if (!notif || !notif.parentNode) return;
        
        notif.style.animation = 'slideOut 0.3s ease forwards';
        
        setTimeout(() => {
            if (notif.parentNode) {
                notif.remove();
            }
        }, 300);
    }

    /**
     * Cierra todas las notificaciones activas
     */
    cerrarTodasLasNotificaciones() {
        const notificaciones = document.querySelectorAll('.notification');
        notificaciones.forEach(notif => {
            this.cerrarNotificacion(notif);
        });
    }

    /**
     * Muestra una notificación temporal que se desvanece
     */
    mostrarTemporal(mensaje, tipo = 'info', duracion = 2000) {
        this.mostrarNotificacion('', mensaje, tipo, duracion);
    }

    /**
     * Muestra una notificación persistente (no se auto-cierra)
     */
    mostrarPersistente(titulo, mensaje, tipo = 'info') {
        const notif = document.createElement('div');
        notif.className = `notification notification-${tipo} persistent`;
        
        const estilos = this.getEstilosTipo(tipo);
        notif.style.cssText = estilos.base;
        Object.assign(notif.style, estilos.especificos);
        
        notif.innerHTML = this.crearContenidoNotificacion(titulo, mensaje, tipo);
        
        document.body.appendChild(notif);
        
        // Animación de entrada
        setTimeout(() => {
            notif.style.animation = 'slideIn 0.3s ease forwards';
        }, 10);
        
        // Agregar evento de clic para cerrar
        notif.addEventListener('click', () => {
            this.cerrarNotificacion(notif);
        });
        
        return notif; // Devolver referencia para manejo manual
    }

    /**
     * Muestra una notificación en la parte inferior
     */
    mostrarInferior(titulo, mensaje, tipo = 'info', duracion = 4000) {
        const notif = document.createElement('div');
        notif.className = `notification notification-${tipo} bottom`;
        
        const estilosBase = {
            position: 'fixed',
            bottom: '20px',
            right: '20px',
            padding: '16px 20px',
            borderRadius: '6px',
            boxShadow: '0 4px 12px rgba(0, 0, 0, 0.15)',
            zIndex: '10001',
            maxWidth: '400px',
            cursor: 'pointer',
            transition: 'all 0.3s ease',
            transform: 'translateY(100%)',
            opacity: '0',
            fontSize: '14px',
            lineHeight: '1.4'
        };

        const estilosEspecificos = this.getEstilosTipo(tipo).especificos;
        
        notif.style.cssText = estilosBase;
        Object.assign(notif.style, estilosEspecificos);
        
        notif.innerHTML = this.crearContenidoNotificacion(titulo, mensaje, tipo);
        
        document.body.appendChild(notif);
        
        setTimeout(() => {
            notif.style.animation = 'slideUp 0.3s ease forwards';
        }, 10);
        
        setTimeout(() => {
            this.cerrarNotificacion(notif);
        }, duracion);
        
        notif.addEventListener('click', () => {
            this.cerrarNotificacion(notif);
        });
    }

    /**
     * Muestra una confirmación con botones
     */
    mostrarConfirmacion(titulo, mensaje, onConfirmar, onCancelar) {
        const notif = document.createElement('div');
        notif.className = 'notification confirmation';
        
        notif.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            z-index: 10002;
            max-width: 400px;
            text-align: center;
        `;
        
        notif.innerHTML = `
            <h3 style="margin: 0 0 12px 0; font-size: 18px; color: #333;">${titulo}</h3>
            <p style="margin: 0 0 20px 0; color: #666; line-height: 1.4;">${mensaje}</p>
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button id="confirmar-btn" style="padding: 8px 16px; background: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 500;">
                    Confirmar
                </button>
                <button id="cancelar-btn" style="padding: 8px 16px; background: #e5e7eb; color: #333; border: none; border-radius: 4px; cursor: pointer; font-weight: 500;">
                    Cancelar
                </button>
            </div>
        `;
        
        document.body.appendChild(notif);
        
        // Eventos
        document.getElementById('confirmar-btn').onclick = () => {
            notif.remove();
            if (onConfirmar) onConfirmar();
        };
        
        document.getElementById('cancelar-btn').onclick = () => {
            notif.remove();
            if (onCancelar) onCancelar();
        };
        
        // Cerrar al hacer clic fuera
        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
            z-index: 10001;
        `;
        
        overlay.onclick = () => {
            overlay.remove();
            notif.remove();
            if (onCancelar) onCancelar();
        };
        
        document.body.appendChild(overlay);
    }

    /**
     * Agrega estilos CSS necesarios para las animaciones
     */
    agregarEstilosCSS() {
        if (!document.getElementById('notification-styles')) {
            const style = document.createElement('style');
            style.id = 'notification-styles';
            style.textContent = `
                @keyframes slideIn {
                    from {
                        transform: translateX(100%);
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
                        transform: translateX(100%);
                        opacity: 0;
                    }
                }
                
                @keyframes slideUp {
                    from {
                        transform: translateY(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateY(0);
                        opacity: 1;
                    }
                }
                
                .notification {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                }
                
                .notification:hover {
                    transform: translateX(-5px);
                    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
                }
            `;
            document.head.appendChild(style);
        }
    }
}

// Inicializar el gestor y agregar estilos
document.addEventListener('DOMContentLoaded', () => {
    window.notificationManager = new NotificationManager();
    window.notificationManager.agregarEstilosCSS();
});

// También permitir inicialización manual
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.notificationManager = new NotificationManager();
        window.notificationManager.agregarEstilosCSS();
    });
} else {
    window.notificationManager = new NotificationManager();
    window.notificationManager.agregarEstilosCSS();
}
