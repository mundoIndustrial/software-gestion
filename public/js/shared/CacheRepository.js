/**
 * =====================================================
 * SHARED INFRASTRUCTURE - CACHE REPOSITORY
 * =====================================================
 * Abstracción centralizada para cacheo (sessionStorage, localStorage).
 * Elimina duplicación de lógica TTL y serialización en modules.
 *
 * Métodos públicos:
 *   - get(key) → value o null
 *   - set(key, value, ttl) 
 *   - has(key) → boolean
 *   - remove(key)
 *   - clear()
 *   - getOrFetch(key, fetcher, ttl) → async
 *
 * Custom Errors:
 *   - CacheError: Error general de cache
 */

class CacheRepository {
    /**
     * Obtiene un valor del cache
     * @param {string} key
     * @returns {*|null} Valor o null si no existe/expiró
     */
    get(key) {
        throw new Error('get(key) debe ser implementado por subclases');
    }

    /**
     * Guarda un valor en el cache
     * @param {string} key
     * @param {*} value - Puede ser primitivo u objeto
     * @param {number} [ttl] - Tiempo de vida en ms (opcional, sin TTL = indefinido)
     * @returns {void}
     */
    set(key, value, ttl = null) {
        throw new Error('set(key, value, ttl) debe ser implementado por subclases');
    }

    /**
     * Verifica si una clave existe y no ha expirado
     * @param {string} key
     * @returns {boolean}
     */
    has(key) {
        throw new Error('has(key) debe ser implementado por subclases');
    }

    /**
     * Elimina una clave del cache
     * @param {string} key
     * @returns {void}
     */
    remove(key) {
        throw new Error('remove(key) debe ser implementado por subclases');
    }

    /**
     * Limpia TODO el cache
     * @returns {void}
     */
    clear() {
        throw new Error('clear() debe ser implementado por subclases');
    }

    /**
     * Obtiene un valor del cache, o llama un fetcher si no existe/expiró
     * Útil para lazy-loading con fallback a API
     * 
     * @param {string} key
     * @param {Function} fetcher - async () => value
     * @param {number} [ttl] - TTL del cache en ms
     * @returns {Promise<*>} Valor cacheado o resultado de fetcher
     */
    async getOrFetch(key, fetcher, ttl = null) {
        const cached = this.get(key);
        if (cached !== null) {
            return cached;
        }

        try {
            const value = await fetcher();
            this.set(key, value, ttl);
            return value;
        } catch (error) {
            throw error;
        }
    }
}

// Custom error
class CacheError extends Error {
    constructor(message, originalError = null) {
        super(message);
        this.name = 'CacheError';
        this.originalError = originalError;
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { CacheRepository, CacheError };
}
window.CacheRepository = CacheRepository;
window.CacheError = CacheError;
