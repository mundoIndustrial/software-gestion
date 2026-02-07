/**
 * PrendaImagenService - Gestión de imágenes de prendas
 * 
 * Propósito: Encapsular toda la lógica de carga, procesamiento y presentación de imágenes
 * 
 * Soporta múltiples formatos:
 * - File objects (formulario)
 * - URLs (desde BD)
 * - Wrappers con .file
 * - Imágenes de procesos (reflectivo, logo)
 */
class PrendaImagenService {
    constructor(opciones = {}) {
        this.domAdapter = opciones.domAdapter;
        this.eventBus = opciones.eventBus;
        this.storage = opciones.storage || null; // window.imagenesPrendaStorage
    }

    /**
     * Cargar imágenes desde prenda con estrategia de prioridades
     * 
     * Prioridad:
     * 0. imagenes (formulario con archivos)
     * 1. fotos (BD alternativo)
     * 2. Imágenes de procesos (reflectivo, logo, etc)
     */
    async cargarImagenes(prenda) {
        console.log('[PrendaImagenService.cargarImagenes] Iniciando:', {
            tieneImagenes: !!prenda.imagenes,
            tieneFotos: !!prenda.fotos,
            tieneProcesos: !!prenda.procesos
        });

        // Asegurar que storage existe
        if (!this.storage) {
            this.inicializarStorage();
        }

        let imagenesACargar = [];
        let origen = 'desconocido';

        // PRIORIDAD 0: imagenes (formulario)
        if (prenda.imagenes && Array.isArray(prenda.imagenes) && prenda.imagenes.length > 0) {
            const resultado = this.detectarFormatoImagenes(prenda.imagenes);
            if (resultado.valido) {
                imagenesACargar = prenda.imagenes;
                origen = resultado.origen;
            }
        }

        // PRIORIDAD 1: fotos (BD)
        if (imagenesACargar.length === 0 && prenda.fotos && Array.isArray(prenda.fotos) && prenda.fotos.length > 0) {
            console.log('[PrendaImagenService] Detectado: fotos de BD');
            imagenesACargar = prenda.fotos;
            origen = 'bd-fotos';
        }

        // PRIORIDAD 2: Imágenes de procesos
        if (imagenesACargar.length === 0 && prenda.procesos && typeof prenda.procesos === 'object') {
            const resultado = this.extraerImagenesDeProcesos(prenda.procesos);
            if (resultado) {
                imagenesACargar = resultado;
                origen = 'procesos-reflectivo-logo';
            }
        }

        if (imagenesACargar.length === 0) {
            console.log('[PrendaImagenService] No hay imágenes para cargar');
            this.eventBus?.emit(PrendaEventBus.EVENTOS.IMAGENES_CARGADAS, []);
            return;
        }

        console.log(`[PrendaImagenService] Cargando ${imagenesACargar.length} imágenes desde: ${origen}`);

        // Limpiar storage
        this.limpiarStorage();

        // Procesar cada imagen
        imagenesACargar.forEach((img, idx) => {
            this.procesarImagenIndividual(img, idx);
        });

        // Actualizar preview
        this.actualizarPreviewImagenes(imagenesACargar);

        this.eventBus?.emit(PrendaEventBus.EVENTOS.IMAGENES_CARGADAS, {
            cantidad: imagenesACargar.length,
            origen,
            imagenes: imagenesACargar
        });

        console.log(`[PrendaImagenService] ✓ ${imagenesACargar.length} imágenes cargadas`);
    }

    /**
     * Detectar formato de imágenes (File vs URL)
     * @private
     */
    detectarFormatoImagenes(imagenes) {
        if (!imagenes || imagenes.length === 0) {
            return { valido: false, origen: null };
        }

        const primerItem = imagenes[0];

        // Caso 1: File object
        if (primerItem instanceof File) {
            console.log('[PrendaImagenService] Formato detectado: File objects');
            return { valido: true, origen: 'formulario-files' };
        }

        // Caso 2: Wrapper con .file
        if (primerItem.file instanceof File) {
            console.log('[PrendaImagenService] Formato detectado: Wrapper con File');
            return { valido: true, origen: 'formulario-wrapper' };
        }

        // Caso 3: String o URL
        if (typeof primerItem === 'string') {
            console.log('[PrendaImagenService] Formato detectado: URLs (strings)');
            return { valido: true, origen: 'urls-directas' };
        }

        // Caso 4: Objeto con URL/ruta
        if (primerItem && (primerItem.url || primerItem.ruta || primerItem.ruta_webp)) {
            console.log('[PrendaImagenService] Formato detectado: Objetos con URL/ruta');
            return { valido: true, origen: 'objetos-url' };
        }

        return { valido: false, origen: null };
    }

    /**
     * Extraer imágenes desde procesos
     * @private
     */
    extraerImagenesDeProcesos(procesos) {
        if (!procesos) return null;

        const procesosArray = Array.isArray(procesos) ? procesos : Object.values(procesos);

        for (const proceso of procesosArray) {
            const datosProc = proceso.datos ? proceso.datos : proceso;
            if (datosProc.imagenes && Array.isArray(datosProc.imagenes) && datosProc.imagenes.length > 0) {
                console.log('[PrendaImagenService] ✨ Imágenes extraídas desde procesos');
                return datosProc.imagenes;
            }
        }

        return null;
    }

