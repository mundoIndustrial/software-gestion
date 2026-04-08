/**
 * WizardStateMachine
 * 
 * Implementa una máquina de estados formal para el wizard.
 * Responsabilidades ÚNICAS:
 * - Mantener estado actual
 * - Validar transiciones posibles
 * - Ejecutar hooks pre/post transición
 * - Prevenir transiciones inválidas (fail-fast)
 * 
 * Características:
 * - Encapsulación total del estado
 * - Sin efectos secundarios (puro)
 * - Transiciones atómicas
 * - Historial de cambios para debugging
 */

class WizardStateMachine {
    // Estados posibles del wizard
    static STATES = Object.freeze({
        IDLE: 'IDLE',                    // No inicializado
        INITIALIZING: 'INITIALIZING',    // Preparando DOM y listeners
        READY: 'READY',                  // Esperando interacción del usuario
        USER_INPUT: 'USER_INPUT',        // Usuario en un paso del wizard
        VALIDATING: 'VALIDATING',        // Validando antes de avanzar
        PRE_SAVE: 'PRE_SAVE',            // Preparando para guardar
        SAVING: 'SAVING',                // Guardando en servidor
        POST_SAVE: 'POST_SAVE',          // Procesando respuesta exitosa
        ERROR_SAVE: 'ERROR_SAVE',        // Manejo de error en guardado
        CLOSING: 'CLOSING',              // Limpiando y cerrando
        DISPOSED: 'DISPOSED'             // Destruido, no se puede reutilizar
    });

    // Transiciones válidas: estado_actual -> [estados_permitidos]
    static TRANSITIONS = Object.freeze({
        [this.STATES.IDLE]: [
            this.STATES.INITIALIZING,
            this.STATES.DISPOSED
        ],
        [this.STATES.INITIALIZING]: [
            this.STATES.READY,
            this.STATES.IDLE  // Rollback en error
        ],
        [this.STATES.READY]: [
            this.STATES.USER_INPUT,
            this.STATES.VALIDATING,
            this.STATES.IDLE,        // Cancel
            this.STATES.DISPOSED
        ],
        [this.STATES.USER_INPUT]: [
            this.STATES.READY,
            this.STATES.VALIDATING,
            this.STATES.PRE_SAVE,
            this.STATES.IDLE,        // Cancel
            this.STATES.DISPOSED
        ],
        [this.STATES.VALIDATING]: [
            this.STATES.READY,       // Fail
            this.STATES.USER_INPUT   // Pass
        ],
        [this.STATES.PRE_SAVE]: [
            this.STATES.SAVING,
            this.STATES.USER_INPUT,  // Validación fallida
            this.STATES.IDLE,        // Cancel
            this.STATES.DISPOSED
        ],
        [this.STATES.SAVING]: [
            this.STATES.POST_SAVE,
            this.STATES.ERROR_SAVE
        ],
        [this.STATES.POST_SAVE]: [
            this.STATES.CLOSING,
            this.STATES.IDLE         // Si cierre manual
        ],
        [this.STATES.ERROR_SAVE]: [
            this.STATES.USER_INPUT,  // Reintentar
            this.STATES.IDLE,        // Cancelar
            this.STATES.DISPOSED
        ],
        [this.STATES.CLOSING]: [
            this.STATES.IDLE,
            this.STATES.DISPOSED
        ],
        [this.STATES.DISPOSED]: []   // Terminal, sin salidas
    });

    constructor(initialState = WizardStateMachine.STATES.IDLE) {
        this.currentState = initialState;
        this.previousState = null;
        this.history = [{ state: initialState, timestamp: Date.now() }];
        this.listeners = new Map();
        this.metadata = {};
    }

