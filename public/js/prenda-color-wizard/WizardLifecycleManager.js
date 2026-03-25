/**
 * WizardLifecycleManager
 * 
 * Orquestador central del ciclo de vida del wizard.
 * 
 * Responsabilidades:
 * - Coordinar máquina de estados y event bus
 * - Gestionar listeners del DOM (registro y limpieza)
 * - Garantizar transiciones atómicas y seguras
 * - Manejar inicialización y limpieza de recursos
 * - Proveer API pública limpia
 * 
 * PRINCIPIOS SOLID:
 * - Single Responsibility: coordinación solamente
 * - Open/Closed: extensible vía eventos y hooks
 * - Liskov Substitution: interfaz consistente
 * - Interface Segregation: métodos específicos
 * - Dependency Inversion: inyección de dependencias
 */

class WizardLifecycleManager {
    constructor(config = {}) {
        // Validar configuración
        if (!config.stateMachine || !config.eventBus) {
            throw new Error('WizardLifecycleManager requiere: stateMachine, eventBus');
        }

        this.stateMachine = config.stateMachine;
        this.eventBus = config.eventBus;
        this.domSelectors = config.domSelectors || {};
        this.validators = config.validators || [];
        this.hooks = config.hooks || {};

        // Registro de listeners registrados (para limpieza garantizada)
        this.domListeners = new Map();
        this.unsubscribers = [];

        // Estado interno de inicialización
        this.isInitialized = false;
        this.initializationError = null;

        this._setupStateHooks();
    }

    /**
     * PUNTO DE ENTRADA: Mostrar y activar el wizard
     */
    async show() {
        console.log('[WizardLifecycle] Iniciando show()');

        try {
            // Validar estado precondición
            if (!this.stateMachine.canTransition(WizardStateMachine.STATES.INITIALIZING)) {
                throw new Error(
                    `No se puede mostrar wizard: estado actual es ${this.stateMachine.getState()}`
                );
            }

            // Transicionar a INITIALIZING
            this.stateMachine.transition(WizardStateMachine.STATES.INITIALIZING);

            // Ejecutar hooks pre-inicialización
            await this._executeHook('pre-initialize');

            // Fase 1: Preparar DOM
            await this._prepareDom();

            // Fase 2: Registrar listeners
            await this._registerDomListeners();

            // Fase 3: Restaurar estado anterior si existe
            await this._restoreState();

            // Transicionar a READY
            this.stateMachine.transition(WizardStateMachine.STATES.READY);

            // Ejecutar hooks post-inicialización
            await this._executeHook('post-initialize');

            this.isInitialized = true;
            this.eventBus.emit('wizard:ready');
            console.log('[WizardLifecycle] Wizard mostrado exitosamente');

        } catch (error) {
            console.error('[WizardLifecycle] Error en show():', error);
            this.initializationError = error;

            // Rollback: volver a IDLE y limpiar
            try {
                this.stateMachine.transition(WizardStateMachine.STATES.IDLE);
                await this.cleanup();
            } catch (cleanupError) {
                console.error('[WizardLifecycle] Error en rollback:', cleanupError);
            }

            this.eventBus.emit('wizard:error', { phase: 'initialization', error });
            throw error;
        }
    }

    /**
     * PUNTO DE ENTRADA: Cerrar wizard de forma limpia
     */
    async close() {
        console.log('[WizardLifecycle] Iniciando close()');

        try {
            if (this.stateMachine.getState() === WizardStateMachine.STATES.DISPOSED) {
                console.warn('[WizardLifecycle] Wizard ya está disposed, ignorando close()');
                return;
            }

            // Transicionar a CLOSING
            if (this.stateMachine.canTransition(WizardStateMachine.STATES.CLOSING)) {
                this.stateMachine.transition(WizardStateMachine.STATES.CLOSING);
            }

            // Ejecutar hooks pre-cierre
            await this._executeHook('pre-close');

            // Limpiar DOM listeners para evitar acumulación en reaperturas
            await this.cleanup();

            // Limpiar DOM
            await this._hideDom();

            // Transicionar a IDLE
            this.stateMachine.transition(WizardStateMachine.STATES.IDLE);

            // Ejecutar hooks post-cierre
            await this._executeHook('post-close');

            this.isInitialized = false;
            this.eventBus.emit('wizard:closed');
            console.log('[WizardLifecycle] Wizard cerrado exitosamente');

        } catch (error) {
            console.error('[WizardLifecycle] Error en close():', error);
            this.eventBus.emit('wizard:error', { phase: 'close', error });
            throw error;
        }
    }

