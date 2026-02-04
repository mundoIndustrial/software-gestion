/**
 * Image Storage Service (SIN BASE64)
 * Maneja almacenamiento temporal de imágenes como File objects
 * Usa URL.createObjectURL() para preview, sin base64
 */

class ImageStorageService {
    constructor(maxImages = 3) {
        this.maxImages = maxImages;
        this.images = []; // Array de { file, previewUrl, nombre, tamaño }
        this.snapshotOriginal = null; // SNAPSHOT de imágenes originales (para detectar eliminaciones)
    }

    /**
     * Agregar imagen desde input file
     * Usa URL.createObjectURL() para preview en lugar de base64
     * RETORNA UNA PROMISE
     */
    agregarImagen(file) {
        return new Promise((resolve, reject) => {
            if (!file || !file.type.startsWith('image/')) {
                reject(new Error('INVALID_FILE'));
                return;
            }

            if (this.images.length >= this.maxImages) {
                reject(new Error('MAX_LIMIT'));
                return;
            }

            // Crear URL para preview sin base64
            const previewUrl = URL.createObjectURL(file);
            
            this.images.push({
                file: file,
                previewUrl: previewUrl,
                nombre: file.name,
                tamaño: file.size,
            });

            resolve({ success: true, images: this.images });
        });
    }

    /**
     * Obtener todas las imágenes
     */
    obtenerImagenes() {
        return this.images;
    }

    /**
     *  Establecer/reemplazar el array completo de imágenes
     * Usado cuando la galería elimina una imagen y necesita sincronizar el storage
     * 
     * Normaliza las imágenes para asegurar que tengan previewUrl
     * IMPORTANTE: Preserva un snapshot existente (no lo sobrescribe)
     */
    establecerImagenes(nuevasImagenes) {
        if (!Array.isArray(nuevasImagenes)) {
            console.warn(' [ImageStorageService.establecerImagenes] No es un array válido');
            return;
        }
        
        // CRÍTICO: Si este es el primer establecimiento de imágenes y no tenemos snapshot
        // Guardar un snapshot para detectar eliminaciones después
        // PERO: Si ya existe un snapshot (establecido manualmente desde prenda-editor-modal)
        // NO sobrescribirlo - preservar el snapshot con IDs
        if (this.snapshotOriginal === null && nuevasImagenes.length > 0) {
            this.snapshotOriginal = JSON.parse(JSON.stringify(nuevasImagenes));
            console.log(' [ImageStorageService] 📸 SNAPSHOT GUARDADO de', nuevasImagenes.length, 'imágenes originales');
        } else if (this.snapshotOriginal !== null) {
            console.log(' [ImageStorageService] ⏸️ SNAPSHOT YA EXISTE - PRESERVANDO SNAPSHOT EXISTENTE CON IDs', {
                snapshotImagenes: this.snapshotOriginal.length,
                nuevasImagenes: nuevasImagenes.length,
                primerImagenSnapshot: this.snapshotOriginal[0]
            });
        }
        
        // Limpiar URLs de imágenes que serán reemplazadas
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
        
        // Reemplazar el array
        this.images = imagenesNormalizadas || [];
        console.log(' [ImageStorageService.establecerImagenes] Array sincronizado y normalizado, ahora hay', this.images.length, 'imágenes');
    }

    /**
     * Obtener imagen por índice
     */
    obtenerImagen(index) {
        return this.images[index] || null;
    }

    /**
     * Eliminar imagen por índice y liberar memoria
     */
    eliminarImagen(index) {
        if (index < 0 || index >= this.images.length) {
            throw new Error('Índice de imagen inválido');
        }

        const imagen = this.images[index];
        // Liberar la URL creada para evitar memory leak
        if (imagen.previewUrl) {
            URL.revokeObjectURL(imagen.previewUrl);
        }

        this.images.splice(index, 1);
        return this.images;
    }

    /**
     * Obtener cantidad de imágenes
     */
    contar() {
        return this.images.length;
    }

    /**
     * Verificar si hay espacio para más imágenes
     */
    tieneEspacio() {
        return this.images.length < this.maxImages;
    }

    /**
     * Limpiar todas las imágenes y liberar URLs
     */
    limpiar() {
        this.images.forEach(img => {
            if (img.previewUrl) {
                URL.revokeObjectURL(img.previewUrl);
            }
        });
        this.images = [];
    }

    /**
     * Obtener File objects para envío via FormData
     * Esto se usa cuando se envía el pedido
     */
    obtenerArchivos() {
        return this.images.map(img => img.file);
    }

    /**
     * Convertir a FormData para envío
     */
    toFormData(fieldName = 'imagenes') {
        const formData = new FormData();
        
        this.images.forEach((img, index) => {
            formData.append(`${fieldName}[${index}]`, img.file);
        });

        return formData;
    }
}

// Asignar a window para disponibilidad global (especialmente en carga dinámica)
window.ImageStorageService = ImageStorageService;

// NOTA: Las instancias globales se crean en crear-desde-cotizacion-editable.blade.php
// en el evento DOMContentLoaded para asegurar que el DOM esté listo
