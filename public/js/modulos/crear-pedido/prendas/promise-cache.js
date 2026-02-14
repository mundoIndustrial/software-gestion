/**
 * ================================================
 * PROMISE CACHE - FASE 1
 * ================================================
 * 
 * Patrón minimal para deduplicación de promises
 * Evita múltiples fetch del mismo recurso
 * 
 * Incluye en HTML ANTES de manejadores-variaciones.js:
 * <script src="/public/js/modulos/crear-pedido/prendas/promise-cache.js"></script>
 * 
 * @module PromiseCache
 * @version 1.0.0 (Fase 1 - Estable para producción)
 */

const PromiseCache = (() => {
    // Storage privado - no accesible desde fuera
    const cache = new Map();
    
    // Logger interno
    const log = (message, context = {}) => {
        console.log(`[PromiseCache] ${message}`, context);
    };

    return {
        /**
         * Guardar promise en caché
         * @param {string} key - Identificador único
         * @param {Promise} promise - Promise a guardar
         */
        set: (key, promise) => {
            if (!key || typeof key !== 'string') {
                console.error('[PromiseCache] set() - key debe ser string');
                return;
            }
            
            cache.set(key, promise);
            log('Promise guardada', { key, size: cache.size });
            
            // Auto-limpiar cuando se resuelve o rechaza
            Promise.resolve(promise)
                .finally(() => {
                    cache.delete(key);
                    log('Promise limpiada automáticamente', { key });
                });
        },
        
        /**
         * Obtener promise del caché
         * @param {string} key - Identificador
         * @returns {Promise|undefined}
         */
        get: (key) => {
            return cache.get(key);
        },
        
        /**
         * Verificar si existe promise en caché
         * @param {string} key - Identificador
         * @returns {boolean}
         */
        has: (key) => {
            return cache.has(key);
        },
        
        /**
         * Eliminar promise del caché manualmente
         * @param {string} key - Identificador
         */
        delete: (key) => {
            const deleted = cache.delete(key);
            if (deleted) {
                log('Promise eliminada manualmente', { key });
            }
        },
        
        /**
         * Limpiar TODO el caché (emergencia)
         */
        clear: () => {
            cache.clear();
            log('Cache limpiado completamente');
        },
        
        /**
         * Obtener cantidad de promises en caché
         * @returns {number}
         */
        size: () => {
            return cache.size;
        },
        
        /**
         * Obtener estado actual (para debugging)
         * @returns {object}
         */
        getStatus: () => {
            return {
                size: cache.size,
                keys: Array.from(cache.keys()),
                timestamp: new Date().toISOString()
            };
        }
    };
})();

// Auto-inicialización
console.log('[PromiseCache]  Inicializado y listo');
