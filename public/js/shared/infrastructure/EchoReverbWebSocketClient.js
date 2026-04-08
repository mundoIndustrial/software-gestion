/**
 * =====================================================
 * SHARED INFRASTRUCTURE - ECHO REVERB WEBSOCKET CLIENT
 * =====================================================
 * Implementación concreta del WebSocketClient usando Laravel Echo + Reverb.
 *
 * Dependencias:
 *   - window.EchoInstance (de resources/js/bootstrap.js)
 *   - window.WebSocketClient (public/js/shared/WebSocketClient.js)
 */

class EchoReverbWebSocketClient extends WebSocketClient {
    constructor(echoInstance) {
        super();
        
        if (!echoInstance) {
            throw new WebSocketError('EchoInstance no disponible. Asegúrate de que resources/js/bootstrap.js se cargó ANTES.');
        }
        
        this.echo = echoInstance;
    }

    /**
     * Suscribe a un evento en un canal público
     * @param {string} channel - Nombre del canal
     * @param {string} event - Nombre del evento
     * @param {Function} callback - Handler(data)
     * @returns {Object} Subscription object
     */
    subscribe(channel, event, callback) {
        if (!channel || !event || typeof callback !== 'function') {
            throw new Error('channel, event (con punto) y callback son requeridos');
        }

        try {
            return this.echo.channel(channel).listen(event, callback);
        } catch (error) {
            throw new WebSocketError(`Error subscribing to ${channel}.${event}`, error);
        }
    }

    /**
     * Cancela todas las suscripciones a un canal
     * @param {string} channel
     */
    unsubscribe(channel) {
        if (!channel) throw new Error('channel es requerido');
        
        try {
            this.echo.leaveChannel(channel);
        } catch (error) {
            throw new WebSocketError(`Error unsubscribing from ${channel}`, error);
        }
    }

    /**
     * Accede a un canal PRIVADO (requiere auth)
     * @param {string} channel - Ej: 'private-pedidos.4'
     * @returns {Object} Private channel object con .listen(), .whisper(), etc.
     */
    private(channel) {
        if (!channel) throw new Error('channel es requerido');
        
        try {
            return this.echo.private(channel);
        } catch (error) {
            throw new WebSocketError(`Error accessing private channel ${channel}`, error);
        }
    }

    /**
     * Accede a un canal de PRESENCIA (con datos de presencia)
     * @param {string} channel - Ej: 'presence-team.5'
     * @returns {Object} Presence channel object con .listen(), .here(), .joining(), .leaving()
     */
    join(channel) {
        if (!channel) throw new Error('channel es requerido');
        
        try {
            return this.echo.join(channel);
        } catch (error) {
            throw new WebSocketError(`Error joining presence channel ${channel}`, error);
        }
    }

    /**
     * Verifica si la conexión WebSocket está activa
     * @returns {boolean}
     */
    isConnected() {
        try {
            // Verificar el estado del socket subyacente
            const connector = this.echo?.connector;
            if (!connector) return false;
            
            // Para Pusher/Reverb (WebSocket)
            if (connector.socket?.readyState === WebSocket.OPEN) return true;
            if (connector.socket?.connected) return true;
            
            return false;
        } catch {
            return false;
        }
    }

    /**
     * Envía un mensaje a través de un canal privado (whisper)
     * @param {string} channel - Canal privado
     * @param {string} event - Evento
     * @param {*} data - Datos a enviar
     */
    whisper(channel, event, data) {
        if (!channel || !event) {
            throw new Error('channel y event son requeridos');
        }

        try {
            const privChannel = this.echo.private(channel);
            return privChannel.whisper(event, data);
        } catch (error) {
            throw new WebSocketError(`Error whispering to ${channel}.${event}`, error);
        }
    }

    /**
     * Obtiene información del estado de la conexión
     * @returns {{connected: boolean, connector: string}}
     */
    getStatus() {
        return {
            connected: this.isConnected(),
            connector: this.echo?.connector?.name || 'unknown',
        };
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { EchoReverbWebSocketClient };
} else {
    window.EchoReverbWebSocketClient = EchoReverbWebSocketClient;
}
