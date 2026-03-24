/**
 * Toast Notification Service
 * 
 * Servicio centralizado para mostrar notificaciones toast (mensajes temporales)
 * - Success (verde)
 * - Error (rojo)
 * - Info (azul)
 * 
 * USO:
 * ====
 * ToastNotificationService.success('Cambios guardados');
 * ToastNotificationService.error('Error al guardar', 'Error');
 * ToastNotificationService.info('Procesando...');
 */

class ToastNotificationService {
    constructor() {
        this.container = null;
        this.toasts = new Map();
        this._initContainer();
    }

    /**
     * Inicializar contenedor de toasts si no existe
     */
    _initContainer() {
        if (!document.getElementById('toastContainer')) {
            this.container = document.createElement('div');
            this.container.id = 'toastContainer';
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        } else {
            this.container = document.getElementById('toastContainer');
        }
    }

    /**
     * Mostrar notificación de éxito
     * @param {string} message - Mensaje a mostrar
     * @param {string} title - Título opcional
     */
    success(message, title = 'Éxito') {
        console.log('[ToastNotificationService] ✓ Success:', message);
        return this.show(message, 'success', title);
    }

    /**
     * Mostrar notificación de error
     * @param {string} message - Mensaje a mostrar
     * @param {string} title - Título opcional
     */
    error(message, title = 'Error') {
        console.log('[ToastNotificationService] ✗ Error:', message);
        return this.show(message, 'error', title);
    }

    /**
     * Mostrar notificación de información
     * @param {string} message - Mensaje a mostrar
     * @param {string} title - Título opcional
     */
    info(message, title = 'Información') {
        console.log('[ToastNotificationService] ℹ Info:', message);
        return this.show(message, 'info', title);
    }

    /**
     * Mostrar notificación genérica
     * @param {string} message - Mensaje a mostrar
     * @param {string} type - Tipo: 'success', 'error', 'info'
     * @param {string} title - Título opcional
     * @returns {string} ID del toast creado
     */
    show(message, type = 'info', title = '') {
        this._initContainer();

        // Crear elemento toast
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;

        // Determinar icono según tipo
        const icons = {
            'success': '✓',
            'error': '✕',
            'info': 'ℹ'
        };
        const icon = icons[type] || icons['info'];

        // Generar ID único
        const toastId = 'toast-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        toast.id = toastId;

        // Construir HTML del toast
        toast.innerHTML = `
            <div class="toast-icon">${icon}</div>
            <div class="toast-content">
                ${title ? `<div class="toast-title">${title}</div>` : ''}
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" onclick="ToastNotificationService.getInstance().remove('${toastId}')">×</button>
        `;

        // Agregar al contenedor
        this.container.appendChild(toast);

        // Guardar referencia
        this.toasts.set(toastId, toast);

        // Auto-eliminar después de 5 segundos
        setTimeout(() => {
            this.remove(toastId);
        }, 5000);

        console.log(`[ToastNotificationService] Toast ${type} mostrado:`, { id: toastId, message, title });

        return toastId;
    }

    /**
     * Eliminar un toast específico
     * @param {string} toastId - ID del toast a eliminar
     */
    remove(toastId) {
        const toast = document.getElementById(toastId);
        if (toast && !toast.classList.contains('removing')) {
            toast.classList.add('removing');

            // Esperar a que termine la animación antes de eliminar
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
                this.toasts.delete(toastId);
            }, 300);

            console.log(`[ToastNotificationService] Toast eliminado:`, toastId);
        }
    }

    /**
     * Limpiar todos los toasts activos
     */
    clearAll() {
        const toastIds = Array.from(this.toasts.keys());
        toastIds.forEach(toastId => this.remove(toastId));
        console.log(`[ToastNotificationService] Se limpiaron ${toastIds.length} toasts`);
    }

    /**
     * Obtener instancia singleton del servicio
     */
    static getInstance() {
        if (!window.toastNotificationServiceInstance) {
            window.toastNotificationServiceInstance = new ToastNotificationService();
        }
        return window.toastNotificationServiceInstance;
    }
}

// Crear instancia global disponible
const ToastNotificationService_Instance = ToastNotificationService.getInstance();

// Hacer disponible globalmente para onclick directo en HTML
window.ToastNotificationService = ToastNotificationService;
