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
            console.log('[PrendaServiceContainer]  Modo DEBUG habilitado');
        }
    }

    /**
     * Inicializar todos los servicios
     * Debe llamarse UNA SOLA VEZ al cargar el módulo
     */
    async initialize() {
        if (this.initialized) {
            console.log('[PrendaServiceContainer]  Ya inicializado, ignorando...');
            return;
        }

        console.log('[PrendaServiceContainer] 🚀 Inicializando servicios compartidos...');

        try {
            // 1️⃣ EventBus
            this.services.eventBus = new EventBus();
            if (this.config.debug) {
                this.services.eventBus.enableDebug(true);
            }
            console.log('[PrendaServiceContainer] ✓ EventBus inicializado');

            // 2️⃣ FormatDetector
            this.services.formatDetector = new FormatDetector();
            if (this.config.debug) {
                this.services.formatDetector.enableDebug(true);
            }
            console.log('[PrendaServiceContainer] ✓ FormatDetector inicializado');

            // 3️⃣ DataService
            this.services.data = new SharedPrendaDataService({
                apiBaseUrl: this.config.apiBaseUrl,
                cacheEnabled: this.config.cacheEnabled,
                cacheTTL: this.config.cacheTTL,
                formatDetector: this.services.formatDetector
            });
            console.log('[PrendaServiceContainer] ✓ DataService inicializado');

            // 4️⃣ StorageService
            this.services.storage = new SharedPrendaStorageService({
                endpointBase: this.config.storageEndpoint,
                maxFileSize: this.config.maxFileSize || 5 * 1024 * 1024
            });
            console.log('[PrendaServiceContainer] ✓ StorageService inicializado');

            // 5️⃣ ValidationService
            this.services.validation = new SharedPrendaValidationService({
                rules: this.config.validationRules
            });
            console.log('[PrendaServiceContainer] ✓ ValidationService inicializado');

            // 6️⃣ EditorService (orquestador principal)
            this.services.editor = new SharedPrendaEditorService({
                dataService: this.services.data,
                storageService: this.services.storage,
                validationService: this.services.validation,
                eventBus: this.services.eventBus
            });
            console.log('[PrendaServiceContainer] ✓ EditorService inicializado');

            // 7️⃣ Conectar eventos
            this.conectarEventos();
            console.log('[PrendaServiceContainer] ✓ Eventos conectados');

            // Marcar como inicializado
            this.initialized = true;
            this.services.eventBus.emit('container:inicializado');

            console.log('[PrendaServiceContainer]  TODOS LOS SERVICIOS INICIALIZADOS CORRECTAMENTE');
            console.log('[PrendaServiceContainer] 🔐 AISLADO DE COTIZACIONES');

        } catch (error) {
            console.error('[PrendaServiceContainer]  Error inicializando:', error);
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
            console.log('[PrendaServiceContainer] 📢 Evento: datos-cargados');
            // En la práctica, la UI se suscribe a este evento
        });

        // Cuando hay error
        eventBus.on('editor:error', (error) => {
            console.error('[PrendaServiceContainer] 📢 Evento: error', error);
        });

        // Cuando se guarda
        eventBus.on('editor:guardado', (prenda) => {
            console.log('[PrendaServiceContainer] 📢 Evento: guardado', prenda.nombre);
        });
    }

    /**
     * Limpiar recursos (logout, cambio de página, etc)
     */
    destroy() {
        console.log('[PrendaServiceContainer] 🗑️ Destruyendo servicios...');

        // Limpiar éventualmente
        if (this.services.data) {
            this.services.data.limpiarCache();
        }

        if (this.services.eventBus) {
            this.services.eventBus.clear();
        }

        this.services = {};
        this.initialized = false;

        console.log('[PrendaServiceContainer] ✓ Servicios destruidos');
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

        console.log(`[PrendaServiceContainer] Debug: ${enabled ? 'HABILITADO' : 'DESHABILITADO'}`);
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
console.log('[PrendaServiceContainer] 🔐 Contenedor cargado (AISLADO DE COTIZACIONES)');