    /**
     * Procesar una imagen individual
     * Soporta: File, Wrapper, URL, objeto con URL
     * @private
     */
    procesarImagenIndividual(img, idx = 0) {
        if (!img) {
            console.log(`[PrendaImagenService] Imagen ${idx} es null/undefined`);
            return;
        }

        if (!this.storage) {
            console.warn('[PrendaImagenService] Storage no disponible');
            return;
        }

        try {
            // CASO 1: File directo
            if (img instanceof File) {
                console.log(`[PrendaImagenService] Procesando imagen ${idx}: File object`);
                this.storage.agregarImagen(img);
                return;
            }

            // CASO 2: Wrapper con .file
            if (img.file instanceof File) {
                console.log(`[PrendaImagenService] Procesando imagen ${idx}: Wrapper File`);
                this.storage.agregarImagen(img.file);
                return;
            }

            // CASO 3: URL string
            if (typeof img === 'string' && img.length > 0) {
                console.log(`[PrendaImagenService] Procesando imagen ${idx}: URL string`);
                this.procesarDesdeURL(img);
                return;
            }

            // CASO 4: Objeto con URL
            if (img && typeof img === 'object') {
                const url = img.url || img.ruta || img.ruta_webp || img.ruta_original;
                if (url) {
                    console.log(`[PrendaImagenService] Procesando imagen ${idx}: Objeto con URL`);
                    this.procesarDesdeURL(url);
                    return;
                }
            }

            console.warn(`[PrendaImagenService] Imagen ${idx}: formato no reconocido`, img);

        } catch (error) {
            console.error(`[PrendaImagenService] Error procesando imagen ${idx}:`, error);
        }
    }

    /**
     * Procesar imagen desde URL (fetch + crear reference)
     * @private
     */
    procesarDesdeURL(url) {
        if (!this.storage) return;

        // Crear referencia de imagen desde URL
        // El storage debe ser capaz de manejar esto
        if (this.storage.agregarDesdeURL) {
            this.storage.agregarDesdeURL(url);
        } else if (this.storage.agregarImagen) {
            // Fallback: agregar como referencia simple
            this.storage.agregarImagen({
                url: url,
                previewUrl: url,
                tipo: 'url'
            });
        }
    }

    /**
     * Actualizar preview de imagenes en el DOM
     * @private
     */
    actualizarPreviewImagenes(imagenes) {
        if (!this.storage || this.storage.images.length === 0) {
            console.log('[PrendaImagenService] No hay imágenes en storage para preview');
            return;
        }

        console.log('[PrendaImagenService] Actualizando preview visual');

        // Si existe función global, usar esa (compatibilidad)
        if (typeof window.actualizarPreviewPrenda === 'function') {
            console.log('[PrendaImagenService] Usando window.actualizarPreviewPrenda()');
            window.actualizarPreviewPrenda();
            return;
        }

        // Usar DOM adapter si existe
        const primerImg = this.storage.images[0];
        const urlImg = primerImg.previewUrl || primerImg.url;

        if (urlImg && this.domAdapter) {
            this.domAdapter.establecerPreviewImagen(urlImg);
            this.domAdapter.establecerContadorImagenes(this.storage.images.length);
        }

        console.log('[PrendaImagenService] Preview actualizado');
    }

    /**
     * Validar imagen antes de procesar
     */
    validarImagen(img) {
        if (!img) return { valido: false, razon: 'Imagen null/undefined' };

        // Validar File
        if (img instanceof File) {
            if (img.size > 10 * 1024 * 1024) { // 10MB
                return { valido: false, razon: 'Archivo muy grande' };
            }
            if (!['image/jpeg', 'image/png', 'image/webp'].includes(img.type)) {
                return { valido: false, razon: 'Formato no soportado' };
            }
            return { valido: true };
        }

        // Validar URL
        if (typeof img === 'string' && img.length > 0) {
            return { valido: true };
        }

        // Validar objeto con URL
        if (img && (img.url || img.ruta)) {
            return { valido: true };
        }

        return { valido: false, razon: 'Formato no reconocido' };
    }

    /**
     * Obtener cantidad de imágenes en storage
     */
    obtenerCantidadImagenes() {
        return this.storage?.images?.length || 0;
    }

    /**
     * Limpiar todas las imágenes
     */
    limpiarImagenes() {
        this.limpiarStorage();
        if (this.domAdapter) {
            this.domAdapter.limpiarPreviewImagen();
        }
        this.eventBus?.emit(PrendaEventBus.EVENTOS.IMAGENES_CARGADAS, []);
    }

    /**
     * Limpiar storage de imágenes
     * @private
     */
    limpiarStorage() {
        if (this.storage && typeof this.storage.limpiar === 'function') {
            this.storage.limpiar();
        }
    }

    /**
     * Inicializar storage si no existe
     * @private
     */
    inicializarStorage() {
        // Usar window.imagenesPrendaStorage si está disponible
        if (window.imagenesPrendaStorage) {
            this.storage = window.imagenesPrendaStorage;
            return;
        }

        // Si existe ImageStorageService, crear instancia
        if (typeof ImageStorageService !== 'undefined') {
            try {
                this.storage = new ImageStorageService();
                window.imagenesPrendaStorage = this.storage;
                console.log('[PrendaImagenService] Storage creado con ImageStorageService');
                return;
            } catch (error) {
                console.error('[PrendaImagenService] Error créando ImageStorageService:', error);
            }
        }

        // Fallback: crear mock simple
        console.warn('[PrendaImagenService] Creando storage simple (mock)');
        this.storage = {
            images: [],
            agregarImagen: function(img) {
                this.images.push(img);
            },
            limpiar: function() {
                this.images = [];
            }
        };
        window.imagenesPrendaStorage = this.storage;
    }

    /**
     * Obtener storage actual
     */
    obtenerStorage() {
        return this.storage;
    }

    /**
     * Configurar storage personalizado
     */
    establecerStorage(storage) {
        this.storage = storage;
        window.imagenesPrendaStorage = storage;
    }
}

window.PrendaImagenService = PrendaImagenService;
