/**
 * EventBus - Sistema centralizado de eventos
 * 
 * Permite comunicación desacoplada entre servicios
 * Patrón: Publish/Subscribe
 */

class EventBus {
    constructor() {
        this.events = new Map();
        this.debugMode = false;
    }

    /**
     * Suscribirse a un evento
     * @param {string} eventName - Nombre del evento
     * @param {function} callback - Función a ejecutar
     * @returns {function} Función para desuscribirse
     */
    on(eventName, callback) {
        if (!this.events.has(eventName)) {
            this.events.set(eventName, []);
        }

        const listeners = this.events.get(eventName);
        listeners.push(callback);

        if (this.debugMode) {
            Logger.debug(`Listener agregado: ${eventName}`, 'EventBus');
        }

        // Retornar función para desuscribirse
        return () => {
            const index = listeners.indexOf(callback);
            if (index > -1) {
                listeners.splice(index, 1);
            }
        };
    }

    /**
     * Suscribirse a un evento una sola vez
     */
    once(eventName, callback) {
        const unsubscribe = this.on(eventName, (data) => {
            callback(data);
            unsubscribe();
        });
        return unsubscribe;
    }

    /**
     * Emitir un evento
     * @param {string} eventName - Nombre del evento
     * @param {*} data - Datos a pasar a los listeners
     */
    emit(eventName, data = null) {
        if (!this.events.has(eventName)) {
            if (this.debugMode) {
                Logger.debug(`Evento no tiene listeners: ${eventName}`, 'EventBus');
            }
            return;
        }

        const listeners = this.events.get(eventName);
        
        if (this.debugMode) {
            Logger.debug(`Emitiendo: ${eventName}`, 'EventBus', data);
        }

        // Ejecutar listeners de forma asincrónica para evitar bloqueos
        Promise.resolve().then(() => {
            listeners.forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    Logger.error(`Error en listener de ${eventName}`, 'EventBus', error);
                }
            });
        });
    }

    /**
     * Remover todos los listeners de un evento
     */
    off(eventName) {
        if (this.events.has(eventName)) {
            this.events.delete(eventName);
            if (this.debugMode) {
                Logger.debug(`Listeners removidos: ${eventName}`, 'EventBus');
            }
        }
    }

    /**
     * Remover todos los eventos
     */
    clear() {
        this.events.clear();
        if (this.debugMode) {
            Logger.debug('Todos los eventos cleared', 'EventBus');
        }
    }

    /**
     * Obtener lista de eventos registrados
     */
    getEventNames() {
        return Array.from(this.events.keys());
    }

    /**
     * Obtener cantidad de listeners para un evento
     */
    getListenerCount(eventName) {
        return this.events.has(eventName) 
            ? this.events.get(eventName).length 
            : 0;
    }

    /**
     * Habilitar modo debug
     */
    enableDebug(enabled = true) {
        this.debugMode = enabled;
        if (enabled) {
            Logger.debug('Modo DEBUG habilitado', 'EventBus');
        }
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = EventBus;
}
window.EventBus = EventBus;
