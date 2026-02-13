/**
 * ================================================
 * MINI FSM PARA MODAL - PRODUCCIÓN
 * ================================================
 * 
 * MÁXIMA SIMPLICIDAD, MÁXIMO CONTROL
 * 
 * Responsabilidad única: Coordinar ciclo de vida del modal
 * - Evitar dobles aperturas
 * - Sincronizar eventos
 * - No interferir con código existente
 * 
 * @class ModalMiniFSM
 * @version 1.0
 * @production true
 */

(function() {
    'use strict';

    class ModalMiniFSM {
        constructor(modalId = 'modal-agregar-prenda-nueva') {
            // Estado actual
            this.estado = 'CLOSED';
            this.modalId = modalId;
            
            // Metadata
            this.timestamps = {
                ultimoChange: Date.now(),
                ultimaApertura: null,
                ultimaCierre: null
            };
            
            // Listeners para observadores
            this.stateChangeListeners = [];
            
            // Validaciones
            this._validarModalExiste();
            
            console.log(`[ModalFSM] Inicializado para modal: ${modalId}`);
        }

        /**
         * Validar que el modal existe (o pueda existir)
         * @private
         */
        _validarModalExiste() {
            const modal = document.getElementById(this.modalId);
            if (!modal) {
                console.warn(`[ModalFSM] ⚠️ Modal no encontrado en DOM al iniciar: ${this.modalId}`);
                // No es error fatal - el modal se puede cargar después
            }
        }

        /**
         * Cambiar estado con validaciones mínimas
         * 
         * Transiciones válidas:
         * CLOSED  → OPENING
         * OPENING → OPEN | CLOSED (emergencia)
         * OPEN    → CLOSING
         * CLOSING → CLOSED
         * 
         * @param {string} nuevoEstado
         * @param {object} contexto - Información adicional
         * @returns {boolean} True si fue exitoso
         */
        cambiarEstado(nuevoEstado, contexto = {}) {
            // Guardar estado anterior
            const estadoAnterior = this.estado;

            // Mapeo de transiciones válidas
            const transicionesValidas = {
                'CLOSED':  ['OPENING'],
                'OPENING': ['OPEN', 'CLOSED'],  // CLOSED es emergencia
                'OPEN':    ['CLOSING'],
                'CLOSING': ['CLOSED']
            };

            // Guard: transición inválida
            if (!transicionesValidas[estadoAnterior]) {
                console.error(`[ModalFSM] Estado inválido en tabla: ${estadoAnterior}`);
                return false;
            }

            if (!transicionesValidas[estadoAnterior].includes(nuevoEstado)) {
                console.warn(
                    `[ModalFSM] ❌ Transición rechazada: ${estadoAnterior} → ${nuevoEstado}`
                );
                return false;
            }

            // Actualizar estado
            this.estado = nuevoEstado;
            this.timestamps.ultimoChange = Date.now();

            // Guardar timestamps de apertura/cierre
            if (nuevoEstado === 'OPENING') {
                this.timestamps.ultimaApertura = Date.now();
            } else if (nuevoEstado === 'CLOSED') {
                this.timestamps.ultimaCierre = Date.now();
            }

            // Log de transición
            console.log(
                `[ModalFSM] ✅ Transición: ${estadoAnterior} → ${nuevoEstado}`,
                contexto
            );

            // Notificar listeners
            this._notifyListeners(nuevoEstado, estadoAnterior, contexto);

            return true;
        }

        /**
         * Puede el modal abrirse ahora?
         * Retorna true si estado es CLOSED
         * Retorna false si ya está OPENING, OPEN o CLOSING
         * 
         * @returns {boolean}
         */
        puedeAbrir() {
            const puede = this.estado === 'CLOSED';
            if (!puede) {
                console.warn(
                    `[ModalFSM] Modal no puede abrir ahora (estado: ${this.estado})`
                );
            }
            return puede;
        }

        /**
         * Obtener estado actual
         * @returns {string}
         */
        obtenerEstado() {
            return this.estado;
        }

        /**
         * Obtener información de debugging
         * @returns {object}
         */
        obtenerDebug() {
            const ahora = Date.now();
            return {
                estado: this.estado,
                modalId: this.modalId,
                timestamps: {
                    ultimoChange: new Date(this.timestamps.ultimoChange).toLocaleTimeString(),
                    ultimaApertura: this.timestamps.ultimaApertura ? new Date(this.timestamps.ultimaApertura).toLocaleTimeString() : 'nunca',
                    ultimaCierre: this.timestamps.ultimaCierre ? new Date(this.timestamps.ultimaCierre).toLocaleTimeString() : 'nunca'
                },
                listeners: this.stateChangeListeners.length
            };
        }

        /**
         * Registrar listener para cambios de estado
         * 
         * @param {function} callback - (nuevoEstado, estadoAnterior, contexto) => { }
         * @returns {function} Función para desuscribirse
         * 
         * @example
         * const unsubscribe = fsm.onStateChange((nuevo, anterior) => {
         *     console.log(`Cambio: ${anterior} → ${nuevo}`);
         * });
         * // Luego:
         * unsubscribe();  // Detener escuchar
         */
        onStateChange(callback) {
            if (typeof callback !== 'function') {
                throw new Error('[ModalFSM] Callback debe ser función');
            }

            this.stateChangeListeners.push(callback);

            // Retornar función para desuscribirse
            return () => {
                const idx = this.stateChangeListeners.indexOf(callback);
                if (idx > -1) {
                    this.stateChangeListeners.splice(idx, 1);
                    console.log('[ModalFSM] Listener desuscrito');
                }
            };
        }

        /**
         * Notificar a todos los listeners registrados
         * @private
         */
        _notifyListeners(nuevoEstado, estadoAnterior, contexto) {
            this.stateChangeListeners.forEach((callback, index) => {
                try {
                    callback(nuevoEstado, estadoAnterior, contexto);
                } catch (error) {
                    console.error(`[ModalFSM] Error en listener #${index}:`, error);
                }
            });
        }

        /**
         * Resetear a estado CLOSED (emergencia)
         */
        resetear() {
            this.cambiarEstado('CLOSED', { razon: 'reset manual' });
        }
    }

    // ================================================
    // SINGLETON GLOBAL (inyección explícita)
    // ================================================

    if (!window.__MODAL_FSM__) {
        window.__MODAL_FSM__ = new ModalMiniFSM('modal-agregar-prenda-nueva');
        console.log('[ModalFSM] ✅ Singleton creado en window.__MODAL_FSM__');
    } else {
        console.warn('[ModalFSM] ⚠️ Ya existe window.__MODAL_FSM__, ignorando creación duplicada');
    }

    // Mantenimiento de historial de cambios (debug)
    window.__MODAL_FSM__._historialCambios = [];
    const fsm = window.__MODAL_FSM__;
    fsm.onStateChange((nuevo, anterior) => {
        fsm._historialCambios.push({
            desde: anterior,
            hasta: nuevo,
            timestamp: Date.now()
        });
        
        // Guardar solo los últimos 50 cambios
        if (fsm._historialCambios.length > 50) {
            fsm._historialCambios.shift();
        }
    });

    // Exportar para ES6 modules si es necesario
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = ModalMiniFSM;
    }

})();
