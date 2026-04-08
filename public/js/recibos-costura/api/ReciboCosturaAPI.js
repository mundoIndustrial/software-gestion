/**
 * ReciboCosturaAPI - Cliente HTTP para la API de recibos de costura
 *
 * Endpoints:
 * - GET /api/recibos-costura - Listar recibos con filtros y paginación
 * - GET /api/recibos-costura/filter-options - Obtener opciones de filtros disponibles
 *
 * @class ReciboCosturaAPI
 */
class ReciboCosturaAPI {
    constructor(baseUrl = '') {
        this.baseUrl = baseUrl || '/api';
        this.timeout = 30000; // 30 segundos

        // Cache para filter-options (1 hora)
        this._filterOptionsCache = null;
        this._filterOptionsCacheExpires = 0;
    }

    /**
     * Construye una URL con parámetros query
     */
    _buildQueryString(params) {
        const query = new URLSearchParams();

        for (const [key, value] of Object.entries(params)) {
            if (value !== null && value !== undefined && value !== '') {
                query.append(key, value);
            }
        }

        return query.toString();
    }

    /**
     * Realiza una petición con timeout
     */
    async _fetch(url, options = {}) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.timeout);

        try {
            const response = await fetch(url, {
                ...options,
                signal: controller.signal,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    ...options.headers
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return await response.json();
        } catch (error) {
            if (error.name === 'AbortError') {
                throw new Error('La solicitud excedió el tiempo límite');
            }
            throw error;
        } finally {
            clearTimeout(timeoutId);
        }
    }

    /**
     * Obtiene la lista de recibos con filtros y paginación
     *
     * @param {Object} filtros - Filtros a aplicar
     * @param {string} filtros.numero_recibo - Número del recibo
     * @param {string} filtros.estado - Estado del recibo
     * @param {string} filtros.area - Área de proceso
     * @param {string} filtros.cliente - Nombre del cliente
     * @param {string} filtros.dia_entrega - Día de entrega
     * @param {number} filtros.page - Número de página (default: 1)
     * @param {number} filtros.per_page - Items por página (default: 25)
     *
     * @returns {Promise<Object>} { data: [...], paginacion: {...} }
     */
    async getRecibos(filtros = {}) {
        const params = {
            page: filtros.page || 1,
            per_page: filtros.per_page || 25,
            ...filtros
        };

        const queryString = this._buildQueryString(params);
        const url = `${this.baseUrl}/recibos-costura${queryString ? '?' + queryString : ''}`;

        try {
            const respuesta = await this._fetch(url);
            return respuesta;
        } catch (error) {
            console.error('Error en getRecibos:', error);
            throw error;
        }
    }

    /**
     * Obtiene las opciones disponibles para los filtros
     * Cachea la respuesta por 1 hora
     *
     * @returns {Promise<Object>} { estados: [...], areas: [...], ... }
     */
    async getFilterOptions() {
        // Retornar cache si es válido
        if (this._filterOptionsCache && Date.now() < this._filterOptionsCacheExpires) {
            return this._filterOptionsCache;
        }

        const url = `${this.baseUrl}/recibos-costura/filter-options`;

        try {
            const respuesta = await this._fetch(url);

            // Guardar en cache por 1 hora
            this._filterOptionsCache = respuesta;
            this._filterOptionsCacheExpires = Date.now() + (60 * 60 * 1000);

            return respuesta;
        } catch (error) {
            console.error('Error en getFilterOptions:', error);
            throw error;
        }
    }

    /**
     * Limpia el cache de opciones de filtro
     */
    clearFilterOptionsCache() {
        this._filterOptionsCache = null;
        this._filterOptionsCacheExpires = 0;
    }

    /**
     * Obtiene un recibo específico por su ID
     */
    async getRecibo(id) {
        const url = `${this.baseUrl}/recibos-costura/${id}`;

        try {
            return await this._fetch(url);
        } catch (error) {
            console.error('Error en getRecibo:', error);
            throw error;
        }
    }

    /**
     * Actualiza un recibo
     */
    async updateRecibo(id, datos) {
        const url = `${this.baseUrl}/recibos-costura/${id}`;

        try {
            return await this._fetch(url, {
                method: 'PUT',
                body: JSON.stringify(datos)
            });
        } catch (error) {
            console.error('Error en updateRecibo:', error);
            throw error;
        }
    }

    /**
     * Añade una novedad a un recibo
     */
    async agregarNovedad(reciboId, descripcion) {
        const url = `${this.baseUrl}/recibos-costura/${reciboId}/novedades`;

        try {
            return await this._fetch(url, {
                method: 'POST',
                body: JSON.stringify({ descripcion })
            });
        } catch (error) {
            console.error('Error en agregarNovedad:', error);
            throw error;
        }
    }
}

/**
 * Exportar clase
 */
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ReciboCosturaAPI;
}
