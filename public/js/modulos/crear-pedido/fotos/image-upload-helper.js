/**
 * HELPER: Convertir File objects a rutas subidas
 * 
 * Cuando el usuario sube imágenes, se guardan como File objects.
 * Este helper las sube al servidor y retorna las rutas para enviar en el JSON del pedido.
 */

class ImageUploadHelper {
    /**
     * Subir una imagen al servidor
     * @param {File} file - Archivo a subir
     * @param {string} tipo - Tipo de imagen (prenda, tela, proceso)
     * @param {number} pedidoId - ID del pedido (opcional para pre-subida)
     * @returns {Promise<Object>} {ruta_original, ruta_webp, url}
     */
    static async subirImagen(file, tipo = 'prenda', pedidoId = null) {
        if (!(file instanceof File)) {
            // Si ya es una ruta string, retornarla
            if (typeof file === 'string') {
                return {
                    ruta_original: file,
                    ruta_webp: file,
                    url: file
                };
            }
            throw new Error('No es un archivo File válido');
        }

        const formData = new FormData();
        formData.append('imagen', file);
        formData.append('tipo', tipo);
        if (pedidoId) {
            formData.append('pedido_id', pedidoId);
        }

        try {
            const response = await fetch('/api/upload-imagen-temporal', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json'
                },
                body: formData
            });

            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }

            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Error al subir imagen');
            }

            return {
                ruta_original: data.ruta_original || file.name,
                ruta_webp: data.ruta_webp || data.url,
                url: data.url
            };
        } catch (error) {
            console.error(' Error subiendo imagen:', error);
            throw error;
        }
    }

    /**
     * Subir múltiples imágenes
     * @param {Array} files - Array de File objects o strings
     * @param {string} tipo - Tipo de imagen
     * @param {number} pedidoId - ID del pedido (opcional)
     * @returns {Promise<Array>} Array de {ruta_original, ruta_webp, url}
     */
    static async subirImagenes(files, tipo = 'prenda', pedidoId = null) {
        if (!files || files.length === 0) {
            return [];
        }

        const promesas = files.map(file => this.subirImagen(file, tipo, pedidoId));
        
        try {
            const resultados = await Promise.all(promesas);
            return resultados;
        } catch (error) {
            console.error(' Error subiendo imágenes múltiples:', error);
            throw error;
        }
    }

    /**
     * Convertir array de fotos del gestor a rutas subidas
     * @param {Array} fotos - Array de objetos {file, preview, ...}
     * @param {string} tipo - Tipo de imagen
     * @param {number} pedidoId - ID del pedido (opcional)
     * @returns {Promise<Array>} Array de rutas
     */
    static async convertirFotosGestor(fotos, tipo = 'prenda', pedidoId = null) {
        if (!fotos || fotos.length === 0) {
            return [];
        }

        const promesas = fotos.map(async (foto) => {
            // Si ya tiene ruta webp (imagen existente), usarla
            if (foto.ruta_webp && !foto.file) {
                return foto.ruta_webp;
            }
            
            // Si tiene File object, subirlo
            if (foto.file instanceof File) {
                const resultado = await this.subirImagen(foto.file, tipo, pedidoId);
                return resultado.ruta_webp;
            }
            
            // Si es un File directo
            if (foto instanceof File) {
                const resultado = await this.subirImagen(foto, tipo, pedidoId);
                return resultado.ruta_webp;
            }
            
            // Si es string, retornarlo
            if (typeof foto === 'string') {
                return foto;
            }
            
            // Fallback: intentar extraer preview
            return foto.preview || null;
        });

        const resultados = await Promise.all(promesas);
        return resultados.filter(Boolean);
    }
}

// Exponer globalmente
window.ImageUploadHelper = ImageUploadHelper;
