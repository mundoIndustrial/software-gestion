/**
 * ImageStorageFallback - Servicio de almacenamiento de imágenes (fallback)
 * 
 * Se usa cuando ImageStorageService no está disponible
 * Proporciona API compatible para manejo de imágenes en prendas
 */
class ImageStorageFallback {
    constructor(maxImagenes = 3) {
        this.images = [];
        this.maxImagenes = maxImagenes;
        this.debug = false;
    }

    /**
     * Limpiar todas las imágenes
     */
    limpiar() {
        // Revocar URLs blob para liberar memoria
        this.images.forEach(img => {
            if (img.previewUrl && img.previewUrl.startsWith('blob:')) {
                URL.revokeObjectURL(img.previewUrl);
            }
        });
        
        this.images = [];
        if (this.debug) console.log('🧹 Almacenamiento de imágenes limpiado');
    }

    /**
     * Agregar imagen desde File object
     * @param {File} file - Archivo a agregar
     * @returns {Promise}
     */
    agregarImagen(file) {
        return new Promise((resolve, reject) => {
            if (!file || !file.type.startsWith('image/')) {
                reject(new Error('INVALID_FILE'));
                return;
            }
            
            if (this.images.length >= this.maxImagenes) {
                reject(new Error('MAX_LIMIT'));
                return;
            }
            
            if (file instanceof File) {
                const imagenObj = {
                    previewUrl: URL.createObjectURL(file),
                    nombre: file.name,
                    tamaño: file.size,
                    file: file,
                    tipo: 'archivo',
                    timestamp: new Date().getTime()
                };
                
                this.images.push(imagenObj);
                
                if (this.debug) {
                    console.log(`✅ Imagen agregada: ${file.name}`, imagenObj);
                }
                
                resolve({ success: true, images: this.images });
            } else {
                reject(new Error('INVALID_FILE_TYPE'));
            }
        });
    }

    /**
     * Agregar imagen desde URL o objeto de BD
     * @param {string|Object} urlOImagen - URL string u objeto de imagen
     * @param {string} nombre - Nombre opcional
     */
    agregarDesdeURL(urlOImagen, nombre = 'imagen') {
        let imagenObj;

        if (typeof urlOImagen === 'string') {
            // Es una URL string pura
            imagenObj = {
                previewUrl: urlOImagen,
                nombre: nombre,
                tamaño: 0,
                file: null,
                tipo: 'url-string',
                urlDesdeDB: true,
                timestamp: new Date().getTime()
            };
        } else if (typeof urlOImagen === 'object') {
            // Es un objeto de imagen desde BD - preservar TODOS los campos
            imagenObj = {
                id: urlOImagen.id,
                prenda_foto_id: urlOImagen.prenda_foto_id,
                previewUrl: urlOImagen.previewUrl || urlOImagen.url || urlOImagen.ruta || urlOImagen.ruta_webp,
                url: urlOImagen.url,
                ruta: urlOImagen.ruta,
                ruta_original: urlOImagen.ruta_original,
                ruta_webp: urlOImagen.ruta_webp,
                nombre: urlOImagen.nombre || nombre,
                tamaño: urlOImagen.tamaño || 0,
                file: null,
                tipo: 'url-objeto',
                urlDesdeDB: true,
                timestamp: new Date().getTime(),
                // Preservar cualquier otro campo
                ...urlOImagen
            };
        }

        this.images.push(imagenObj);

        if (this.debug) {
            console.log(`✅ URL agregada: ${nombre}`, imagenObj);
        }
    }

    /**
     * Obtener todas las imágenes
     */
    obtenerImagenes() {
        return this.images;
    }

    /**
     * Establecer array de imágenes (reemplaza todo)
     * @param {Array} nuevasImagenes - Array de imágenes
     */
    establecerImagenes(nuevasImagenes) {
        if (!Array.isArray(nuevasImagenes)) {
            console.warn('[ImageStorageFallback] establecerImagenes: No es un array válido');
            return;
        }

        // Revocar URLs blob que serán reemplazadas
        this.images.forEach(img => {
            if (img.previewUrl && img.previewUrl.startsWith('blob:')) {
                URL.revokeObjectURL(img.previewUrl);
            }
        });

        // Normalizar nuevas imágenes: asegurar que tienen previewUrl
        const imagenesNormalizadas = nuevasImagenes.map(img => {
            // Si no tiene previewUrl, usar url, ruta, o ruta_webp
            if (!img.previewUrl && (img.url || img.ruta || img.ruta_webp)) {
                return {
                    ...img,
                    previewUrl: img.url || img.ruta || img.ruta_webp
                };
            }
            return img;
        });

        this.images = imagenesNormalizadas || [];

        if (this.debug) {
            console.log(`✅ Array sincronizado: ${this.images.length} imágenes`);
        }
    }

    /**
     * Remover imagen por índice
     */
    removerImagenPorIndice(indice) {
        if (indice >= 0 && indice < this.images.length) {
            const img = this.images[indice];
            if (img.previewUrl && img.previewUrl.startsWith('blob:')) {
                URL.revokeObjectURL(img.previewUrl);
            }
            this.images.splice(indice, 1);
            if (this.debug) {
                console.log(`✅ Imagen removida en índice ${indice}`);
            }
            return true;
        }
        return false;
    }

    /**
     * Obtener cantidad de imágenes
     */
    obtenerCantidad() {
        return this.images.length;
    }

    /**
     * Verificar si está lleno
     */
    estaLleno() {
        return this.images.length >= this.maxImagenes;
    }

    /**
     * Obtener imagen por índice
     */
    obtenerImagenPorIndice(indice) {
        return this.images[indice] || null;
    }

    /**
     * Obtener primera imagen
     */
    obtenerPrimeraImagen() {
        return this.images[0] || null;
    }

    /**
     * Verificar si la imagen es de BD (URL)
     */
    esImagenDeDB(indice) {
        const img = this.images[indice];
        return img && img.urlDesdeDB === true;
    }

    /**
     * Verificar si la imagen es File
     */
    esImagenFile(indice) {
        const img = this.images[indice];
        return img && img.file instanceof File;
    }

    /**
     * Exportar datos para guardar en servidor
     */
    exportarDatos() {
        return this.images.map((img, idx) => ({
            indice: idx,
            nombre: img.nombre,
            esArchivo: img.file instanceof File,
            esURL: img.urlDesdeDB,
            previewUrl: img.previewUrl,
            tamaño: img.tamaño,
            file: img.file instanceof File ? img.file : null
        }));
    }

    /**
     * Activar/desactivar debug
     */
    setDebug(activo) {
        this.debug = activo;
    }
}

window.ImageStorageFallback = ImageStorageFallback;
