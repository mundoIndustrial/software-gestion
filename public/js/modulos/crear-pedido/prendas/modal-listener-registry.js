/**
 * ================================================
 * MODAL LISTENER REGISTRY - FASE 2
 * ================================================
 * 
 * Patrón para registrar y limpiar listeners sin duplicación
 * Reemplaza listeners duplicados que aparecen en múltiples aperturas
 * 
 * Incluye en HTML ANTES de otros archivos:
 * <script src="/public/js/modulos/crear-pedido/prendas/modal-listener-registry.js"></script>
 * 
 * @module ModalListenerRegistry
 * @version 1.0.0 (Fase 2 - Control de Listeners)
 */

const ModalListenerRegistry = (() => {
    // Storage privado: array de { element, event, handler }
    const listeners = [];
    
    // Logger interno
    const log = (message, context = {}) => {
        console.log(`[ModalListeners] ${message}`, {
            totalListeners: listeners.length,
            ...context
        });
    };

    return {
        /**
         * Registrar un listener (elemento, evento, handler)
         * 
         * Garantiza una única instancia del listener
         * Guarda referencia para desregistrar después
         * 
         * @param {HTMLElement} element - Elemento donde registrar
         * @param {string} event - Nombre del evento (ej: 'shown.bs.modal')
         * @param {function} handler - Función a ejecutar
         */
        register: (element, event, handler) => {
            // Guard: validaciones
            if (!element) {
                console.error('[ModalListeners] register() - element requerido');
                return;
            }
            
            if (!event || typeof event !== 'string') {
                console.error('[ModalListeners] register() - event debe ser string');
                return;
            }
            
            if (!handler || typeof handler !== 'function') {
                console.error('[ModalListeners] register() - handler debe ser función');
                return;
            }

            // Verificar que NO esté ya registrado (evitar duplicación)
            const yaRegistrado = listeners.some(l => 
                l.element === element && 
                l.event === event && 
                l.handler === handler
            );
            
            if (yaRegistrado) {
                log(` Listener ya registrado`, { event });
                return;
            }

            // Registrar en DOM
            element.addEventListener(event, handler);
            
            // Guardar referencia
            listeners.push({ element, event, handler });
            
            log(` Listener registrado`, { event });
        },
        
        /**
         * Desregistrar todos los listeners guardados
         * Llamar cuando el modal se cierra
         */
        unregisterAll: () => {
            if (listeners.length === 0) {
                log(' No hay listeners para limpiar');
                return;
            }

            listeners.forEach(({ element, event, handler }, index) => {
                try {
                    element.removeEventListener(event, handler);
                } catch (error) {
                    console.error(`[ModalListeners] Error removiendo listener ${index}:`, error);
                }
            });

            listeners.length = 0;  // Limpiar array
            log(` Todos los listeners desregistrados`);
        },
        
        /**
         * Desregistrar un listener específico
         * 
         * @param {HTMLElement} element 
         * @param {string} event 
         * @param {function} handler 
         */
        unregister: (element, event, handler) => {
            const index = listeners.findIndex(l => 
                l.element === element && 
                l.event === event && 
                l.handler === handler
            );

            if (index === -1) {
                log(` Listener no encontrado`, { event });
                return;
            }

            try {
                element.removeEventListener(event, handler);
                listeners.splice(index, 1);
                log(` Listener desregistrado`, { event });
            } catch (error) {
                console.error('[ModalListeners] Error desregistrando:', error);
            }
        },
        
        /**
         * Obtener cantidad de listeners registrados
         * @returns {number}
         */
        count: () => {
            return listeners.length;
        },
        
        /**
         * Obtener array de listeners (para debugging)
         * @returns {array}
         */
        getListeners: () => {
            return listeners.map(({ element, event, handler }) => ({
                element: element.id || element.className || element.tagName,
                event,
                handler: handler.name || 'anonymous'
            }));
        },
        
        /**
         * Obtener estado actual (para debugging)
         * @returns {object}
         */
        getStatus: () => {
            return {
                totalListeners: listeners.length,
                details: ModalListenerRegistry.getListeners(),
                timestamp: new Date().toISOString()
            };
        }
    };
})();

// Auto-inicialización
console.log('[ModalListeners]  Inicializado y listo');
