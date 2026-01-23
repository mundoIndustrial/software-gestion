/**
 * HttpService - Gestión centralizada de peticiones HTTP
 * 
 * Single Responsibility: Manejo exclusivo de peticiones HTTP con CSRF token
 * 
 * @module HttpService
 */
class HttpService {
    constructor() {
        this.csrfTokenSelector = 'input[name="_token"]';
        this.defaultHeaders = {
            'Accept': 'application/json'
        };
    }

    /**
     * Obtiene el token CSRF del DOM
     * @returns {string} Token CSRF
     */
    getCsrfToken() {
        const tokenElement = document.querySelector(this.csrfTokenSelector);
        if (!tokenElement) {
            throw new Error('CSRF token no encontrado en el documento');
        }
        return tokenElement.value;
    }

    /**
     * Obtiene los headers por defecto incluyendo CSRF token
     * @returns {Object} Headers con CSRF token
     */
    getHeaders() {
        return {
            ...this.defaultHeaders,
            'X-CSRF-TOKEN': this.getCsrfToken()
        };
    }

    /**
     * Realiza una petición POST
     * @param {string} url - URL del endpoint
     * @param {FormData|Object} data - Datos a enviar
     * @param {Object} options - Opciones adicionales
     * @returns {Promise<Object>} Respuesta JSON del servidor
     */
    async post(url, data, options = {}) {
        return this.request(url, {
            method: 'POST',
            body: data,
            ...options
        });
    }

    /**
     * Realiza una petición GET
     * @param {string} url - URL del endpoint
     * @param {Object} options - Opciones adicionales
     * @returns {Promise<Object>} Respuesta JSON del servidor
     */
    async get(url, options = {}) {
        return this.request(url, {
            method: 'GET',
            ...options
        });
    }

    /**
     * Realiza una petición PUT
     * @param {string} url - URL del endpoint
     * @param {FormData|Object} data - Datos a enviar
     * @param {Object} options - Opciones adicionales
     * @returns {Promise<Object>} Respuesta JSON del servidor
     */
    async put(url, data, options = {}) {
        return this.request(url, {
            method: 'PUT',
            body: data,
            ...options
        });
    }

    /**
     * Realiza una petición DELETE
     * @param {string} url - URL del endpoint
     * @param {Object} options - Opciones adicionales
     * @returns {Promise<Object>} Respuesta JSON del servidor
     */
    async delete(url, options = {}) {
        return this.request(url, {
            method: 'DELETE',
            ...options
        });
    }

    /**
     * Realiza una petición HTTP genérica
     * @private
     * @param {string} url - URL del endpoint
     * @param {Object} config - Configuración de fetch
     * @returns {Promise<Object>} Respuesta JSON del servidor
     */
    async request(url, config = {}) {
        try {
            // Combinar headers (no sobrescribir si data es FormData)
            const isFormData = config.body instanceof FormData;
            const headers = isFormData 
                ? { 'X-CSRF-TOKEN': this.getCsrfToken() }
                : this.getHeaders();

            const response = await fetch(url, {
                ...config,
                headers: {
                    ...headers,
                    ...config.headers
                }
            });

            // Intentar parsear JSON primero (para errores 422, etc)
            const contentType = response.headers.get('content-type');
            let responseData = {};
            
            if (contentType && contentType.includes('application/json')) {
                responseData = await response.json();
            }

            // Validar que la respuesta sea correcta
            if (!response.ok) {
                // Retornar objeto de error con detalles del servidor
                return {
                    success: false,
                    message: responseData.message || `HTTP ${response.status}: ${response.statusText}`,
                    errors: responseData.errors || {},
                    status: response.status
                };
            }

            return responseData.success !== undefined ? responseData : { success: true, ...responseData };
        } catch (error) {

            return {
                success: false,
                message: error.message
            };
        }
    }

    /**
     * Cambia el selector del token CSRF
     * @param {string} selector - Selector CSS del elemento del token
     */
    setTokenSelector(selector) {
        this.csrfTokenSelector = selector;
    }

    /**
     * Agrega headers adicionales por defecto
     * @param {Object} headers - Headers a agregar
     */
    addDefaultHeaders(headers) {
        this.defaultHeaders = {
            ...this.defaultHeaders,
            ...headers
        };
    }
}

// Crear instancia global
const httpService = new HttpService();

