/**
 * Servicio de API para Pedidos
 * Centraliza todas las llamadas al backend
 * 
 * @class ApiService
 * 
 * NOTA: Este archivo se carga como módulo ES6 pero también mantiene
 * compatibilidad con window global. PedidoCompletoUnificado se inyecta
 * desde inicializador-pedido-completo.js
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
        // NUEVO: Usar builder unificado desde window
        if (!window.PedidoCompletoUnificado) {
            console.warn('[ApiService] PedidoCompletoUnificado no disponible, usando payload directo');
            return await this.request(
                `${this.baseUrl}/crear-sin-cotizacion`,
                {
                    method: 'POST',
                    body: JSON.stringify(pedidoData)
                }
            );
        }
        
        const builder = new window.PedidoCompletoUnificado();
        
        // Construir pedido desde datos crudos
        builder
            .setCliente(pedidoData.cliente)
            .setAsesora(pedidoData.asesora || pedidoData.asesor)
            .setFormaPago(pedidoData.forma_de_pago);
        
        // Agregar items (prendas)
        if (Array.isArray(pedidoData.items)) {
            pedidoData.items.forEach(item => builder.agregarPrenda(item));
        }
        
        // Validar y construir
        builder.validate();
        const pedidoLimpio = builder.build();
        
        console.log('[ApiService] Pedido sanitizado con builder:', pedidoLimpio);
        
        const data = await this.request(
            `${this.baseUrl}/crear-sin-cotizacion`,
            {
                method: 'POST',
                body: JSON.stringify(pedidoLimpio)
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
        // NUEVO: Usar builder unificado desde window
        if (!window.PedidoCompletoUnificado) {
            console.warn('[ApiService] PedidoCompletoUnificado no disponible, usando payload directo');
            return await this.request(
                `${this.baseUrl}/crear-prenda-sin-cotizacion`,
                {
                    method: 'POST',
                    body: JSON.stringify(pedidoData)
                }
            );
        }
        
        const builder = new window.PedidoCompletoUnificado();
        
        // Construir pedido desde datos crudos
        builder
            .setCliente(pedidoData.cliente)
            .setAsesora(pedidoData.asesora || pedidoData.asesor)
            .setFormaPago(pedidoData.forma_de_pago);
        
        // Agregar la prenda
        builder.agregarPrenda(pedidoData);
        
        // Validar y construir
        builder.validate();
        const pedidoLimpio = builder.build();
        
        console.log('[ApiService] Prenda sanitizada con builder:', pedidoLimpio);
        
        const data = await this.request(
            `${this.baseUrl}/crear-prenda-sin-cotizacion`,
            {
                method: 'POST',
                body: JSON.stringify(pedidoLimpio)
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

    /**
     * ═══════════════════════════════════════════════════════════════════
     * SANITIZADOR DE PEDIDOS - Limpia JSON antes de enviar
     * ═══════════════════════════════════════════════════════════════════
     */
    sanitizePedido(pedidoData) {
        return {
            cliente: pedidoData.cliente,
            asesora: pedidoData.asesora,
            forma_de_pago: (pedidoData.forma_de_pago || pedidoData.forma_pago || 'contado').toLowerCase(),
            items: (pedidoData.items || []).map(item => this._sanitizeItem(item))
        };
    }

    _sanitizeItem(item) {
        return {
            tipo: item.tipo || 'prenda_nueva',
            nombre_prenda: item.nombre_prenda || item.nombre_producto || '',
            descripcion: this._cleanString(item.descripcion),
            origen: item.origen || 'bodega',
            de_bodega: item.origen === 'bodega' ? 1 : 0,
            cantidad_talla: this._sanitizeCantidadTalla(item.cantidad_talla),
            variaciones: this._sanitizeVariaciones(item.variaciones || item.variantes),
            telas: this._sanitizeTelas(item.telas),
            imagenes: this._sanitizeImagenes(item.imagenes),
            procesos: this._sanitizeProcesos(item.procesos)
        };
    }

    _sanitizeCantidadTalla(cantidadTalla) {
        if (!cantidadTalla || typeof cantidadTalla !== 'object') {
            return { DAMA: {}, CABALLERO: {}, UNISEX: {} };
        }
        const cleaned = {};
        ['DAMA', 'CABALLERO', 'UNISEX'].forEach(genero => {
            const tallas = cantidadTalla[genero];
            if (tallas && typeof tallas === 'object' && !Array.isArray(tallas)) {
                cleaned[genero] = {};
                Object.entries(tallas).forEach(([talla, cantidad]) => {
                    const cant = parseInt(cantidad);
                    if (!isNaN(cant) && cant > 0) {
                        cleaned[genero][talla] = cant;
                    }
                });
            } else {
                cleaned[genero] = {};
            }
        });
        return cleaned;
    }

    _sanitizeVariaciones(variaciones) {
        if (!variaciones) return {};
        return {
            tipo_manga: this._cleanString(variaciones.tipo_manga),
            obs_manga: this._cleanString(variaciones.obs_manga),
            tiene_bolsillos: Boolean(variaciones.tiene_bolsillos),
            obs_bolsillos: this._cleanString(variaciones.obs_bolsillos),
            tipo_broche: this._cleanString(variaciones.tipo_broche),
            obs_broche: this._cleanString(variaciones.obs_broche),
            tipo_broche_boton_id: this._cleanInt(variaciones.tipo_broche_boton_id),
            tipo_manga_id: this._cleanInt(variaciones.tipo_manga_id),
            tiene_reflectivo: Boolean(variaciones.tiene_reflectivo),
            obs_reflectivo: this._cleanString(variaciones.obs_reflectivo)
        };
    }

    _sanitizeTelas(telas) {
        if (!Array.isArray(telas)) return [];
        return telas
            .filter(tela => tela && typeof tela === 'object')
            .map(tela => ({
                tela: this._cleanString(tela.tela),
                color: this._cleanString(tela.color),
                referencia: this._cleanString(tela.referencia),
                tela_id: this._cleanInt(tela.tela_id),
                color_id: this._cleanInt(tela.color_id),
                imagenes: this._sanitizeImagenes(tela.imagenes)
            }));
    }

    _sanitizeImagenes(imagenes) {
        if (!imagenes) return [];
        if (!Array.isArray(imagenes)) return [];
        const flattened = this._flattenImages(imagenes);
        return flattened.filter(img => img && typeof img === 'string' && img.trim() !== '');
    }

    _flattenImages(arr, depth = 0) {
        if (depth > 5) return [];
        const result = [];
        for (const item of arr) {
            if (Array.isArray(item)) {
                result.push(...this._flattenImages(item, depth + 1));
            } else if (item && typeof item === 'string') {
                result.push(item);
            }
        }
        return result;
    }

    _sanitizeProcesos(procesos) {
        if (!procesos || typeof procesos !== 'object') return {};
        const cleaned = {};
        const tiposProceso = ['reflectivo', 'bordado', 'estampado', 'dtf', 'sublimado'];
        tiposProceso.forEach(tipo => {
            if (procesos[tipo]) {
                cleaned[tipo] = {
                    tipo: tipo,
                    datos: this._sanitizeDatosProceso(procesos[tipo].datos || procesos[tipo])
                };
            }
        });
        return cleaned;
    }

    _sanitizeDatosProceso(datos) {
        if (!datos || typeof datos !== 'object') return {};
        return {
            tipo: this._cleanString(datos.tipo),
            ubicaciones: this._sanitizeUbicaciones(datos.ubicaciones),
            observaciones: this._cleanString(datos.observaciones),
            tallas: this._sanitizeTallasProceso(datos.tallas),
            imagenes: this._sanitizeImagenes(datos.imagenes)
        };
    }

    _sanitizeUbicaciones(ubicaciones) {
        if (!ubicaciones) return [];
        if (typeof ubicaciones === 'string') return [ubicaciones];
        if (Array.isArray(ubicaciones)) {
            return ubicaciones.filter(u => u && typeof u === 'string' && u.trim() !== '');
        }
        return [];
    }

    _sanitizeTallasProceso(tallas) {
        if (!tallas || typeof tallas !== 'object') return { dama: {}, caballero: {} };
        const cleaned = { dama: {}, caballero: {} };
        ['dama', 'caballero'].forEach(genero => {
            const generoTallas = tallas[genero];
            if (generoTallas && typeof generoTallas === 'object' && !Array.isArray(generoTallas)) {
                Object.entries(generoTallas).forEach(([talla, cantidad]) => {
                    const cant = parseInt(cantidad);
                    if (!isNaN(cant) && cant > 0) {
                        cleaned[genero][talla] = cant;
                    }
                });
            }
        });
        return cleaned;
    }

    _cleanString(value) {
        if (value === null || value === undefined) return null;
        if (typeof value === 'string') return value.trim() || null;
        return String(value).trim() || null;
    }

    _cleanInt(value) {
        const parsed = parseInt(value);
        return isNaN(parsed) ? null : parsed;
    }
}

// Exportar para módulos ES6
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ApiService;
}

// Crear instancia global para compatibilidad
window.ApiService = new ApiService();

// Exportar como módulo ES6
export default ApiService;
