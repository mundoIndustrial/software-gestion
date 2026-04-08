/**
 * ImageConverterService - Servicio centralizado para conversión de imágenes
 * Evita duplicación de código para convertir File objects a blob URLs
 */

window.ImageConverterService = {
    _normalizarColeccion(value) {
        if (!value) return [];
        if (Array.isArray(value)) return value;
        if (typeof value === 'object') return Object.values(value);
        if (typeof value === 'string') {
            const trimmed = value.trim();
            if (!trimmed) return [];
            if ((trimmed.startsWith('[') && trimmed.endsWith(']')) || (trimmed.startsWith('{') && trimmed.endsWith('}'))) {
                try {
                    const parsed = JSON.parse(trimmed);
                    if (Array.isArray(parsed)) return parsed;
                    if (parsed && typeof parsed === 'object') return Object.values(parsed);
                } catch (_) {
                    return [];
                }
            }
        }
        return [];
    },
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

        // Si tiene propiedad ruta (común en varios flujos de telas)
        if (img && img.ruta) {
            if (img.ruta.startsWith('/') || img.ruta.startsWith('http') || img.ruta.startsWith('blob:') || img.ruta.startsWith('data:')) {
                return img.ruta;
            }
            return `/storage/${img.ruta}`;
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
        const lista = this._normalizarColeccion(imagenes);
        if (!lista || lista.length === 0) {
            return null;
        }
        return this.convertirAUrl(lista[0]);
    },

    /**
     * Obtener imagen de tela desde múltiples fuentes
     */
    obtenerImagenTela(telaItem) {
        if (!telaItem) return null;

        // Intentar en múltiples llaves/fuentes, tolerando arrays, objetos y JSON string
        const posiblesFuentes = [
            telaItem.imagenes,
            telaItem.fotos,
            telaItem.telaFotos,
            telaItem.imagenes_tela,
            telaItem.fotos_tela,
            telaItem.archivos,
            telaItem.imagen,
            telaItem.foto
        ];

        for (const fuente of posiblesFuentes) {
            const lista = this._normalizarColeccion(fuente);
            if (lista.length > 0) {
                const url = this.obtenerPrimeraImagen(lista);
                if (url) return url;
            }
        }

        return null;
    }
};
