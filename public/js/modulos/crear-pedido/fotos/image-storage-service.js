/**
 * Image Storage Service (CON COMPRESIÓN AUTOMÁTICA)
 * Maneja almacenamiento temporal de imágenes como File objects
 * - Comprime automáticamente imágenes grandes a WebP
 * - Valida tamaños después de comprimir
 * - Usa URL.createObjectURL() para preview
 */

class ImageStorageService {
    constructor(maxImages = 3) {
        this.maxImages = maxImages;
        this.images = []; // Array de { file, previewUrl, nombre, tamano, tamanioOriginal, comprimida }
        this.snapshotOriginal = null; // SNAPSHOT de imágenes originales (para detectar eliminaciones)
        this.MAX_SIZE_SIN_COMPRIMIR_MB = 2;
        this.MAX_SIZE_COMPRIMIDO_MB = 3;
        this.CALIDAD_WEBP = 0.8;
    }

    /**
     * Agregar imagen desde input file
     * Comprime automáticamente si es > 2MB
     * RETORNA UNA PROMISE
     */
    async agregarImagen(file) {
        if (!file?.type?.startsWith('image/')) {
            throw new Error('INVALID_FILE');
        }

        if (this.images.length >= this.maxImages) {
            throw new Error('MAX_LIMIT');
        }

        const originalSizeMB = file.size / (1024 * 1024);
        const inicioTiempo = performance.now();

        try {
            // 1. Comprimir si es necesario
            let fileAUsar = file;
            let comprimida = false;

            if (originalSizeMB > this.MAX_SIZE_SIN_COMPRIMIR_MB) {
                console.log(`[ImageStorageService] Comprimiendo imagen (${originalSizeMB.toFixed(1)}MB)...`);
                fileAUsar = await this._comprimirImagen(file);
                comprimida = true;
                const comprimidoMB = fileAUsar.size / (1024 * 1024);
                console.log(`[ImageStorageService] ✅ Comprimida: ${originalSizeMB.toFixed(1)}MB → ${comprimidoMB.toFixed(1)}MB`);
            }

            // 2. Validar tamaño final
            const finalSizeMB = fileAUsar.size / (1024 * 1024);
            if (finalSizeMB > this.MAX_SIZE_COMPRIMIDO_MB) {
                throw new Error(
                    `FILE_TOO_LARGE: La imagen es demasiado grande. Tamaño final: ${finalSizeMB.toFixed(1)}MB (máximo: ${this.MAX_SIZE_COMPRIMIDO_MB}MB)`
                );
            }

            // 3. Crear URL para preview
            const previewUrl = URL.createObjectURL(fileAUsar);

            this.images.push({
                file: fileAUsar,
                previewUrl,
                nombre: file.name,
                tamano: fileAUsar.size,
                tamanioOriginal: file.size,
                comprimida
            });

            // 4. Registrar en analytics si está disponible
            const tiempoTranscurrido = performance.now() - inicioTiempo;
            if (globalThis.DraftPedidoAnalytics?.registrarImagenProcesada) {
                globalThis.DraftPedidoAnalytics.registrarImagenProcesada(file.size, fileAUsar.size, comprimida);
                globalThis.DraftPedidoAnalytics.registrarTiempo(tiempoTranscurrido);
            }

            return { success: true, images: this.images };

        } catch (error) {
            console.error('[ImageStorageService] Error procesando imagen:', error);
            throw new Error(`ERROR_PROCESSING: ${error.message}`);
        }
    }

    /**
     * Comprimir imagen a WebP
     * @private
     */
    async _comprimirImagen(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();

            reader.onload = (e) => {
                const img = new Image();

                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');

                    let width = img.width;
                    let height = img.height;

                    // Redimensionar si es más ancho que 1920px
                    if (width > 1920) {
                        height = (height * 1920) / width;
                        width = 1920;
                    }

                    canvas.width = width;
                    canvas.height = height;
                    ctx.drawImage(img, 0, 0, width, height);

                    // Convertir a WebP con calidad configurable
                    canvas.toBlob(
                        (blob) => {
                            if (!blob) {
                                reject(new Error('Error al crear blob'));
                                return;
                            }
                            const fileComprimido = new File([blob], file.name, {
                                type: 'image/webp'
                            });
                            resolve(fileComprimido);
                        },
                        'image/webp',
                        this.CALIDAD_WEBP
                    );
                };

                img.onerror = () => {
                    reject(new Error('Error al cargar imagen'));
                };

                img.src = e.target.result;
            };

            reader.onerror = () => {
                reject(new Error('Error al leer archivo'));
            };

            reader.readAsDataURL(file);
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
        //  DIAGNÓSTICO: Capturar stack trace para identificar quién llama con array vacío
        if (Array.isArray(nuevasImagenes) && nuevasImagenes.length === 0) {
            console.warn(' [ImageStorageService] LLAMADA CON ARRAY VACÍO - Stack trace:', {
                caller: new Error().stack?.split('\n')[1]?.trim(),
                stack: new Error().stack
            });
        }
        
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
            console.log(' [ImageStorageService]  SNAPSHOT GUARDADO de', nuevasImagenes.length, 'imágenes originales');
        } else if (this.snapshotOriginal !== null) {
            console.log(' [ImageStorageService] ⏸️ SNAPSHOT YA EXISTE - PRESERVANDO SNAPSHOT EXISTENTE CON IDs', {
                snapshotImagenes: this.snapshotOriginal.length,
                nuevasImagenes: nuevasImagenes.length,
                primerImagenSnapshot: this.snapshotOriginal[0]
            });
        }
        
        // Limpiar URLs de imágenes que serán reemplazadas
        //  CRÍTICO FIX: Solo revocar blob URLs de imágenes SIN File object
        // Las imágenes nuevas (con File object) necesitan mantener su blob URL
        this.images.forEach(img => {
            if (img.previewUrl && img.previewUrl.startsWith('blob:')) {
                // Solo revocar si NO tiene File object (imagen antigua/de BD)
                if (!img.file || !(img.file instanceof File)) {
                    URL.revokeObjectURL(img.previewUrl);
                } else {
                    console.log('[ImageStorageService.establecerImagenes]  Preservando blob URL de imagen con File object');
                }
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
