/**
 * MÓDULO: tableManager.js
 * Responsabilidad: Orquestar todos los módulos y gestionar el ciclo de vida de la tabla
 * Principios SOLID: SRP (Single Responsibility - orchestration), DIP (Dependency Inversion)
 */

const TableManager = {
    // Estado de inicialización
    initialized: false,
    modules: {},

    /**
     * Inicializar todos los módulos en orden correcto
     */
    async init() {
        if (this.initialized) {
            console.warn('⚠️ TableManager ya fue inicializado');
            return;
        }

        console.log('🚀 Iniciando TableManager...');
        
        try {
            // Fase 1: Inicializar módulos sin dependencias
            this._loadPhase1();
            
            // Fase 2: Inicializar módulos con dependencias
            this._loadPhase2();
            
            // Fase 3: Configurar integraciones
            this._loadPhase3();
            
            // Fase 4: Adjuntar listeners globales
            this._attachGlobalListeners();
            
            this.initialized = true;
            console.log(' TableManager inicializado correctamente');
        } catch (error) {
            console.error(' Error al inicializar TableManager:', error);
            this._handleInitializationError(error);
        }
    },

    /**
     * FASE 1: Módulos sin dependencias
     */
    _loadPhase1() {
        console.log('📦 Fase 1: Inicializando módulos base...');
        
        // Estos módulos no dependen de otros
        this.modules.notification = NotificationModule;
        this.modules.formatting = FormattingModule;
        this.modules.storage = StorageModule;
        
        // Inicializar storage listeners para sincronización cross-tab
        if (StorageModule.initializeListener) {
            StorageModule.initializeListener();
        }
        
        console.log(' Fase 1 completada');
    },

    /**
     * FASE 2: Módulos con dependencias
     */
    _loadPhase2() {
        console.log('📦 Fase 2: Inicializando módulos dependientes...');
        
        // UpdatesModule depende de NotificationModule
        this.modules.updates = UpdatesModule;
        
        // DropdownManager depende de UpdatesModule
        this.modules.dropdownManager = DropdownManager;
        if (DropdownManager.initialize) {
            DropdownManager.initialize();
        }
        
        // RowManager depende de FormattingModule
        this.modules.rowManager = RowManager;
        
        // DiaEntregaModule depende de UpdatesModule
        this.modules.diaEntrega = DiaEntregaModule;
        if (DiaEntregaModule.initialize) {
            DiaEntregaModule.initialize();
        }
        
        console.log(' Fase 2 completada');
    },

    /**
     * FASE 3: Integraciones y configuraciones
     */
    _loadPhase3() {
        console.log('📦 Fase 3: Configurando integraciones...');
        
        // Inicializar todos los dropdowns
        this._initializeAllDropdowns();
        
        // Configurar handlers de WebSocket
        this._setupWebSocketHandlers();
        
        console.log(' Fase 3 completada');
    },

    /**
     * FASE 4: Listeners globales
     */
    _attachGlobalListeners() {
        console.log('📦 Fase 4: Adjuntando listeners globales...');
        
        // Detectar cuando la página está a punto de recargar
        window.addEventListener('beforeunload', () => {
            console.log('🔄 Página a recargar');
        });
        
        // Detectar cambios de visibilidad (tab cambió de activo)
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                console.log('👁️ Tab ocultada');
            } else {
                console.log('👁️ Tab visible');
            }
        });
        
        console.log(' Fase 4 completada');
    },

    /**
     * Inicializar todos los dropdowns en la tabla
     */
    _initializeAllDropdowns() {
        const statusSelects = document.querySelectorAll('.estado-select');
        const areaSelects = document.querySelectorAll('.area-select');
        const diaSelects = document.querySelectorAll('.dia-entrega-select');
        
        console.log(` Encontrados: ${statusSelects.length} dropdowns estado, ${areaSelects.length} área, ${diaSelects.length} día`);
        
        // Inicializar dropdowns de estado y área
        if (DropdownManager.initializeStatusDropdowns) {
            DropdownManager.initializeStatusDropdowns();
        }
        
        if (DropdownManager.initializeAreaDropdowns) {
            DropdownManager.initializeAreaDropdowns();
        }
    },

    /**
     * Configurar handlers de WebSocket para actualizaciones en tiempo real
     */
    _setupWebSocketHandlers() {
        // Este método será complementado por realtime-listeners.js
        // que se carga después de este módulo
        console.log('🔌 WebSocket handlers configurados');
    },

    /**
     * Método público para obtener un módulo
     */
    getModule(moduleName) {
        if (!this.modules[moduleName]) {
            console.warn(`⚠️ Módulo '${moduleName}' no encontrado`);
            return null;
        }
        return this.modules[moduleName];
    },

    /**
     * Método público para listar módulos cargados
     */
    listModules() {
        return {
            loaded: Object.keys(this.modules),
            initialized: this.initialized
        };
    },

    /**
     * Recargar tabla
     */
    reloadTable() {
        console.log('🔄 Recargando tabla...');
        location.reload();
    },

    /**
     * Manejar errores de inicialización
     */
    _handleInitializationError(error) {
        console.error(' Error crítico:', error);
        
        // Mostrar notificación al usuario
        if (NotificationModule && NotificationModule.showError) {
            NotificationModule.showError(
                'Error al inicializar la tabla. Recargue la página.',
                5000
            );
        }
        
        // Log adicional para debugging
        console.log('Estado de módulos:', this.modules);
    },

    /**
     * Verificar disponibilidad de módulos requeridos
     */
    verifyDependencies() {
        const required = [
            'NotificationModule',
            'FormattingModule',
            'UpdatesModule',
            'DropdownManager',
            'RowManager',
            'StorageModule',
            'DiaEntregaModule'
        ];
        
        const missing = required.filter(module => {
            const globalModule = window[module];
            return !globalModule || !Object.keys(globalModule).length;
        });
        
        if (missing.length > 0) {
            console.warn(`⚠️ Módulos faltantes: ${missing.join(', ')}`);
            return false;
        }
        
        console.log(' Todas las dependencias disponibles');
        return true;
    }
};

/**
 * Auto-inicializar cuando el DOM está listo
 */
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        // Verificar dependencias antes de inicializar
        if (TableManager.verifyDependencies()) {
            TableManager.init();
        }
    });
} else {
    // DOM ya está listo
    if (TableManager.verifyDependencies()) {
        TableManager.init();
    }
}

