/**
 * WizardEventBus
 * 
 * Sistema de eventos centralizado para desacoplar componentes.
 * 
 * Responsabilidades ÚNICAS:
 * - Publicar eventos
 * - Suscribirse a eventos
 * - Desuscribirse (crítico para evitar memory leaks)
 * - Filtrar eventos por prioridad
 * 
 * Patrón Observer implementado correctamente:
 * - Sin referencias circulares
 * - Limpieza garantizada
 * - Orden de ejecución predecible
 * - Aislamiento de errores
 */

class WizardEventBus {
    constructor() {
        this.subscribers = new Map();
        this.eventHistory = [];
        this.maxHistorySize = 50;
    }

    /**
     * Suscribirse a un evento
     * @param {string} eventName - Nombre del evento
     * @param {Function} handler - Función a ejecutar
     * @param {Object} options - { once: bool, priority: number }
     * @returns {Function} - Función para desuscribirse
     */
    subscribe(eventName, handler, options = {}) {
        if (typeof eventName !== 'string') {
            throw new Error('eventName debe ser string');
        }
        if (typeof handler !== 'function') {
            throw new Error('handler debe ser función');
        }

        const { once = false, priority = 0 } = options;

        // Crear lista de suscriptores si no existe
        if (!this.subscribers.has(eventName)) {
            this.subscribers.set(eventName, []);
        }

        const subscription = {
            handler,
            once,
            priority,
            id: `${Date.now()}-${Math.random()}`  // ID único para desuscripción
        };

        // Insertar ordenado por prioridad (mayor primero)
        const handlers = this.subscribers.get(eventName);
        const index = handlers.findIndex(s => s.priority < priority);
        if (index === -1) {
            handlers.push(subscription);
        } else {
            handlers.splice(index, 0, subscription);
        }

        // CRITICAL: Retornar función para desuscripción sin referencias
        return () => this._unsubscribe(eventName, subscription.id);
    }

    /**
     * Suscribirse una única vez
     */
    once(eventName, handler, priority = 0) {
        return this.subscribe(eventName, handler, { once: true, priority });
    }

    /**
     * Publicar evento (disparo síncrono)
     */
    emit(eventName, data = {}) {
        this._recordEvent(eventName, data);

        const handlers = this.subscribers.get(eventName) || [];
        const handlersToRemove = [];

        // Ejecutar en orden de prioridad
        handlers.forEach((subscription) => {
            try {
                subscription.handler(data);
                if (subscription.once) {
                    handlersToRemove.push(subscription.id);
                }
            } catch (error) {
                console.error(`Error en handler para ${eventName}:`, error);
            }
        });

        // Limpiar suscriptores únicos
        handlersToRemove.forEach(id => {
            this._unsubscribe(eventName, id);
        });
    }

    /**
     * Publicar evento asíncrono (retorna Promise)
     */
    async emitAsync(eventName, data = {}) {
        this._recordEvent(eventName, data);

        const handlers = this.subscribers.get(eventName) || [];
        const handlersToRemove = [];

        for (const subscription of handlers) {
            try {
                await Promise.resolve(subscription.handler(data));
                if (subscription.once) {
                    handlersToRemove.push(subscription.id);
                }
            } catch (error) {
                console.error(`Error en handler async para ${eventName}:`, error);
            }
        }

        // Limpiar suscriptores únicos
        handlersToRemove.forEach(id => {
            this._unsubscribe(eventName, id);
        });
    }

    /**
     * Obtener número de suscriptores a un evento
     */
    getSubscriberCount(eventName) {
        return this.subscribers.get(eventName)?.length || 0;
    }

    /**
     * Desuscribirse de todos los eventos
     */
    clear() {
        this.subscribers.clear();
    }

    /**
     * Obtener historial de eventos (para debugging)
     */
    getEventHistory() {
        return [...this.eventHistory];
    }

    // ========== PRIVADOS ==========

    _unsubscribe(eventName, subscriptionId) {
        const handlers = this.subscribers.get(eventName);
        if (!handlers) return;

        const index = handlers.findIndex(s => s.id === subscriptionId);
        if (index !== -1) {
            handlers.splice(index, 1);
        }

        // Limpiar entrada si no hay más suscriptores
        if (handlers.length === 0) {
            this.subscribers.delete(eventName);
        }
    }

    _recordEvent(eventName, data) {
        this.eventHistory.push({
            event: eventName,
            timestamp: Date.now(),
            data: JSON.parse(JSON.stringify(data))  // Deep copy para evitar mutaciones
        });

        // Limitar tamaño del historial
        if (this.eventHistory.length > this.maxHistorySize) {
            this.eventHistory.shift();
        }
    }
}

// Exportar para uso modular
if (typeof module !== 'undefined' && module.exports) {
    module.exports = WizardEventBus;
}
