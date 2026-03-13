/**
 * Infrastructure Layer - SessionStorage Insumo Repository
 * 
 * Implementación concreta de InsumoRepository usando SessionStorage
 * Puede reemplazarse con otra implementación (IndexedDB, API, etc) sin cambiar el código de negocio
 * 
 * DDD Principle: Implementación desacoplada de la interfaz
 */

class SessionStorageInsumoRepository extends InsumoRepository {
    constructor(httpClient) {
        super();
        this.httpClient = httpClient;
        this.cachePrefix = 'insumos_';
        this.cacheExpiry = 30 * 60 * 1000; // 30 minutos
    }

    /**
     * Obtiene insumos desde caché o HTTP
     */
    async obtenerInsumos(pedidoId, prendaId = null) {
        const cacheKey = this._generateCacheKey(pedidoId, prendaId);
        
        // Intentar obtener del caché primero
        const cached = this._getCached(cacheKey);
        if (cached) {
            console.log(`[InsumoRepository] Cache hit: ${cacheKey}`);
            return cached;
        }

        // Obtener del servidor
        try {
            let path = `/insumos/api/materiales/${pedidoId}`;
            if (prendaId) {
                path += `?prenda_id=${prendaId}`;
            }

            const datos = await this.httpClient.get(path);
            
            // Guardar en caché
            this._setCached(cacheKey, datos);
            
            console.log(`[InsumoRepository] Datos obtenidos y cacheados: ${cacheKey}`);
            return datos;
        } catch (error) {
            console.error('[InsumoRepository] Error obteniendo insumos:', error);
            throw new RepositoryError(`No se pudieron obtener insumos para pedido ${pedidoId}`, error);
        }
    }

    /**
     * Guarda insumos en el servidor (optativo cachear)
     */
    async guardarInsumos(pedidoId, prendaId, datos) {
        try {
            const response = await this.httpClient.post(
                `/insumos/api/materiales`,
                { pedidoId, prendaId, ...datos }
            );

            // Invalidar caché de este pedido
            this.limpiar(pedidoId);
            
            return response;
        } catch (error) {
            console.error('[InsumoRepository] Error guardando insumos:', error);
            throw new RepositoryError(`No se pudieron guardar insumos para pedido ${pedidoId}`, error);
        }
    }

    /**
     * Verifica si existe en caché
     */
    async existeEnCache(pedidoId, prendaId = null) {
        const cacheKey = this._generateCacheKey(pedidoId, prendaId);
        return this._isCacheValid(cacheKey);
    }

    /**
     * Limpia caché
     */
    async limpiar(pedidoId = null) {
        try {
            if (pedidoId === null) {
                // Limpiar TODO
                for (let i = sessionStorage.length - 1; i >= 0; i--) {
                    const key = sessionStorage.key(i);
                    if (key && key.startsWith(this.cachePrefix)) {
                        sessionStorage.removeItem(key);
                    }
                }
                console.log('[InsumoRepository] Caché completamente limpiado');
            } else {
                // Limpiar solo pedido específico
                for (let i = sessionStorage.length - 1; i >= 0; i--) {
                    const key = sessionStorage.key(i);
                    if (key && key.startsWith(`${this.cachePrefix}${pedidoId}_`)) {
                        sessionStorage.removeItem(key);
                    }
                }
                console.log(`[InsumoRepository] Caché limpiado para pedido ${pedidoId}`);
            }
        } catch (error) {
            console.error('[InsumoRepository] Error limpiando caché:', error);
        }
    }

    /**
     * Obtiene datos del caché
     * @private
     */
    _getCached(key) {
        try {
            const cached = sessionStorage.getItem(this.cachePrefix + key);
            if (!cached) return null;

            const data = JSON.parse(cached);
            
            // Verificar expiración
            if (Date.now() - data.timestamp > this.cacheExpiry) {
                sessionStorage.removeItem(this.cachePrefix + key);
                return null;
            }

            return data.value;
        } catch (error) {
            console.error('[InsumoRepository] Error leyendo caché:', error);
            return null;
        }
    }

    /**
     * Guarda datos en caché
     * @private
     */
    _setCached(key, value) {
        try {
            const cacheData = {
                value: value,
                timestamp: Date.now()
            };
            sessionStorage.setItem(this.cachePrefix + key, JSON.stringify(cacheData));
        } catch (error) {
            if (error.name === 'QuotaExceededError') {
                console.warn('[InsumoRepository] sessionStorage lleno, limpiando expirados...');
                this._clearExpired();
            } else {
                console.error('[InsumoRepository] Error guardando caché:', error);
            }
        }
    }

    /**
     * Verifica si caché es válido
     * @private
     */
    _isCacheValid(key) {
        try {
            const cached = sessionStorage.getItem(this.cachePrefix + key);
            if (!cached) return false;

            const data = JSON.parse(cached);
            return Date.now() - data.timestamp <= this.cacheExpiry;
        } catch {
            return false;
        }
    }

    /**
     * Genera clave de caché
     * @private
     */
    _generateCacheKey(pedidoId, prendaId) {
        return prendaId ? `${pedidoId}_${prendaId}` : `${pedidoId}_general`;
    }

    /**
     * Limpia items expirados
     * @private
     */
    _clearExpired() {
        const now = Date.now();
        for (let i = sessionStorage.length - 1; i >= 0; i--) {
            const key = sessionStorage.key(i);
            if (key && key.startsWith(this.cachePrefix)) {
                try {
                    const data = JSON.parse(sessionStorage.getItem(key));
                    if (now - data.timestamp > this.cacheExpiry) {
                        sessionStorage.removeItem(key);
                    }
                } catch (error) {
                    sessionStorage.removeItem(key);
                }
            }
        }
    }
}

/**
 * Custom Error para Repository
 */
class RepositoryError extends Error {
    constructor(message, originalError) {
        super(message);
        this.name = 'RepositoryError';
        this.originalError = originalError;
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { SessionStorageInsumoRepository, RepositoryError };
} else {
    window.SessionStorageInsumoRepository = SessionStorageInsumoRepository;
    window.RepositoryError = RepositoryError;
}
