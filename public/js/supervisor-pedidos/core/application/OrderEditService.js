/**
 * Application Layer - OrderEditService
 * =====================================================
 * Lógica de negocio para edición de pedidos.
 * Gestiona carga de datos, actualización, imágenes y fecha estimada.
 *
 * Responsabilidades:
 *   - Cargar datos del pedido para edición
 *   - Guardar cambios del formulario
 *   - Eliminar imágenes (inmediata o marcada para lote)
 *   - Calcular fecha estimada de entrega
 */

class OrderEditService {
    constructor(repository) {
        this.repository = repository;
        this._imagenesParaEliminar = [];
    }

    /**
     * Carga los datos del pedido para edición
     * @param {number|string} ordenId
     * @returns {Promise<{orden: Object, colores: Array, telas: Array}>}
     */
    async loadOrderData(ordenId) {
        if (!ordenId) {
            throw new PedidoValidationError('ordenId es requerido');
        }

        const data = await this.repository.getOrderEditData(ordenId);

        if (!data.success) {
            throw new PedidoBusinessError(data.message || 'Error al cargar datos del pedido');
        }

        return {
            orden: data.orden,
            colores: data.colores || [],
            telas: data.telas || [],
        };
    }

    /**
     * Guarda cambios del formulario de edición
     * @param {number|string} ordenId
     * @param {FormData} formData
     * @returns {Promise<{success: boolean, message: string}>}
     */
    async saveOrder(ordenId, formData) {
        if (!ordenId) {
            throw new PedidoValidationError('ordenId es requerido');
        }

        const data = await this.repository.updateOrder(ordenId, formData);

        if (!data.success) {
            throw new PedidoBusinessError(data.message || 'Error al actualizar pedido');
        }

        return data;
    }

    /**
     * Elimina una imagen inmediatamente
     * @param {string} tipo - Tipo de imagen (prenda, logo, tela) 
     * @param {number|string} imageId
     * @returns {Promise<{success: boolean}>}
     */
    async deleteImageNow(tipo, imageId) {
        if (!tipo || !imageId) {
            throw new PedidoValidationError('tipo e imageId son requeridos');
        }

        const data = await this.repository.deleteImage(tipo, imageId);

        if (!data.success) {
            throw new PedidoBusinessError(data.message || 'Error al eliminar imagen');
        }

        return data;
    }

    /**
     * Marca una imagen para eliminación diferida (dentro del modal)
     * @param {number|string} imageId
     */
    markImageForDeletion(imageId) {
        if (!this._imagenesParaEliminar.includes(imageId)) {
            this._imagenesParaEliminar.push(imageId);
        }
    }

    /**
     * Obtiene las imágenes marcadas para eliminación
     * @returns {number[]}
     */
    getMarkedImages() {
        return [...this._imagenesParaEliminar];
    }

    /**
     * Limpia la lista de imágenes pendientes de eliminación
     */
    clearMarkedImages() {
        this._imagenesParaEliminar = [];
    }

    /**
     * Calcula la fecha estimada de entrega
     * @param {number|string} ordenId
     * @param {number} diasEntrega
     * @returns {Promise<{fecha_estimada: string, fecha_estimada_iso: string}>}
     */
    async calculateEstimatedDate(ordenId, diasEntrega) {
        if (!ordenId) {
            throw new PedidoValidationError('ordenId es requerido');
        }
        if (!diasEntrega || diasEntrega <= 0) {
            throw new PedidoValidationError('Número de días de entrega debe ser mayor a 0');
        }

        const data = await this.repository.calculateEstimatedDate(ordenId, diasEntrega);

        if (!data.success || !data.fecha_estimada) {
            throw new PedidoBusinessError(data.message || 'Error al calcular la fecha estimada');
        }

        return {
            fecha_estimada: data.fecha_estimada,
            fecha_estimada_iso: data.fecha_estimada_iso,
        };
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { OrderEditService };
} else {
    window.OrderEditService = OrderEditService;
}
