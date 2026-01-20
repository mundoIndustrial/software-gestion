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
            console.error('[EppApiService] Error obtenerEPP:', error);
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
            console.error('[EppApiService] Error actualizarEPP:', error);
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
            console.error('[EppApiService] Error subirImagen:', error);
            throw error;
        }
    }

    /**
     * Eliminar imagen de EPP
     */
    async eliminarImagen(imagenId) {
        try {
            const response = await fetch(`${this.baseUrl}/imagenes/${imagenId}`, {
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
            console.error('[EppApiService] Error eliminarImagen:', error);
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
