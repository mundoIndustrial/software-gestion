/**
 * Domain Layer - Repository Interface
 * =====================================================
 * Define el contrato que deben cumplir todas las implementaciones
 * de acceso a datos para el módulo supervisor-pedidos.
 *
 * DDD Principle: El dominio no conoce detalles de infraestructura.
 */

class PedidoRepository {
    // ===== FILTROS =====

    /**
     * Obtiene opciones de filtro para una columna
     * @param {string} campo - Campo a filtrar (numero, cliente, estado, asesora, forma_pago)
     * @returns {Promise<{opciones: string[]}>}
     */
    async getFilterOptions(campo) {
        throw new Error('getFilterOptions() debe ser implementado por subclases');
    }

    // ===== SELECCIÓN =====

    /**
     * Marca un pedido como seleccionado
     * @param {number|string} pedidoId
     * @returns {Promise<{success: boolean}>}
     */
    async selectOrder(pedidoId) {
        throw new Error('selectOrder() debe ser implementado por subclases');
    }

    /**
     * Desmarca un pedido seleccionado
     * @param {number|string} pedidoId
     * @returns {Promise<{success: boolean}>}
     */
    async deselectOrder(pedidoId) {
        throw new Error('deselectOrder() debe ser implementado por subclases');
    }

    /**
     * Obtiene todas las selecciones guardadas
     * @returns {Promise<{success: boolean, selecciones: number[]}>}
     */
    async getSelections() {
        throw new Error('getSelections() debe ser implementado por subclases');
    }

    // ===== EDICIÓN DE PEDIDO =====

    /**
     * Obtiene datos del pedido para edición
     * @param {number|string} ordenId
     * @returns {Promise<{success: boolean, orden: Object, colores: Array, telas: Array}>}
     */
    async getOrderEditData(ordenId) {
        throw new Error('getOrderEditData() debe ser implementado por subclases');
    }

    /**
     * Actualiza un pedido
     * @param {number|string} ordenId
     * @param {FormData} formData
     * @returns {Promise<{success: boolean, message: string}>}
     */
    async updateOrder(ordenId, formData) {
        throw new Error('updateOrder() debe ser implementado por subclases');
    }

    /**
     * Elimina una imagen de un pedido
     * @param {string} tipo - Tipo de imagen (prenda, logo, tela)
     * @param {number|string} imageId
     * @returns {Promise<{success: boolean}>}
     */
    async deleteImage(tipo, imageId) {
        throw new Error('deleteImage() debe ser implementado por subclases');
    }

    /**
     * Calcula la fecha estimada de entrega
     * @param {number|string} ordenId
     * @param {number} diasEntrega
     * @returns {Promise<{success: boolean, fecha_estimada: string, fecha_estimada_iso: string}>}
     */
    async calculateEstimatedDate(ordenId, diasEntrega) {
        throw new Error('calculateEstimatedDate() debe ser implementado por subclases');
    }

    // ===== NAVEGACIÓN AJAX =====

    /**
     * Obtiene contenido HTML de una página via AJAX
     * @param {string} url
     * @returns {Promise<string>} HTML string
     */
    async fetchPageContent(url) {
        throw new Error('fetchPageContent() debe ser implementado por subclases');
    }

    /**
     * Obtiene datos JSON del listado de pedidos para renderizado cliente
     * @param {string} url
     * @returns {Promise<object>}
     */
    async fetchOrdersData(url) {
        throw new Error('fetchOrdersData() debe ser implementado por subclases');
    }
}

// Errores de dominio
class PedidoValidationError extends Error {
    constructor(message) {
        super(message);
        this.name = 'PedidoValidationError';
    }
}

class PedidoBusinessError extends Error {
    constructor(message) {
        super(message);
        this.name = 'PedidoBusinessError';
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { PedidoRepository, PedidoValidationError, PedidoBusinessError };
} else {
    window.PedidoRepository = PedidoRepository;
    window.PedidoValidationError = PedidoValidationError;
    window.PedidoBusinessError = PedidoBusinessError;
}
