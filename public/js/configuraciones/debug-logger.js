/**
 * 📊 Debug Logger - Control centralizado de logs
 * Desactiva logs pesados en producción
 */

// ✅ Detectar modo: usar variable global o localStorage
const isProduction = !window.DEBUG_MODE && (
    document.documentElement.getAttribute('data-env') === 'production' ||
    localStorage.getItem('app-env') === 'production' ||
    window.location.hostname !== 'localhost'
);

window.DEBUG_LOGGER = {
    /**
     * Log general - solo en desarrollo
     */
    log: (message, data = null) => {
        if (!isProduction) {
            if (data) {
                console.log(message, data);
            } else {
                console.log(message);
            }
        }
    },

    /**
     * Warn - siempre mostrar
     */
    warn: (message, data = null) => {
        if (data) {
            console.warn(message, data);
        } else {
            console.warn(message);
        }
    },

    /**
     * Error - siempre mostrar
     */
    error: (message, data = null) => {
        if (data) {
            console.error(message, data);
        } else {
            console.error(message);
        }
    },

    /**
     * Timing - medir performance en desarrollo
     */
    time: (label) => {
        if (!isProduction) {
            console.time(label);
        }
    },

    timeEnd: (label) => {
        if (!isProduction) {
            console.timeEnd(label);
        }
    },

    /**
     * Check si está en modo debug
     */
    isDebug: () => !isProduction
};

// Para máximo rendimiento en producción, reemplazar console.log con noop
if (isProduction) {
    window.DEBUG_LOG = () => {};
} else {
    window.DEBUG_LOG = console.log.bind(console);
}
