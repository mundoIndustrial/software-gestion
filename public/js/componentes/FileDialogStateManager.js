/**
 * ================================================
 * FILE DIALOG STATE MANAGER
 * ================================================
 * 
 * Controla el ciclo de vida completo del file dialog
 * Previene reaperturas automáticas mediante state tracking
 * 
 * ESTADOS:
 * - CLOSED: Listo para abrir
 * - OPENING: Diálogo abriéndose  
 * - PROCESSING: Procesando archivo seleccionado
 * - HANDLING_CHANGE: En medio del change event
 * - LOCKED: Bloqueado temporalmente para prevenir reapertura
 * 
 * @module FileDialogStateManager
 * @version 1.0.0
 */

class FileDialogStateManager {
    /**
     * @param {string} inputId - ID del input file
     */
    constructor(inputId) {
        this.inputId = inputId;
        this.input = document.getElementById(inputId);
        
        // Estados permitidos
        this.STATES = {
            CLOSED: 'CLOSED',                   // Listo para usar
            OPENING: 'OPENING',                 // Diálogo abriéndose
            PROCESSING: 'PROCESSING',           // Procesando archivo
            HANDLING_CHANGE: 'HANDLING_CHANGE', // En cambio
            LOCKED: 'LOCKED'                    // Bloqueado temporalmente
        };
        
        // Estado actual
        this.currentState = this.STATES.CLOSED;
        
        // Timestamp del último cambio
        this.lastStateChange = Date.now();
        
        // Timeout para bloqueo temporal
        this.lockTimeoutId = null;
        
        if (!this.input) {
            console.error(`[FileDialogStateManager] Input ${inputId} no encontrado`);
            throw new Error(`Input ${inputId} not found`);
        }
        
        // Storear el manager en el input para acceso rápido
        this.input._fileDialogStateManager = this;
        
        console.log(`[FileDialogStateManager]  Inicializado para ${inputId}`);
    }
    
    /**
     * Verificar si se puede abrir el diálogo
     * @returns {boolean}
     */
    canOpen() {
        const canOpen = this.currentState === this.STATES.CLOSED;
        return canOpen;
    }
    
    /**
     * Marcar que el diálogo se está abriendo
     * @returns {boolean} true si se puede abrir, false en caso contrario
     */
    markOpening() {
        if (!this.canOpen()) {
            console.warn(
                `[FileDialogStateManager:${this.inputId}] ` +
                `No se puede abrir - estado: ${this.currentState}`
            );
            return false;
        }
        
        this.setState(this.STATES.OPENING);
        return true;
    }
    
    /**
     * Marcar que estamos maneando el change event
     */
    markHandlingChange() {
        this.setState(this.STATES.HANDLING_CHANGE);
    }
    
    /**
     * Marcar que estamos procesando la imagen
     */
    markProcessing() {
        this.setState(this.STATES.PROCESSING);
    }
    
    /**
     * Marcar como cerrado (listo para siguiente apertura)
     */
    markClosed() {
        this.setState(this.STATES.CLOSED);
    }
    
    /**
     * Bloquear temporalmente para prevenir reaperturas automáticas
     * 
     * Esto es crítico porque:
     * - Después del change event, el navegador puede auto-interactuar con el input
     * - Los handlers se están actualizando en el DOM
     * - Un click accidental podría re-abrir el dialogo
     * 
     * @param {number} durationMs - Duración del bloqueo en ms (default: 750)
     */
    lockTemporarily(durationMs = 750) {
        // Limpiar timeout anterior si existe
        if (this.lockTimeoutId) {
            clearTimeout(this.lockTimeoutId);
        }
        
        this.setState(this.STATES.LOCKED);
        
        this.lockTimeoutId = setTimeout(() => {
            if (this.currentState === this.STATES.LOCKED) {
                this.markClosed();
                console.log(
                    `[FileDialogStateManager:${this.inputId}] ` +
                    ` Bloqueo temporal removido, listo para siguiente apertura`
                );
            }
        }, durationMs);
    }
    
    /**
     * Cambiar estado interno
     * @private
     */
    setState(newState) {
        const oldState = this.currentState;
        this.currentState = newState;
        this.lastStateChange = Date.now();
        
        console.log(
            `[FileDialogStateManager:${this.inputId}] ` +
            `${oldState} → ${newState}`
        );
    }
    
    /**
     * Obtener estado actual
     * @returns {string}
     */
    getState() {
        return this.currentState;
    }
}

// ════════════════════════════════════════════════════════════════
// INICIALIZACIÓN GLOBAL
// ════════════════════════════════════════════════════════════════

// Contenedor global para los managers de cada input
globalThis._fileDialogManagers = globalThis._fileDialogManagers || {};

/**
 * Inicializar todos los FileDialogStateManagers para los inputs de proceso
 * Se llama cuando el documento está listo
 */
function inicializarFileDialogStateManagers() {
    console.log('[inicializarFileDialogStateManagers]  Inicializando...');
    
    for (let i = 1; i <= 3; i++) {
        const inputId = `proceso-foto-input-${i}`;
        
        try {
            // Crear manager solo si no existe ya
            if (!globalThis._fileDialogManagers[inputId]) {
                globalThis._fileDialogManagers[inputId] = new FileDialogStateManager(inputId);
            }
        } catch (e) {
            console.error(
                `[inicializarFileDialogStateManagers] ` +
                `Error para ${inputId}:`, 
                e
            );
        }
    }
    
    console.log('[inicializarFileDialogStateManagers]  Todos los managers inicializados');
}

// Ejecutar cuando el documento está listo
document.addEventListener('DOMContentLoaded', inicializarFileDialogStateManagers);

// Exportar para uso en otros módulos si es necesario
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FileDialogStateManager;
}
