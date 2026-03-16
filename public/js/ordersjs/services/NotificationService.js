/**
 * Notification Service - Centralización de mensajes y notificaciones
 * 
 * ARQUITECTURA: Notification Layer Pattern
 * - Responsabilidad única: mostrar mensajes al usuario
 * - SRP (Single Responsibility Principle) ✓
 * - DRY (Don't Repeat Yourself) ✓
 * - Interfaz consistente para todos los mensajes
 * - Fallback si el sistema de toasts no está disponible
 * 
 * USO:
 * NotificationService.success('Operación exitosa');
 * NotificationService.error('Ocurrió un error');
 * NotificationService.info('Información');
 * NotificationService.warning('Advertencia');
 */

const NotificationService = {
  /**
   * Tipos de notificación
   */
  TYPES: {
    SUCCESS: 'success',
    ERROR: 'error',
    INFO: 'info',
    WARNING: 'warning'
  },

  /**
   * Configuración
   */
  config: {
    useToastIfAvailable: true,
    useBrowserNotification: false,
    consoleLogging: true,
    timeout: 5000 // ms
  },

  /**
   * Mostrar notificación de éxito
   * @param {string} message - Mensaje a mostrar
   * @param {Object} options - Opciones adicionales
   */
  success: function(message, options = {}) {
    this._show(message, this.TYPES.SUCCESS, options);
  },

  /**
   * Mostrar notificación de error
   * @param {string} message - Mensaje a mostrar
   * @param {Object} options - Opciones adicionales
   */
  error: function(message, options = {}) {
    this._show(message, this.TYPES.ERROR, options);
  },

  /**
   * Mostrar notificación informativa
   * @param {string} message - Mensaje a mostrar
   * @param {Object} options - Opciones adicionales
   */
  info: function(message, options = {}) {
    this._show(message, this.TYPES.INFO, options);
  },

  /**
   * Mostrar notificación de advertencia
   * @param {string} message - Mensaje a mostrar
   * @param {Object} options - Opciones adicionales
   */
  warning: function(message, options = {}) {
    this._show(message, this.TYPES.WARNING, options);
  },

  /**
   * Lógica interna de mostrar notificación
   * @private
   */
  _show: function(message, type, options = {}) {
    // Validar mensaje
    if (!message || !message.trim()) {
      console.warn('[NotificationService] Mensaje vacío o nulo');
      return;
    }

    const finalMessage = String(message).trim();
    const timestamp = new Date().toLocaleTimeString();

    // Log en consola si está habilitado
    if (this.config.consoleLogging) {
      const logMethod = type === this.TYPES.ERROR ? 'error' : type === this.TYPES.WARNING ? 'warn' : 'log';
      console[logMethod](`[${timestamp}] [${type.toUpperCase()}] ${finalMessage}`);
    }

    // Intentar mostrar con toast si está disponible
    if (this.config.useToastIfAvailable && window.showToast) {
      try {
        window.showToast(finalMessage, type);
        return;
      } catch (error) {
        console.warn('[NotificationService] Error al usar showToast:', error);
      }
    }

    // Fallback: alert del navegador (solo para errores críticos)
    if (type === this.TYPES.ERROR && options.fallbackAlert) {
      alert(`❌ Error: ${finalMessage}`);
      return;
    }

    // Fallback: crear notificación HTML simple si no hay toasts
    if (!window.showToast) {
      this._createFallbackNotification(finalMessage, type);
    }
  },

  /**
   * Crear notificación HTML como fallback si no hay sistema de toasts
   * @private
   */
  _createFallbackNotification: function(message, type) {
    // Si ya existe un contenedor, reutilizarlo
    let container = document.getElementById('notificationContainer');
    if (!container) {
      container = document.createElement('div');
      container.id = 'notificationContainer';
      container.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 999999;
        max-width: 400px;
        font-family: system-ui, -apple-system, sans-serif;
      `;
      document.body.appendChild(container);
    }

    // Crear notificación
    const notification = document.createElement('div');
    notification.style.cssText = `
      margin-bottom: 10px;
      padding: 12px 16px;
      border-radius: 4px;
      font-size: 14px;
      line-height: 1.5;
      animation: slideIn 0.3s ease-out;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    `;

    // Estilos según tipo
    const typeStyles = {
      success: {
        backgroundColor: '#d4edda',
        color: '#155724',
        borderLeft: '4px solid #28a745'
      },
      error: {
        backgroundColor: '#f8d7da',
        color: '#721c24',
        borderLeft: '4px solid #dc3545'
      },
      warning: {
        backgroundColor: '#fff3cd',
        color: '#856404',
        borderLeft: '4px solid #ffc107'
      },
      info: {
        backgroundColor: '#d1ecf1',
        color: '#0c5460',
        borderLeft: '4px solid #17a2b8'
      }
    };

    const styles = typeStyles[type] || typeStyles.info;
    Object.assign(notification.style, styles);

    notification.textContent = message;
    container.appendChild(notification);

    // Animation keyframes
    if (!document.querySelector('style[data-notification-animations]')) {
      const style = document.createElement('style');
      style.setAttribute('data-notification-animations', 'true');
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
      `;
      document.head.appendChild(style);
    }

    // Auto-remover después del timeout
    setTimeout(() => {
      notification.style.animation = 'slideOut 0.3s ease-in';
      setTimeout(() => {
        notification.remove();
      }, 300);
    }, this.config.timeout);
  },

  /**
   * Limpiar todas las notificaciones
   */
  clearAll: function() {
    const container = document.getElementById('notificationContainer');
    if (container) {
      container.innerHTML = '';
    }
    console.log('[NotificationService] Notificaciones limpiadas');
  },

  /**
   * Cambiar configuración
   * @param {Object} newConfig - Nueva configuración
   */
  setConfig: function(newConfig) {
    this.config = { ...this.config, ...newConfig };
    console.log('[NotificationService] Configuración seteada:', this.config);
  }
};

// Exportar para uso global
if (typeof window !== 'undefined') {
  window.NotificationService = NotificationService;
}