    /**
     * Limpiar y liberar todos los recursos (final)
     * Llamar cuando el wizard ya no se va a usar más en la página
     */
    async dispose() {
        console.log('[WizardLifecycle] Iniciando dispose()');

        try {
            // Cerrar si está abierto
            if (this.stateMachine.isActive()) {
                await this.close();
            }

            // Desregistrar TODOS los listeners
            await this.cleanup();

            // Destruir máquina de estados
            this.stateMachine.destroy();

            // Limpiar event bus
            this.eventBus.clear();

            // Transicionar a DISPOSED (estado final)
            this.stateMachine.transition(WizardStateMachine.STATES.DISPOSED);

            console.log('[WizardLifecycle] Wizard disposed completamente');

        } catch (error) {
            console.error('[WizardLifecycle] Error en dispose():', error);
        }
    }

    /**
     * Registrar callback a ejecutarse en transiciones
     */
    on(event, callback) {
        this.eventBus.subscribe(event, callback);
    }

    /**
     * Obtener estado actual
     */
    getState() {
        return this.stateMachine.getState();
    }

    /**
     * Obtener historial de cambios (debugging)
     */
    getHistory() {
        return {
            states: this.stateMachine.getHistory(),
            events: this.eventBus.getEventHistory()
        };
    }

    // ========== PRIVADOS: CICLO DE INICIALIZACIÓN ==========

    async _prepareDom() {
        console.log('[WizardLifecycle] Preparando DOM');
        
        // Mostrar contenedores principales
        const container = document.getElementById(this.domSelectors.container || 'vista-asignacion-colores');
        if (container) {
            container.style.display = 'block';
        }

        // Esta es la oportunidad para validar que todos los elementos existan
        this._validateDomElements();
    }

    async _registerDomListeners() {
        console.log('[WizardLifecycle] Registrando listeners del DOM');

        // Registrar listeners con seguimiento para limpieza
        const listenersConfig = this.hooks.registerListeners || [];

        for (const config of listenersConfig) {
            const { selector, event, handler } = config;
            const element = document.querySelector(selector);

            if (!element) {
                console.warn(`[WizardLifecycle] Elemento no encontrado: ${selector}`);
                continue;
            }

            // Crear wrapper que registra la desuscripción
            const wrappedHandler = (e) => {
                try {
                    handler(e);
                } catch (error) {
                    console.error(`[WizardLifecycle] Error en listener ${selector}:`, error);
                    this.eventBus.emit('listener:error', { selector, error });
                }
            };

            // Registrar listener
            element.addEventListener(event, wrappedHandler);

            // Guardar para limpieza posterior
            if (!this.domListeners.has(selector)) {
                this.domListeners.set(selector, []);
            }
            this.domListeners.get(selector).push({
                event,
                handler: wrappedHandler,
                element
            });
        }
    }

    async _restoreState() {
        console.log('[WizardLifecycle] Restaurando estado anterior');

        // Hook para restaurar datos guardados
        if (this.hooks.restoreState) {
            await this.hooks.restoreState();
        }

        this.eventBus.emit('state:restored');
    }

    async _hideDom() {
        console.log('[WizardLifecycle] Ocultando DOM');

        const container = document.getElementById(this.domSelectors.container || 'vista-asignacion-colores');
        if (container) {
            container.style.display = 'none';
        }
    }

    /**
     * Limpiar listeners sin destruir completamente
     */
    async cleanup() {
        console.log('[WizardLifecycle] Limpiando listeners del DOM');

        // Desregistrar todos los listeners del DOM
        for (const [selector, listeners] of this.domListeners) {
            listeners.forEach(({ element, event, handler }) => {
                element.removeEventListener(event, handler);
            });
        }
        this.domListeners.clear();

        // Desuscribirse de eventos internos
        this.unsubscribers.forEach(unsub => {
            try {
                unsub();
            } catch (error) {
                console.warn('[WizardLifecycle] Error en unsubscriber:', error);
            }
        });
        this.unsubscribers = [];

        this.eventBus.emit('cleanup:complete');
    }

    // ========== PRIVADOS: HOOKS Y UTILIDADES ==========

    _setupStateHooks() {
        // Permitir transiciones solo en estados válidos
        this.stateMachine.on('state-changed', ({ oldState, newState }) => {
            console.log(`[WizardLifecycle] Transición: ${oldState} → ${newState}`);
            this.eventBus.emit('state:changed', { oldState, newState });
        });
    }

    async _executeHook(hookName) {
        if (this.hooks[hookName] && typeof this.hooks[hookName] === 'function') {
            try {
                await Promise.resolve(this.hooks[hookName]());
            } catch (error) {
                console.error(`[WizardLifecycle] Error en hook ${hookName}:`, error);
                throw error;
            }
        }
    }

    _validateDomElements() {
        const criticalElements = this.domSelectors.required || [];
        const missing = [];

        criticalElements.forEach(selector => {
            if (!document.querySelector(selector)) {
                missing.push(selector);
            }
        });

        if (missing.length > 0) {
            throw new Error(`Elementos DOM requeridos no encontrados: ${missing.join(', ')}`);
        }
    }
}

// Exportar para uso modular
if (typeof module !== 'undefined' && module.exports) {
    module.exports = WizardLifecycleManager;
}
