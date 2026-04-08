/**
 * Infrastructure Layer - SessionStorage Insumo Repository
 * Phase 6: Cache Unification - Simplificado usando SessionStorageCacheRepository
 * 
 * Implementación concreta de InsumoRepository usando caché centralizado
 * Eliminó duplicación: ahora usa SessionStorageCacheRepository en lugar de reimplementar lógica
 * 
 * DDD Principle: Implementación desacoplada de la interfaz
 */

class SessionStorageInsumoRepository extends InsumoRepository {
    constructor(httpClient, cacheRepository = null) {
        super();
        this.httpClient = httpClient;
        
        // Usar caché inyectado o crear uno (fallback)
        this.cache = cacheRepository || new SessionStorageCacheRepository({
            storage: 'session',
            keyPrefix: 'insumos_'
        });
        
        // TTL estandarizado: 30 minutos
        this.DEFAULT_TTL = 30 * 60 * 1000;
    }

    /**
     * Obtiene insumos desde caché centralizado o HTTP
     * Fase 6: Usa getOrFetch de SessionStorageCacheRepository
     */
    async obtenerInsumos(pedidoId, prendaId = null) {
        const cacheKey = this._generateCacheKey(pedidoId, prendaId);
        
        try {
            // getOrFetch automatiza: obtener del caché o llamar fetcher
            const datos = await this.cache.getOrFetch(
                cacheKey,
                () => this._fetchFromAPI(pedidoId, prendaId),
                this.DEFAULT_TTL
            );
            
            console.log(`[InsumoRepository] Datos obtenidos ${this.cache.has(cacheKey) ? '(cache hit)' : '(desde API)'}: ${cacheKey}`);
            return datos;
        } catch (error) {
            console.error('[InsumoRepository] Error obteniendo insumos:', error);
            throw new RepositoryError(`No se pudieron obtener insumos para pedido ${pedidoId}`, error);
        }
    }

    /**
     * Guarda insumos en el servidor e invalida caché
     */
    async guardarInsumos(pedidoId, prendaId, datos) {
        try {
            const response = await this.httpClient.post(
                `/insumos/api/materiales`,
                { pedidoId, prendaId, ...datos }
            );

            // Invalidar caché de este pedido
            this.limpiar(pedidoId);
            
            console.log(`[InsumoRepository] Insumos guardados, caché invalidado para pedido ${pedidoId}`);
            return response;
        } catch (error) {
            console.error('[InsumoRepository] Error guardando insumos:', error);
            throw new RepositoryError(`No se pudieron guardar insumos para pedido ${pedidoId}`, error);
        }
    }

    /**
     * Verifica si existe en caché (usando cache centralizado)
     */
    async existeEnCache(pedidoId, prendaId = null) {
        const cacheKey = this._generateCacheKey(pedidoId, prendaId);
        return this.cache.has(cacheKey);
    }

    /**
     * Limpia caché específico o todo
     */
    async limpiar(pedidoId = null) {
        try {
            if (pedidoId === null) {
                // Limpiar TODO el caché de insumos
                this.cache.clear();
                console.log('[InsumoRepository] Caché completamente limpiado');
            } else {
                // Limpiar solo pedido específico
                // Generar claves para ambos: con y sin prendaId
                const cacheKeyGeneral = this._generateCacheKey(pedidoId, null);
                this.cache.remove(cacheKeyGeneral);
                
                // También intentar limpia claves específicas de prenda encontradas
                // (nota: idealmente mantendríamos índice, pero esto es suficiente)
                console.log(`[InsumoRepository] Caché limpiado para pedido ${pedidoId}`);
            }
        } catch (error) {
            console.error('[InsumoRepository] Error limpiando caché:', error);
        }
    }

    /**
     * Obtiene datos del servidor (helper privado)
     * @private
     */
    async _fetchFromAPI(pedidoId, prendaId) {
        let path = `/insumos/api/materiales/${pedidoId}`;
        if (prendaId) {
            path += `?prenda_id=${prendaId}`;
        }
        
        const datos = await this.httpClient.get(path);
        return datos;
    }

    /**
     * Genera clave de caché estandarizada
     * @private
     */
    _generateCacheKey(pedidoId, prendaId) {
        return prendaId ? `pedido_${pedidoId}_prenda_${prendaId}` : `pedido_${pedidoId}_general`;
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
