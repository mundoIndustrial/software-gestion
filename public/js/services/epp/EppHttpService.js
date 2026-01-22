/**
 * Servicio HTTP para EPP
 * 
 * Encapsula todas las llamadas a la API de EPP
 * Endpoints:
 * - GET /api/epp - Buscar/listar EPP
 * - GET /api/epp/{id} - Obtener EPP por ID
 * - GET /api/epp/categorias/all - Obtener categorías
 * - GET /api/pedidos/{pedidoId}/epp - Obtener EPP del pedido
 * - POST /api/pedidos/{pedidoId}/epp/agregar - Agregar EPP al pedido
 * - DELETE /api/pedidos/{pedidoId}/epp/{eppId} - Eliminar EPP del pedido
 */
class EppHttpService {
    constructor(baseUrl = '/api') {
        this.baseUrl = baseUrl;
    }

    /**
     * Buscar EPP por término o listar todos los activos
     * @param {string|null} termino - Término de búsqueda opcional
     * @param {string|null} categoria - Categoría opcional
     * @returns {Promise<Array>} Array de EPP
     */
    async buscar(termino = null, categoria = null) {
        let url = `${this.baseUrl}/epp`;
        const params = new URLSearchParams();

        if (termino) {
            params.append('q', termino);
        }
        if (categoria) {
            params.append('categoria', categoria);
        }

        if (params.toString()) {
            url += `?${params.toString()}`;
        }

        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`Error buscando EPP: ${response.statusText}`);
        }

        const data = await response.json();
        return data.data || [];
    }

    /**
     * Obtener EPP por ID
     * @param {number} id - ID del EPP
     * @returns {Promise<Object>} Datos del EPP
     */
    async obtenerPorId(id) {
        const response = await fetch(`${this.baseUrl}/epp/${id}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            if (response.status === 404) {
                return null;
            }
            throw new Error(`Error obteniendo EPP: ${response.statusText}`);
        }

        const data = await response.json();
        return data.data || null;
    }

    /**
     * Obtener todas las categorías disponibles
     * @returns {Promise<Array>} Array de categorías
     */
    async obtenerCategorias() {
        const response = await fetch(`${this.baseUrl}/epp/categorias/all`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`Error obteniendo categorías: ${response.statusText}`);
        }

        const data = await response.json();
        return data.data || [];
    }

    /**
     * Obtener EPP de un pedido
     * @param {number} pedidoId - ID del pedido
     * @returns {Promise<Array>} Array de EPP del pedido
     */
    async obtenerDelPedido(pedidoId) {
        const response = await fetch(`${this.baseUrl}/pedidos/${pedidoId}/epp`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`Error obteniendo EPP del pedido: ${response.statusText}`);
        }

        const data = await response.json();
        return data.data || [];
    }

    /**
     * Agregar EPP a un pedido
     * @param {number} pedidoId - ID del pedido
     * @param {number} eppId - ID del EPP
     * @param {number} cantidad - Cantidad
     * @param {string|null} observaciones - Observaciones opcionales
     * @returns {Promise<Object>} Respuesta de la API
     */
    async agregarAlPedido(pedidoId, eppId, cantidad, observaciones = null) {
        const response = await fetch(`${this.baseUrl}/pedidos/${pedidoId}/epp/agregar`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                epp_id: eppId,
                cantidad: cantidad,
                observaciones: observaciones,
            }),
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Error agregando EPP');
        }

        return await response.json();
    }

    /**
     * Eliminar EPP de un pedido
     * @param {number} pedidoId - ID del pedido
     * @param {number} eppId - ID del EPP
     * @returns {Promise<Object>} Respuesta de la API
     */
    async eliminarDelPedido(pedidoId, eppId) {
        const response = await fetch(`${this.baseUrl}/pedidos/${pedidoId}/epp/${eppId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Error eliminando EPP');
        }

        return await response.json();
    }

    /**
     * Subir imagen a un EPP
     * @param {number} eppId - ID del EPP
     * @param {File} archivo - Archivo de imagen
     * @param {boolean} principal - Si es imagen principal
     * @returns {Promise<Object>} Datos de la imagen subida
     */
    async subirImagen(eppId, archivo, principal = false) {
        const formData = new FormData();
        formData.append('imagen', archivo);
        formData.append('principal', principal ? '1' : '0');

        const response = await fetch(`${this.baseUrl}/epp/${eppId}/imagenes`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Error subiendo imagen');
        }

        const data = await response.json();
        return data.data || null;
    }

    /**
     * Eliminar imagen de un EPP
     * @param {number} imagenId - ID de la imagen
     * @returns {Promise<Object>} Respuesta de la API
     */
    async eliminarImagen(imagenId) {
        const response = await fetch(`${this.baseUrl}/epp/imagenes/${imagenId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            const error = await response.json();
            throw new Error(error.message || 'Error eliminando imagen');
        }

        return await response.json();
    }
}

// Exportar como objeto global si es necesario
window.EppHttpService = EppHttpService;
