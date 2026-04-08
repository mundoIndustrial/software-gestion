/**
 * =====================================================
 * SHARED INFRASTRUCTURE - WEBSOCKET CLIENT
 * =====================================================
 * Abstracción centralizada para WebSocket/real-time.
 * Elimina duplicación de inicialización de Echo en 19+ archivos.
 *
 * Métodos públicos:
 *   - subscribe(channel, event, callback)
 *   - unsubscribe(channel)
 *   - private(channel) → Para canales privados
 *   - join(channel) → Para presencia
 *   - isConnected()
 *
 * Nota: Requiere que window.EchoInstance esté disponible.
 * Usar window.waitForEcho() o window.websocket para esperar.
 */

class WebSocketClient {
    /**
     * Se subscribe a un evento en un canal público
     * @param {string} channel - Nombre del canal
     * @param {string} event - Nombre del evento
     * @param {Function} callback - Handler
     * @returns {Object} Subscription object (with stopListening())
     */
    subscribe(channel, event, callback) {
        throw new Error('subscribe() debe ser implementado por subclases');
    }

    /**
     * Cancela todas las suscripciones a un canal
     * @param {string} channel
     * @returns {void}
     */
    unsubscribe(channel) {
        throw new Error('unsubscribe() debe ser implementado por subclases');
    }

    /**
     * Accede a un canal PRIVADO (requiere autenticación)
     * @param {string} channel
     * @returns {Object} Private channel object
     */
    private(channel) {
        throw new Error('private() debe ser implementado por subclases');
    }

    /**
     * Accede a un canal de PRESENCIA (con información de usuarios)
     * @param {string} channel
     * @returns {Object} Presence channel object
     */
    join(channel) {
        throw new Error('join() debe ser implementado por subclases');
    }

    /**
     * Verifica si el WebSocket está conectado
     * @returns {boolean}
     */
    isConnected() {
        throw new Error('isConnected() debe ser implementado por subclases');
    }

    /**
     * Envía un mensaje a través de un canal privado (whisper)
     * @param {string} channel
     * @param {string} event
     * @param {*} data
     * @returns {void}
     */
    whisper(channel, event, data) {
        throw new Error('whisper() debe ser implementado por subclases');
    }
}

// Custom error
class WebSocketError extends Error {
    constructor(message, originalError = null) {
        super(message);
        this.name = 'WebSocketError';
        this.originalError = originalError;
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = { WebSocketClient, WebSocketError };
}
window.WebSocketClient = WebSocketClient;
window.WebSocketError = WebSocketError;