    /**
     * Transición: Validar y cambiar estado
     * @param {string} nextState - Estado destino
     * @param {Object} metadata - Datos asociados a la transición (opcional)
     * @returns {boolean} - true si la transición fue exitosa
     * @throws {Error} - Si la transición es inválida
     */
    transition(nextState, metadata = {}) {
        // Validar que el estado existe
        if (!Object.values(WizardStateMachine.STATES).includes(nextState)) {
            throw new Error(`Estado inválido: ${nextState}`);
        }

        // Validar que la transición es permitida
        const allowedStates = WizardStateMachine.TRANSITIONS[this.currentState];
        if (!allowedStates || !allowedStates.includes(nextState)) {
            throw new Error(
                `Transición inválida: ${this.currentState} -> ${nextState}. ` +
                `Estados permitidos: ${allowedStates.join(', ')}`
            );
        }

        // Ejecutar hooks pre-transición
        this._executeHooks('pre-transition', {
            fromState: this.currentState,
            toState: nextState,
            metadata
        });

        // Actualizar estado
        this.previousState = this.currentState;
        this.currentState = nextState;
        this.metadata = { ...this.metadata, ...metadata };

        // Registrar en historial (para debugging y auditoria)
        this.history.push({
            state: nextState,
            timestamp: Date.now(),
            fromState: this.previousState,
            metadata
        });

        // Ejecutar hooks post-transición
        this._executeHooks('post-transition', {
            fromState: this.previousState,
            toState: nextState,
            metadata
        });

        // Notificar listeners
        this._notifyListeners('state-changed', {
            oldState: this.previousState,
            newState: nextState,
            metadata
        });

        return true;
    }

    /**
     * Obtener estado actual (getter puro, sin efectos)
     */
    getState() {
        return this.currentState;
    }

    /**
     * Verificar si se puede hacer una transición
     */
    canTransition(nextState) {
        const allowedStates = WizardStateMachine.TRANSITIONS[this.currentState];
        return allowedStates && allowedStates.includes(nextState);
    }

    /**
     * Verificar si el wizard puede recibir entrada del usuario
     */
    isInteractable() {
        return [
            WizardStateMachine.STATES.READY,
            WizardStateMachine.STATES.USER_INPUT,
            WizardStateMachine.STATES.VALIDATING,
            WizardStateMachine.STATES.ERROR_SAVE
        ].includes(this.currentState);
    }

    /**
     * Verificar si el wizard está en operación (no idle ni disposed)
     */
    isActive() {
        return ![
            WizardStateMachine.STATES.IDLE,
            WizardStateMachine.STATES.DISPOSED
        ].includes(this.currentState);
    }

    /**
     * Registrar hook a ejecutar en transiciones
     */
    onTransition(fromState, toState, callback) {
        const key = `${fromState}->${toState}`;
        if (!this.listeners.has(key)) {
            this.listeners.set(key, []);
        }
        this.listeners.get(key).push(callback);
    }

    /**
     * Suscribirse a eventos de cambio de estado
     */
    on(event, callback) {
        if (!this.listeners.has(event)) {
            this.listeners.set(event, []);
        }
        this.listeners.get(event).push(callback);
    }

    /**
     * Obtener historial (para debugging)
     */
    getHistory() {
        return [...this.history];
    }

    /**
     * Limpiar listeners (importante para evitar memory leaks)
     */
    destroy() {
        this.listeners.clear();
        this.history = [];
        this.metadata = {};
        this.transition(WizardStateMachine.STATES.DISPOSED);
    }

    // ========== PRIVADOS ==========

    _executeHooks(type, data) {
        const key = `${data.fromState}->${data.toState}`;
        const hooks = this.listeners.get(key) || [];
        hooks.forEach(hook => {
            try {
                hook(data);
            } catch (error) {
                console.error(`Error en hook ${key}:`, error);
            }
        });
    }

    _notifyListeners(event, data) {
        const handlers = this.listeners.get(event) || [];
        handlers.forEach(handler => {
            try {
                handler(data);
            } catch (error) {
                console.error(`Error en listener ${event}:`, error);
            }
        });
    }
}

// Exportar para uso modular
if (typeof module !== 'undefined' && module.exports) {
    module.exports = WizardStateMachine;
}
