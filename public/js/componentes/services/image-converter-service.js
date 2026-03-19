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
            if (img.startsWith('/') || img.startsWith('http') || img.startsWith('blob:') || img.startsWith('data:')) {
                return img;
            }
            return `/storage/${img}`;
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
            if (img.url.startsWith('/') || img.url.startsWith('http') || img.url.startsWith('blob:') || img.url.startsWith('data:')) {
                return img.url;
            }
            return `/storage/${img.url}`;
        }

        // Si tiene propiedades de BD
        if (img && img.ruta_webp) {
            if (img.ruta_webp.startsWith('/') || img.ruta_webp.startsWith('http') || img.ruta_webp.startsWith('blob:') || img.ruta_webp.startsWith('data:')) {
                return img.ruta_webp;
            }
            return `/storage/${img.ruta_webp}`;
        }
        if (img && img.ruta_original) {
            if (img.ruta_original.startsWith('/') || img.ruta_original.startsWith('http') || img.ruta_original.startsWith('blob:') || img.ruta_original.startsWith('data:')) {
                return img.ruta_original;
            }
            return `/storage/${img.ruta_original}`;
        }

        // Si tiene previewUrl
        if (img && img.previewUrl) {
            return img.previewUrl;
        }

        if (img && img.preview) {
            return img.preview;
        }

        if (img && img.src) {
            if (img.src.startsWith('/') || img.src.startsWith('http') || img.src.startsWith('blob:') || img.src.startsWith('data:')) {
                return img.src;
            }
            return `/storage/${img.src}`;
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

        // Fuente 1b: fotos (algunos flujos legacy)
        if (telaItem.fotos && Array.isArray(telaItem.fotos) && telaItem.fotos.length > 0) {
            return this.obtenerPrimeraImagen(telaItem.fotos);
        }

        // Fuente 2: telaFotos (desde BD)
        if (telaItem.telaFotos && Array.isArray(telaItem.telaFotos) && telaItem.telaFotos.length > 0) {
            return this.obtenerPrimeraImagen(telaItem.telaFotos);
        }

        if (telaItem.imagenes_tela && Array.isArray(telaItem.imagenes_tela) && telaItem.imagenes_tela.length > 0) {
            return this.obtenerPrimeraImagen(telaItem.imagenes_tela);
        }

        return null;
    }
};

