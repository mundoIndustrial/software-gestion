/**
 * Domain Layer - Repository Interface
 * 
 * Define el contrato que deben cumplir todas las implementaciones
 * No depende de tecnología específica (localStorage, sessionStorage, etc)
 * 
 * DDD Principle: Domain objects no deben conocer detalles de implementación
 */

class InsumoRepository {
    /**
     * Obtiene insumos por pedido
     * @param {number} pedidoId - ID del pedido
     * @param {number|null} prendaId - ID de la prenda (opcional)
     * @returns {Promise<{nombre_prenda: string, materiales: Array}>}
     */
    async obtenerInsumos(pedidoId, prendaId = null) {
        throw new Error('obtenerInsumos() debe ser implementado por subclases');
    }

    /**
     * Guarda insumos
     * @param {number} pedidoId - ID del pedido
     * @param {number} prendaId - ID de la prenda
     * @param {Object} datos - Datos de insumos
     * @returns {Promise<boolean>}
     */
    async guardarInsumos(pedidoId, prendaId, datos) {
        throw new Error('guardarInsumos() debe ser implementado por subclases');
    }

    /**
     * Verifica si hay insumos en caché
     * @param {number} pedidoId
     * @param {number|null} prendaId
     * @returns {Promise<boolean>}
     */
    async existeEnCache(pedidoId, prendaId = null) {
        throw new Error('existeEnCache() debe ser implementado por subclases');
    }

    /**
     * Limpia caché específico o todo
     * @param {number|null} pedidoId - Si null, limpia todo
     */
    async limpiar(pedidoId = null) {
        throw new Error('limpiar() debe ser implementado por subclases');
    }
}

// Exportar para uso en otros módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = InsumoRepository;
} else {
    window.InsumoRepository = InsumoRepository;
}
