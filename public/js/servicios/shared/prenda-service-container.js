/**
 * 🔒 PrendaServiceContainer
 * 
 * Contenedor de inyección de dependencias para servicios de prendas
 * 
 * IMPORTANTE:
 * - COMPLETAMENTE AISLADO de cotizaciones
 * - Punto único de inicialización
 * - Usa solo en módulos de pedidos (crear-nuevo, editar)
 * - NUNCA importar en cotizaciones
 */

class PrendaServiceContainer {
    constructor(config = {}) {
        this.services = {};
        this.initialized = false;
        this.config = {
            debug: config.debug || false,
            apiBaseUrl: config.apiBaseUrl || '/api/prendas',
            storageEndpoint: config.storageEndpoint || '/api/storage/prendas',
            cacheEnabled: config.cacheEnabled !== false,
            cacheTTL: config.cacheTTL || 5 * 60 * 1000, // 5 minutos
            ...config
        };

        if (this.config.debug) {
            Logger.debug('Modo DEBUG habilitado', 'PrendaServiceContainer');
        }
    }

    /**
     * Inicializar todos los servicios
     * Debe llamarse UNA SOLA VEZ al cargar el módulo
     */
    async initialize() {
        if (this.initialized) {
            Logger.debug('Ya inicializado, ignorando...', 'PrendaServiceContainer');
            return;
        }

        Logger.info('Inicializando servicios compartidos...', 'PrendaServiceContainer');

        try {
            // 1️⃣ EventBus
            this.services.eventBus = new EventBus();
            if (this.config.debug) {
                this.services.eventBus.enableDebug(true);
            }
            Logger.debug('EventBus inicializado', 'PrendaServiceContainer');

            // 2️⃣ FormatDetector
            this.services.formatDetector = new FormatDetector();
            if (this.config.debug) {
                this.services.formatDetector.enableDebug(true);
            }
            Logger.debug('FormatDetector inicializado', 'PrendaServiceContainer');

            // 3️⃣ DataService
            this.services.data = new SharedPrendaDataService({
                apiBaseUrl: this.config.apiBaseUrl,
                cacheEnabled: this.config.cacheEnabled,
                cacheTTL: this.config.cacheTTL,
                formatDetector: this.services.formatDetector
            });
            Logger.debug('DataService inicializado', 'PrendaServiceContainer');

            // 4️⃣ StorageService
            this.services.storage = new SharedPrendaStorageService({
                endpointBase: this.config.storageEndpoint,
                maxFileSize: this.config.maxFileSize || 5 * 1024 * 1024
            });
            Logger.debug('StorageService inicializado', 'PrendaServiceContainer');

            // 5️⃣ ValidationService
            this.services.validation = new SharedPrendaValidationService({
                rules: this.config.validationRules
            });
            Logger.debug('ValidationService inicializado', 'PrendaServiceContainer');

            // 6️⃣ EditorService (orquestador principal)
            this.services.editor = new SharedPrendaEditorService({
                dataService: this.services.data,
                storageService: this.services.storage,
                validationService: this.services.validation,
                eventBus: this.services.eventBus
            });
            Logger.debug('EditorService inicializado', 'PrendaServiceContainer');

            // 7️⃣ Conectar eventos
            this.conectarEventos();
            Logger.debug('Eventos conectados', 'PrendaServiceContainer');

            // Marcar como inicializado
            this.initialized = true;
            this.services.eventBus.emit('container:inicializado');

            Logger.success('Todos los servicios inicializados correctamente', 'PrendaServiceContainer');

        } catch (error) {
            Logger.error('Error inicializando servicios', 'PrendaServiceContainer', error);
            throw error;
        }
    }

    /**
     * Obtener servicio
     */
    getService(nombreServicio) {
        if (!this.initialized) {
            throw new Error(
                `[PrendaServiceContainer] No inicializado. ` +
                `Llama a initialize() primero: await container.initialize()`
            );
        }

        if (!this.services[nombreServicio]) {
            throw new Error(
                `[PrendaServiceContainer] Servicio no encontrado: ${nombreServicio}. ` +
                `Disponibles: ${Object.keys(this.services).join(', ')}`
            );
        }

        return this.services[nombreServicio];
    }

    /**
     * Obtener todos los servicios
     */
    getAllServices() {
        if (!this.initialized) {
            throw new Error('[PrendaServiceContainer] No inicializado');
        }
        return { ...this.services };
    }

    /**
     * Conectar eventos para sincronizar UI con servicios
     */
    conectarEventos() {
        const { eventBus, editor } = this.services;

        // Cuando el editor carga datos, emitir evento para UI
        eventBus.on('editor:datos-cargados', (datos) => {
            Logger.debug('Evento: datos-cargados', 'PrendaServiceContainer');
            // En la práctica, la UI se suscribe a este evento
        });

        // Cuando hay error
        eventBus.on('editor:error', (error) => {
            Logger.error('Evento: error', 'PrendaServiceContainer', error);
        });

        // Cuando se guarda
        eventBus.on('editor:guardado', (prenda) => {
            Logger.debug('Evento: guardado', 'PrendaServiceContainer', prenda.nombre);
        });
    }

    /**
     * Limpiar recursos (logout, cambio de página, etc)
     */
    destroy() {
        Logger.info('Destruyendo servicios...', 'PrendaServiceContainer');

        // Limpiar éventualmente
        if (this.services.data) {
            this.services.data.limpiarCache();
        }

        if (this.services.eventBus) {
            this.services.eventBus.clear();
        }

        this.services = {};
        this.initialized = false;

        Logger.debug('Servicios destruidos', 'PrendaServiceContainer');
    }

    /**
     * Obtener estadísticas
     */
    getEstadisticas() {
        return {
            inicializado: this.initialized,
            servicios: Object.keys(this.services),
            cacheStats: this.services.data?.getEstadisticasCache?.(),
            editorState: this.services.editor?.getEstado?.()
        };
    }

    /**
     * Habilitar/deshabilitar debug
     */
    setDebug(enabled) {
        this.config.debug = enabled;

        if (this.services.eventBus) {
            this.services.eventBus.enableDebug(enabled);
        }

        if (this.services.formatDetector) {
            this.services.formatDetector.enableDebug(enabled);
        }

        Logger.info(`Debug: ${enabled ? 'HABILITADO' : 'DESHABILITADO'}`, 'PrendaServiceContainer');
    }
}

// Crear instancia global UNA SOLA VEZ
if (!window.prendasServiceContainer) {
    window.prendasServiceContainer = new PrendaServiceContainer({
        debug: false, // Cambiar a true para debugging
        apiBaseUrl: '/api/prendas',
        storageEndpoint: '/api/storage/prendas',
        cacheEnabled: true,
        cacheTTL: 5 * 60 * 1000
    });
}

// Exportar
window.PrendaServiceContainer = PrendaServiceContainer;
Logger.debug('Contenedor cargado', 'PrendaServiceContainer');
