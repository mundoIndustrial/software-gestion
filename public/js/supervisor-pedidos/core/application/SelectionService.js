/**
 * Application Layer - SelectionService
 * =====================================================
 * Lógica de negocio para selección múltiple de pedidos.
 * Persiste el estado de selección en el servidor.
 *
 * Responsabilidades:
 *   - Seleccionar/deseleccionar pedidos
 *   - Cargar selecciones guardadas
 *   - Revertir UI si el servidor falla
 */

class SelectionService {
    constructor(repository) {
        this.repository = repository;
    }

    /**
     * Selecciona un pedido en el servidor
     * @param {number|string} pedidoId
     * @returns {Promise<boolean>} true si fue exitoso
     */
    async select(pedidoId) {
        if (!pedidoId) {
            throw new PedidoValidationError('pedidoId es requerido');
        }

        const data = await this.repository.selectOrder(pedidoId);
        return data.success === true;
    }

    /**
     * Deselecciona un pedido en el servidor
     * @param {number|string} pedidoId
     * @returns {Promise<boolean>} true si fue exitoso
     */
    async deselect(pedidoId) {
        if (!pedidoId) {
            throw new PedidoValidationError('pedidoId es requerido');
        }

        const data = await this.repository.deselectOrder(pedidoId);
        return data.success === true;
    }

    /**
     * Carga las selecciones guardadas del servidor
     * @returns {Promise<number[]>} Array de IDs seleccionados
     */
    async loadSavedSelections() {
        const data = await this.repository.getSelections();

        if (data && data.success && Array.isArray(data.selecciones)) {
            return data.selecciones;
        }

        return [];
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { SelectionService };
} else {
    window.SelectionService = SelectionService;
}
