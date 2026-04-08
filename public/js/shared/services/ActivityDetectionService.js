/**
 * ActivityDetectionService - DDD Service Layer
 * Maneja detección de actividad del usuario y visibilidad de página
 * Responsabilidad única: Rastrear estado de actividad
 * 
 * Uso: const detector = new ActivityDetectionService(callbacks)
 */

class ActivityDetectionService {
    constructor(callbacks = {}) {
        this.debug = callbacks.debug || false;
        this.onActivityDetected = callbacks.onActivityDetected || (() => {});
        this.onInactivityDetected = callbacks.onInactivityDetected || (() => {});
        this.onVisibilityChange = callbacks.onVisibilityChange || (() => {});
        
        this.isVisible = true;
        this.hasFocus = true;
        this.userActivityTimeout = null;
        this.activityDebounceTimeout = null;
        this.inactivityThreshold = 300000; // 5 minutos
        
        this.init();
    }

    /**
     * Inicializar detectores
     */
    init() {
        this.setupActivityDetection();
        this.setupVisibilityDetection();
        if (this.debug) console.log('[ActivityDetectionService] Iniciado');
    }

    /**
     * Detectar actividad del usuario con debounce
     */
    setupActivityDetection() {
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'click', 'focus'];
        
        events.forEach(event => {
            document.addEventListener(event, () => {
                this.onUserActivityDebounced();
            }, { passive: true });
        });
    }

    /**
     * Detectar visibilidad de página y foco
     */
    setupVisibilityDetection() {
        document.addEventListener('visibilitychange', () => {
            this.isVisible = !document.hidden;
            this.onVisibilityChange({
                isVisible: this.isVisible,
                hasFocus: this.hasFocus
            });
        });

        window.addEventListener('focus', () => {
            this.hasFocus = true;
            this.onVisibilityChange({
                isVisible: this.isVisible,
                hasFocus: this.hasFocus
            });
        });

        window.addEventListener('blur', () => {
            this.hasFocus = false;
            this.onVisibilityChange({
                isVisible: this.isVisible,
                hasFocus: this.hasFocus
            });
        });
    }

    /**
     * Manejar actividad con debounce
     */
    onUserActivityDebounced() {
        if (this.activityDebounceTimeout) {
            clearTimeout(this.activityDebounceTimeout);
        }
        
        this.activityDebounceTimeout = setTimeout(() => {
            this.onUserActivity();
        }, 500);
    }

    /**
     * Procesar actividad del usuario
     */
    onUserActivity() {
        clearTimeout(this.userActivityTimeout);
        this.onActivityDetected();
        
        // Marcar como activo por N minutos
        this.userActivityTimeout = setTimeout(() => {
            this.onInactivityDetected();
        }, this.inactivityThreshold);
    }

    /**
     * Obtener estado actual
     */
    getStatus() {
        return {
            isVisible: this.isVisible,
            hasFocus: this.hasFocus,
            isActive: this.userActivityTimeout !== null
        };
    }

    /**
     * Destruir servicio
     */
    destroy() {
        if (this.userActivityTimeout) clearTimeout(this.userActivityTimeout);
        if (this.activityDebounceTimeout) clearTimeout(this.activityDebounceTimeout);
    }
}

// Exportar como módulo
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ActivityDetectionService;
}
