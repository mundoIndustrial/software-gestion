/**
 * WizardBootstrap
 * 
 * Punto de inicialización del wizard con inyección de dependencias.
 * Aquí se ensamblan todos los componentes siguiendo la arquitectura.
 * 
 * PATRÓN: Factory + Dependency Injection
 * - Desacoplamiento total de componentes
 * - Fácil de testear (pasar mocks)
 * - Configuración centralizada
 */

class WizardBootstrap {
    /**
     * Crear e inicializar el wizard
     * @param {Object} config - Configuración del wizard
     * @returns {Object} - { lifecycle, stateMachine, eventBus }
     */
    static async create(config = {}) {
        // 1. Crear máquina de estados
        const stateMachine = new WizardStateMachine();

        // 2. Crear event bus
        const eventBus = new WizardEventBus();

        // 3. Configurar hooks y listeners
        const lifecycleConfig = {
            stateMachine,
            eventBus,
            domSelectors: config.domSelectors || {
                container: 'modal-asignar-colores-por-talla',
                required: [
                    '#wzd-btn-atras',
                    '#wzd-btn-siguiente',
                    '#btn-guardar-asignacion',
                    '#btn-cancelar-wizard'
                ]
            },
            hooks: {
                // Hook ejecutado ANTES de iniciar el wizard
                'pre-initialize': async () => {
                    await WizardBootstrap._preInitializeHooks();
                },

                // Hook ejecutado DESPUES de inicializar (listeners listos)
                'post-initialize': async () => {
                    if (config.onReady) {
                        await Promise.resolve(config.onReady());
                    }
                },

                // Hook para registrar listeners del DOM
                'registerListeners': [
                    {
                        selector: '#wzd-btn-siguiente',
                        event: 'click',
                        handler: (e) => {
                            eventBus.emit('button:siguiente:clicked');
                        }
                    },
                    {
                        selector: '#wzd-btn-atras',
                        event: 'click',
                        handler: (e) => {
                            eventBus.emit('button:atras:clicked');
                        }
                    },
                    {
                        selector: '#btn-guardar-asignacion',
                        event: 'click',
                        handler: (e) => {
                            // Mostrar spinner inmediatamente
                            const btnG = document.getElementById('btn-guardar-asignacion');
                            if (btnG && !btnG._htmlOriginal) {
                                btnG._htmlOriginal = btnG.innerHTML;
                                btnG.disabled = true;
                                btnG.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="margin-right: 0.5rem;"></span><span>Guardando...</span>';
                                btnG.style.opacity = '0.85';
                            }
                            eventBus.emit('button:guardar:clicked');
                        }
                    },
                    {
                        selector: '#btn-cancelar-wizard',
                        event: 'click',
                        handler: (e) => {
                            eventBus.emit('button:cancelar:clicked');
                        }
                    }
                ],

                // Hook para restaurar estado anterior del wizard
                'restoreState': async () => {
                    // El StateManager restauraría datos aquí si existen
                    if (window.StateManager && typeof window.StateManager.getPasoActual === 'function') {
                        const pasoActual = window.StateManager.getPasoActual();
                    }
                },

                // Hook ejecutado ANTES de cerrar
                'pre-close': async () => {
                },

                // Hook ejecutado DESPUES de cerrar
                'post-close': async () => {
                    if (config.onClosed) {
                        await Promise.resolve(config.onClosed());
                    }
                }
            }
        };

        // 4. Crear lifecycle manager
        const lifecycle = new WizardLifecycleManager(lifecycleConfig);

        // 5. Registrar listeners de eventos que el rest del código puede usar
        WizardBootstrap._setupEventListeners(eventBus, config);

        return { lifecycle, stateMachine, eventBus };
    }

    /**
     * Pre-initialize: Limpiar estado anterior, validar precondiciones
     */
    static async _preInitializeHooks() {
        // Limpiar banderas globales antiguas
        delete window.evitarInicializacionWizard;

        // Resetear el flujo interno del wizard para evitar bloqueos en reaperturas
        if (window.WizardManager && typeof window.WizardManager.resetearFlujo === 'function') {
            window.WizardManager.resetearFlujo();
        }

        // NO llamar a WizardManager.inicializarListeners() aquí
        // Los listeners se registran una sola vez desde los hooks de registerListeners
        // Llamar aquí causa listeners duplicados

        // Validar que la tela está seleccionada (verificar en StateManager o window)
        const telaDelStateManager = window.StateManager ? window.StateManager.getTelaSeleccionada() : null;
        const telasCreacion = window.telasCreacion || [];
        
        // Acepta si hay al menos una tela en StateManager O en window.telasCreacion
        const hayTela = telaDelStateManager || telasCreacion.length > 0;
        
        if (!hayTela) {
            throw new Error('No hay telas seleccionadas para asignar colores');
        }
    }

    /**
     * Configurar listeners de eventos para la integración con código existente
     * NOTA: Los eventos principales (siguiente, atras, guardar) son manejados por ColoresPorTalla.js
     * Aquí solo registramos lo que NO esté cubierto por ColoresPorTalla
     */
    static _setupEventListeners(eventBus, config) {
        // Los eventos button:siguiente:clicked y button:atras:clicked
        // ya son manejados por ColoresPorTalla._setupEventListeners()
        // NO duplicar aquí para evitar llamadas múltiples a pasoSiguiente/pasoAnterior
    }
}

// Exportar para uso global
if (typeof window !== 'undefined') {
    window.WizardBootstrap = WizardBootstrap;
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = WizardBootstrap;
}
