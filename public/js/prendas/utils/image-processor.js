/**
 * ImageProcessor - Procesa y convierte imágenes a URLs usables
 * 
 * Responsabilidad: Convertir File objects, blob URLs y rutas en URLs consistentes
 * Patrón: Adapter + Transformer
 */

class ImageProcessor {
    /**
     * Procesar imagen única en URL usable
     * @param {File|Blob|Object|string} imagen - Imagen a procesar
     * @returns {string|null} URL de la imagen o null
     */
    static procesarImagen(imagen) {
        if (!imagen) return null;

        try {
            // Ya es blob URL
            if (typeof imagen === 'string' && imagen.startsWith('blob:')) {
                return imagen;
            }

            // Ya es ruta URL
            if (typeof imagen === 'string' && imagen.startsWith('http')) {
                return imagen;
            }

            // Tiene blobUrl precreado (guardado previamente)
            if (imagen.blobUrl && typeof imagen.blobUrl === 'string') {
                return imagen.blobUrl;
            }

            // Es File object
            if (imagen instanceof File) {
                return URL.createObjectURL(imagen);
            }

            // Es Blob
            if (imagen instanceof Blob) {
                return URL.createObjectURL(imagen);
            }

            // Fallback: intentar convertir a URL
            if (typeof imagen === 'string') {
                return imagen;
            }

            console.warn('[ImageProcessor] Formato de imagen no reconocido:', imagen);
            return null;

        } catch (error) {
            console.error('[ImageProcessor] Error procesando imagen:', error);
            return null;
        }
    }

    /**
     * Procesar array de imágenes
     * @param {Array} imagenes - Array de imágenes
     * @returns {Array} Array de URLs de imágenes
     */
    static procesarImagenes(imagenes) {
        if (!Array.isArray(imagenes)) {
            return [];
        }

        return imagenes
            .map(img => this.procesarImagen(img))
            .filter(url => url !== null);
    }

    /**
     * Obtener URL principal de imagen desde estructura de prenda
     * @param {Array} imagenes - Array de imágenes de prenda
     * @returns {string|null} URL de foto principal
     */
    static obtenerFotoPrincipal(imagenes) {
        if (!Array.isArray(imagenes) || imagenes.length === 0) {
            return null;
        }

        return this.procesarImagen(imagenes[0]);
    }

    /**
     * Limpiar recursos (blob URLs)
     * @param {Array} urls - URLs a limpiar
     */
    static limpiarRecursos(urls) {
        if (!Array.isArray(urls)) return;

        urls.forEach(url => {
            if (typeof url === 'string' && url.startsWith('blob:')) {
                try {
                    URL.revokeObjectURL(url);
                } catch (e) {
                    console.warn('[ImageProcessor] Error al limpiar blob URL:', e);
                }
            }
        });
    }
}

window.ImageProcessor = ImageProcessor;
console.log('✓ [IMAGE-PROCESSOR] Cargado correctamente');
