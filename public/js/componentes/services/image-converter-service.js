/**
 * ImageConverterService - Servicio centralizado para conversión de imágenes
 * Evita duplicación de código para convertir File objects a blob URLs
 */

window.ImageConverterService = {
    /**
     * Convertir una imagen a URL utilizable
     * Soporta: File objects, blob URLs, strings, propiedades anidadas
     */
    convertirAUrl(img) {
        if (!img) return null;

        // Si ya es un blob URL
        if (img.blobUrl && typeof img.blobUrl === 'string') {
            return img.blobUrl;
        }

        // Si es un string (URL)
        if (typeof img === 'string') {
            return img;
        }

        // Si es un File object directo
        if (img instanceof File) {
            return URL.createObjectURL(img);
        }

        // Si tiene propiedad file que es File
        if (img && img.file instanceof File) {
            return URL.createObjectURL(img.file);
        }

        // Si tiene propiedad url
        if (img && img.url) {
            return img.url;
        }

        // Si tiene propiedades de BD
        if (img && img.ruta_webp) {
            return img.ruta_webp;
        }
        if (img && img.ruta_original) {
            return img.ruta_original;
        }

        // Si tiene previewUrl
        if (img && img.previewUrl) {
            return img.previewUrl;
        }

        return null;
    },

    /**
     * Obtener la primera imagen de un array
     */
    obtenerPrimeraImagen(imagenes) {
        if (!imagenes || !Array.isArray(imagenes) || imagenes.length === 0) {
            return null;
        }
        return this.convertirAUrl(imagenes[0]);
    },

    /**
     * Obtener imagen de tela desde múltiples fuentes
     */
    obtenerImagenTela(telaItem) {
        if (!telaItem) return null;

        // Fuente 1: imagenes (desde formulario modal)
        if (telaItem.imagenes && Array.isArray(telaItem.imagenes) && telaItem.imagenes.length > 0) {
            return this.obtenerPrimeraImagen(telaItem.imagenes);
        }

        // Fuente 2: telaFotos (desde BD)
        if (telaItem.telaFotos && Array.isArray(telaItem.telaFotos) && telaItem.telaFotos.length > 0) {
            return this.obtenerPrimeraImagen(telaItem.telaFotos);
        }

        return null;
    }
};

console.log(' [ImageConverterService] Cargado - Servicio centralizado de conversión de imágenes');
