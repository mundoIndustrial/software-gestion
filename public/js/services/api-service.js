/**
 * Servicio de API para Pedidos
 * Centraliza todas las llamadas al backend
 * 
 * @class ApiService
 */

class ApiService {
    constructor() {
        this.baseUrl = '/asesores/pedidos-produccion';
        this.csrfToken = this.getCsrfToken();
    }

    /**
     * Obtener token CSRF
     * @returns {string}
     */
    getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || 
               document.querySelector('input[name="_token"]')?.value;
    }

    /**
     * Realizar petición fetch con manejo de errores
     * @param {string} url - URL de la petición
     * @param {Object} options - Opciones de fetch
     * @returns {Promise<Object>}
     */
    async request(url, options = {}) {
        try {
            const response = await fetch(url, {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    ...options.headers
                }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || `Error ${response.status}: ${response.statusText}`);
            }

            return data;

        } catch (error) {
            console.error('❌ Error en petición API:', error);
            throw error;
        }
    }

    // ============================================================
    // COTIZACIONES
    // ============================================================

    /**
     * Obtener datos completos de una cotización
     * @param {number} cotizacionId - ID de la cotización
     * @returns {Promise<Object>}
     */
    async obtenerDatosCotizacion(cotizacionId) {
        console.log(`📥 Obteniendo datos de cotización ${cotizacionId}...`);
        
        const data = await this.request(
            `${this.baseUrl}/obtener-datos-cotizacion/${cotizacionId}`,
            { method: 'GET' }
        );

        return data;
    }

    // ============================================================
    // PEDIDOS DESDE COTIZACIÓN
    // ============================================================

    /**
     * Crear pedido desde cotización
     * @param {number} cotizacionId - ID de la cotización
     * @param {Object} pedidoData - Datos del pedido
     * @returns {Promise<Object>}
     */
    async crearPedidoDesdeCotizacion(cotizacionId, pedidoData) {
        console.log(`📤 Creando pedido desde cotización ${cotizacionId}...`);
        
        const data = await this.request(
            `${this.baseUrl}/crear-desde-cotizacion/${cotizacionId}`,
            {
                method: 'POST',
                body: JSON.stringify(pedidoData)
            }
        );

        return data;
    }

    // ============================================================
    // PEDIDOS SIN COTIZACIÓN
    // ============================================================

    /**
     * Crear pedido sin cotización (nuevo)
     * @param {Object} pedidoData - Datos del pedido
     * @returns {Promise<Object>}
     */
    async crearPedidoSinCotizacion(pedidoData) {
        console.log(`📤 Creando pedido sin cotización...`);
        
        const data = await this.request(
            `${this.baseUrl}/crear-sin-cotizacion`,
            {
                method: 'POST',
                body: JSON.stringify(pedidoData)
            }
        );

        return data;
    }

    /**
     * Crear pedido tipo PRENDA sin cotización
     * @param {Object} pedidoData - Datos del pedido
     * @returns {Promise<Object>}
     */
    async crearPedidoPrendaSinCotizacion(pedidoData) {
        console.log(`📤 Creando pedido PRENDA sin cotización...`);
        
        const data = await this.request(
            `${this.baseUrl}/crear-prenda-sin-cotizacion`,
            {
                method: 'POST',
                body: JSON.stringify(pedidoData)
            }
        );

        return data;
    }

    /**
     * Crear pedido tipo REFLECTIVO sin cotización
     * @param {Object} pedidoData - Datos del pedido
     * @returns {Promise<Object>}
     */
    async crearPedidoReflectivoSinCotizacion(pedidoData) {
        console.log(`📤 Creando pedido REFLECTIVO sin cotización...`);
        
        const data = await this.request(
            `${this.baseUrl}/crear-reflectivo-sin-cotizacion`,
            {
                method: 'POST',
                body: JSON.stringify(pedidoData)
            }
        );

        return data;
    }

    // ============================================================
    // LOGOS
    // ============================================================

    /**
     * Eliminar foto de logo
     * @param {number} cotizacionId - ID de la cotización
     * @param {number} fotoId - ID de la foto
     * @returns {Promise<Object>}
     */
    async eliminarFotoLogo(cotizacionId, fotoId) {
        console.log(`🗑️ Eliminando foto ${fotoId} de logo...`);
        
        const data = await this.request(
            `/asesores/logos/${cotizacionId}/eliminar-foto`,
            {
                method: 'POST',
                body: JSON.stringify({ foto_id: fotoId })
            }
        );

        return data;
    }

    // ============================================================
    // VALIDACIONES
    // ============================================================

    /**
     * Validar datos de pedido antes de enviar
     * @param {Object} pedidoData - Datos del pedido
     * @returns {Promise<Object>}
     */
    async validarPedido(pedidoData) {
        console.log(`🔍 Validando datos de pedido...`);
        
        // Endpoint futuro para validación en backend
        // Por ahora retornamos validación local
        return {
            success: true,
            errors: []
        };
    }

    // ============================================================
    // UTILIDADES
    // ============================================================

    /**
     * Construir FormData para upload de archivos
     * @param {Object} data - Datos a convertir
     * @returns {FormData}
     */
    buildFormData(data) {
        const formData = new FormData();
        
        Object.keys(data).forEach(key => {
            const value = data[key];
            
            if (value instanceof File) {
                formData.append(key, value);
            } else if (Array.isArray(value)) {
                value.forEach((item, index) => {
                    if (item instanceof File) {
                        formData.append(`${key}[]`, item);
                    } else {
                        formData.append(`${key}[${index}]`, JSON.stringify(item));
                    }
                });
            } else if (typeof value === 'object' && value !== null) {
                formData.append(key, JSON.stringify(value));
            } else {
                formData.append(key, value);
            }
        });
        
        return formData;
    }

    /**
     * Manejar errores de API de forma consistente
     * @param {Error} error - Error capturado
     * @param {string} context - Contexto del error
     */
    handleError(error, context = 'Operación') {
        console.error(`❌ Error en ${context}:`, error);
        
        let message = error.message || 'Error desconocido';
        
        // Personalizar mensajes según el tipo de error
        if (error.message?.includes('Failed to fetch')) {
            message = 'Error de conexión. Verifica tu conexión a internet.';
        } else if (error.message?.includes('419')) {
            message = 'Sesión expirada. Por favor, recarga la página.';
        } else if (error.message?.includes('422')) {
            message = 'Datos inválidos. Verifica la información ingresada.';
        } else if (error.message?.includes('500')) {
            message = 'Error del servidor. Intenta nuevamente más tarde.';
        }
        
        // Mostrar notificación al usuario
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonColor: '#dc3545'
            });
        }
        
        return { success: false, message, error };
    }

    /**
     * Mostrar loading durante petición
     * @param {Promise} promise - Promesa a ejecutar
     * @param {string} message - Mensaje de loading
     * @returns {Promise}
     */
    async withLoading(promise, message = 'Procesando...') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: message,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }

        try {
            const result = await promise;
            
            if (typeof Swal !== 'undefined') {
                Swal.close();
            }
            
            return result;
        } catch (error) {
            if (typeof Swal !== 'undefined') {
                Swal.close();
            }
            throw error;
        }
    }

    /**
     * Reintentar petición en caso de fallo
     * @param {Function} fn - Función a ejecutar
     * @param {number} retries - Número de reintentos
     * @param {number} delay - Delay entre reintentos (ms)
     * @returns {Promise}
     */
    async retry(fn, retries = 3, delay = 1000) {
        try {
            return await fn();
        } catch (error) {
            if (retries === 0) {
                throw error;
            }
            
            console.log(`⚠️ Reintentando... (${retries} intentos restantes)`);
            await new Promise(resolve => setTimeout(resolve, delay));
            return this.retry(fn, retries - 1, delay);
        }
    }

    /**
     * Obtener estado de la API
     * @returns {Promise<Object>}
     */
    async healthCheck() {
        try {
            const response = await fetch('/api/health', { method: 'GET' });
            return { online: response.ok };
        } catch (error) {
            return { online: false };
        }
    }
}

// Crear instancia global
window.ApiService = new ApiService();
