/**
 * EppApiService - Maneja todas las llamadas a la API de EPPs
 * Patrón: Service Layer
 */

class EppApiService {
    constructor(baseUrl = '/api') {
        this.baseUrl = baseUrl;
    }

    /**
     * Obtener EPP por ID desde BD
     */
    async obtenerEPP(eppId) {
        try {
            const response = await fetch(`${this.baseUrl}/epps/${eppId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this._obtenerCsrfToken()
                }
            });

            if (!response.ok) {
                throw new Error(`Error al obtener EPP: ${response.statusText}`);
            }

            return await response.json();
        } catch (error) {

            throw error;
        }
    }

    /**
     * Actualizar EPP en BD
     */
    async actualizarEPP(eppId, datos) {
        try {
            const response = await fetch(`${this.baseUrl}/epps/${eppId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this._obtenerCsrfToken()
                },
                body: JSON.stringify(datos)
            });

            if (!response.ok) {
                throw new Error(`Error al actualizar EPP: ${response.statusText}`);
            }

            return await response.json();
        } catch (error) {

            throw error;
        }
    }

    /**
     * Subir imagen para EPP
     */
    async subirImagen(eppId, archivo, esPrincipal = false) {
        try {
            const formData = new FormData();
            formData.append('imagen', archivo);
            formData.append('es_principal', esPrincipal);

            const response = await fetch(`${this.baseUrl}/epps/${eppId}/imagenes`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this._obtenerCsrfToken()
                },
                body: formData
            });

            if (!response.ok) {
                throw new Error(`Error al subir imagen: ${response.statusText}`);
            }

            return await response.json();
        } catch (error) {

            throw error;
        }
    }

    /**
     * Eliminar imagen de EPP
     */
    async eliminarImagen(imagenId) {
        try {
            const response = await fetch(`${this.baseUrl}/epp/imagenes/${imagenId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': this._obtenerCsrfToken()
                }
            });

            if (!response.ok) {
                throw new Error(`Error al eliminar imagen: ${response.statusText}`);
            }

            return await response.json();
        } catch (error) {

            throw error;
        }
    }

    /**
     * Agregar EPP a un pedido
     */
    async agregarEPPAlPedido(pedidoId, eppId, cantidad, observaciones, imagenes = []) {
        try {
            const formData = new FormData();
            formData.append('epp_id', eppId);
            formData.append('cantidad', cantidad);
            if (observaciones) {
                formData.append('observaciones', observaciones);
            }

            // Agregar imágenes si existen
            if (imagenes && Array.isArray(imagenes)) {
                imagenes.forEach((imagen, index) => {
                    if (imagen instanceof File) {
                        formData.append(`imagenes[${index}]`, imagen);
                    }
                });
            }

            const response = await fetch(`${this.baseUrl}/pedidos/${pedidoId}/epp/agregar`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this._obtenerCsrfToken()
                },
                body: formData
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || `Error al agregar EPP: ${response.statusText}`);
            }

            return await response.json();
        } catch (error) {

            throw error;
        }
    }

    /**
     * Subir imagen de EPP durante creación del pedido
     * Guarda directamente en pedidos/{pedido_id}/epp/
     * Retorna ruta webp en el servidor
     */
    async subirImagenEpp(formData) {
        try {
            const response = await fetch(`${this.baseUrl}/epp/imagenes/upload`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': this._obtenerCsrfToken()
                },
                body: formData
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message || `Error al subir imagen: ${response.statusText}`);
            }

            return await response.json();
        } catch (error) {
            console.error('[EppApiService] Error subirImagenEpp:', error);
            throw error;
        }
    }

    /**
     * Obtener token CSRF
     */
    _obtenerCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }
}

// Exportar instancia global
window.eppApiService = new EppApiService();
