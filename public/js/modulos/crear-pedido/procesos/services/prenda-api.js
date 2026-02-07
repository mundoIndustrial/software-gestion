/**
 * PrendaAPI - Abstracción de API para operaciones de prendas
 * 
 * Propósito: Centralizar todas las llamadas HTTP
 * Ventajas: Fácil cambiar endpoints, testeable, documentado
 */
class PrendaAPI {
    constructor(baseURL = '') {
        this.baseURL = baseURL;
        this.timeout = 30000;
    }

    /**
     * Realizar request genérico
     * @private
     */
    async request(endpoint, opciones = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const config = {
            method: opciones.method || 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                ...opciones.headers
            },
            ...opciones
        };

        // Remover Content-Type para FormData
        if (opciones.body instanceof FormData) {
            delete config.headers['Content-Type'];
        }

        try {
            const response = await fetch(url, config);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            // Si es un blob (descarga), retornarlo directamente
            if (opciones.returnBlob) {
                return await response.blob();
            }

            // Intentar parsear JSON
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            }

            return await response.text();
        } catch (error) {
            console.error(`[PrendaAPI] Error en request ${endpoint}:`, error);
            throw error;
        }
    }

    /**
     * ENDPOINT PRINCIPAL: Obtener prenda para edición
     * 
     * Backend retorna PRENDA COMPLETAMENTE PROCESADA Y NORMALIZADA
     * Todo listo para presentar en el formulario
     */
    async obtenerPrendaParaEdicion(prendaId) {
        return this.request(`/api/prendas/${prendaId}`);
    }

    /**
     * ENDPOINT PRINCIPAL: Guardar prenda (crear o actualizar)
     * 
     * Backend:
     * 1. Valida datos
     * 2. Aplica lógica de negocio
     * 3. Guarda en BD
     * 4. Retorna resultado con errores si aplica
     */
    async guardarPrenda(datos) {
        return this.request('/api/prendas', {
            method: 'POST',
            body: JSON.stringify(datos)
        });
    }

    /**
     * ENDPOINT: Obtener prendas de una cotización
     * 
     * Backend retorna prendas CON origen ya aplicado automáticamente
     */
    async obtenerPrendasDesdeCotizacion(cotizacionId) {
        return this.request(`/api/cotizaciones/${cotizacionId}/prendas`);
    }

    /**
     * ENDPOINT: Validar datos de prenda
     * 
     * Backend ejecuta toda validación de negocio
     * Retorna: { valido, errores }
     */
    async validarPrenda(datos) {
        return this.request('/api/prendas/validar', {
            method: 'POST',
            body: JSON.stringify(datos)
        });
    }

    /**
     * ENDPOINT: Obtener tipos de manga disponibles
     */
    async obtenerTiposManga() {
        return this.request('/api/catologos/tipos-manga');
    }

    /**
     * ENDPOINT: Obtener tallas disponibles
     */
    async obtenerTallasDisponibles(generoId = null) {
        let endpoint = '/api/catalogos/tallas';
        if (generoId) {
            endpoint += `?genero_id=${generoId}`;
        }
        return this.request(endpoint);
    }

    /**
     * ENDPOINT: Obtener procesos disponibles
     */
    async obtenerProcesosDisponibles(tipoCotizacionId = null) {
        let endpoint = '/api/catalogos/procesos';
        if (tipoCotizacionId) {
            endpoint += `?tipo_cotizacion_id=${tipoCotizacionId}`;
        }
        return this.request(endpoint);
    }

    /**
     * ENDPOINT: Guardar imagen de prenda
     * 
     * El backend procesará y almacenará la imagen
     */
    async guardarImagenPrenda(prendaId, archivo) {
        const formData = new FormData();
        formData.append('imagen', archivo);

        return this.request(`/api/prendas/${prendaId}/imagenes`, {
            method: 'POST',
            body: formData
        });
    }

    /**
     * ENDPOINT: Guardar imagen de tela
     */
    async guardarImagenTela(telaId, archivo) {
        const formData = new FormData();
        formData.append('imagen', archivo);

        return this.request(`/api/telas/${telaId}/imagenes`, {
            method: 'POST',
            body: formData
        });
    }

    /**
     * ENDPOINT: Obtener cotización con prendas
     */
    async obtenerCotizacion(cotizacionId) {
        return this.request(`/api/cotizaciones/${cotizacionId}`);
    }

    /**
     * DEPRECATED: Mantener para compatibilidad, pero use obtenerPrendaParaEdicion
     * 
     * Backend ya retorna todo en un solo endpoint
     */
    async cargarTelasDesdeCotizacion(cotizacionId, prendaId) {
        console.warn('[PrendaAPI] cargarTelasDesdeCotizacion es DEPRECATED. Use obtenerPrendaParaEdicion()');
        // Ahora se obtiene todo en un endpoint
        return this.request(`/api/prendas/${prendaId}`);
    }

    /**
     * Configurar timeout global
     */
    setTimeout(ms) {
        this.timeout = ms;
    }

    /**
     * Obtener estado de la API (health check)
     */
    async verificarConexion() {
        try {
            return await this.request('/api/health');
        } catch {
            return { status: 'error' };
        }
    }
}

window.PrendaAPI = PrendaAPI;
