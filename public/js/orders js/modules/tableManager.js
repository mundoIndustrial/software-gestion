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

            return;
        }


        
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

        } catch (error) {

            this._handleInitializationError(error);
        }
    },

    /**
     * FASE 1: Módulos sin dependencias
     */
    _loadPhase1() {

        
        // Estos módulos no dependen de otros
        this.modules.notification = NotificationModule;
        this.modules.formatting = FormattingModule;
        this.modules.storage = StorageModule;
        
        // Inicializar storage listeners para sincronización cross-tab
        if (StorageModule.initializeListener) {
            StorageModule.initializeListener();
        }
        

    },

    /**
     * FASE 2: Módulos con dependencias
     */
    _loadPhase2() {

        
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
        

    },

    /**
     * FASE 3: Integraciones y configuraciones
     */
    _loadPhase3() {

        
        // Inicializar todos los dropdowns
        this._initializeAllDropdowns();
        
        // Configurar handlers de WebSocket
        this._setupWebSocketHandlers();
        

    },

    /**
     * FASE 4: Listeners globales
     */
    _attachGlobalListeners() {

        
        // Detectar cuando la página está a punto de recargar
        window.addEventListener('beforeunload', () => {

        });
        
        // Detectar cambios de visibilidad (tab cambió de activo)
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {

            } else {

            }
        });
        

    },

    /**
     * Inicializar todos los dropdowns en la tabla
     */
    _initializeAllDropdowns() {
        const statusSelects = document.querySelectorAll('.estado-select');
        const areaSelects = document.querySelectorAll('.area-select');
        const diaSelects = document.querySelectorAll('.dia-entrega-select');
        

        
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

    },

    /**
     * Método público para obtener un módulo
     */
    getModule(moduleName) {
        if (!this.modules[moduleName]) {

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

        location.reload();
    },

    /**
     * Manejar errores de inicialización
     */
    _handleInitializationError(error) {

        
        // Mostrar notificación al usuario
        if (NotificationModule && NotificationModule.showError) {
            NotificationModule.showError(
                'Error al inicializar la tabla. Recargue la página.',
                5000
            );
        }
        
        // Log adicional para debugging

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

            return false;
        }
        

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

