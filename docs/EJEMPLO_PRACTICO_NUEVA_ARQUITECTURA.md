/**
 * EJEMPLO PRÁCTICO: Uso de la Nueva Arquitectura
 * 
 * Este archivo demuestra cómo reemplazar la lógica actual de ColoresPorTalla.js
 * con la arquitectura limpia propuesta.
 * 
 * COMPARACIÓN:
 * - Lado izquierdo: Código antiguo (problemático)
 * - Lado derecho: Código nuevo (limpio)
 */

// ============================================================
// EJEMPLO 1: Inicializar el Wizard
// ============================================================

/* CÓDIGO ANTIGUO - Problemático
function toggleVistaAsignacion() {
    // ... 50 líneas de lógica mezcladA ...
    const vistaTablaTelas = document.getElementById('vista-tabla-telas');
    const vistaAsignacion = document.getElementById('vista-asignacion-colores');
    
    if (vistaAsignacion.style.display === 'block') {
        // Closing
        vistaTablaTelas.style.display = 'block';
        vistaAsignacion.style.display = 'none';
        
        // ¿Reset? ¿No reset?
        if (window.WizardManager && !window.evitarInicializacionWizard) {
            window.WizardManager.resetWizard();
        } else if (window.evitarInicializacionWizard) {
            // Weird branch para evitar reinit
            // ¿Por qué existe este flag?
            // ¿Quién lo setea?
            // ¿Quién lo limpia?
        }
    } else {
        // Opening
        vistaAsignacion.style.display = 'block';
        // ... más lógica confusa ...
    }
}
*/

/* CÓDIGO NUEVO - Limpio
class ColoresPorTallaNew {
    constructor() {
        this.wizardInstance = null;
    }

    async initialize() {
        // Crear la instancia DEL wizard una sola vez
        this.wizardInstance = await WizardBootstrap.create({
            onReady: () => this._handleWizardReady(),
            onClosed: () => this._handleWizardClosed()
        });

        // Registrar escuchador del botón principal
        document.getElementById('btn-asignar-colores-tallas')
            .addEventListener('click', () => this.toggleVistaAsignacion());
    }

    async toggleVistaAsignacion() {
        try {
            const currentState = this.wizardInstance.lifecycle.getState();
            
            if (currentState === 'IDLE') {
                // Mostrar wizard
                await this.wizardInstance.lifecycle.show();
                this._updateUI_ShowWizard();
            } else {
                // Cerrar wizard (pero no destruir)
                await this.wizardInstance.lifecycle.close();
                this._updateUI_HideWizard();
            }
        } catch (error) {
            console.error('Error toggling wizard:', error);
            // El state machine garantiza rollback automático
        }
    }

    _handleWizardReady() {
        console.log('Wizard está listo para interactuar');
        // UI feedback: mostrar mensaje "preparado"
    }

    _handleWizardClosed() {
        console.log('Wizard fue cerrado');
        // UI feedback: mostrar tabla de telas de nuevo
    }

    async _updateUI_ShowWizard() {
        document.getElementById('vista-tabla-telas').style.display = 'none';
        document.getElementById('vista-asignacion-colores').style.display = 'block';
        document.getElementById('btn-asignar-colores-tallas').innerHTML = 
            '<span class="material-symbols-rounded">arrow_back</span>Volver a Telas';
    }

    async _updateUI_HideWizard() {
        document.getElementById('vista-tabla-telas').style.display = 'block';
        document.getElementById('vista-asignacion-colores').style.display = 'none';
        document.getElementById('btn-asignar-colores-tallas').innerHTML = 
            '<span class="material-symbols-rounded">palette</span>Asignar Colores';
    }
}
*/

// ============================================================
// EJEMPLO 2: Manejar Clicks de Botones
// ============================================================

/* CÓDIGO ANTIGUO - Acoplado
function configurarEventosGlobales() {
    const btnSiguiente = document.getElementById('wzd-btn-siguiente');
    const btnAtras = document.getElementById('wzd-btn-atras');
    const btnGuardar = document.getElementById('btn-guardar-asignacion');
    
    // Cada click llama una función diferente
    // No hay forma centralizada de manejar esto
    // Los listeners se repiten en múltiples funciones
    // Limpieza: tenemos que acordarnos de hacerla
    
    btnSiguiente?.addEventListener('click', () => {
        if (!window.WizardManager) return;
        // Llamada directa: acoplado a WizardManager
        window.WizardManager.irPaso(window.StateManager.getPasoActual() + 1);
    });
    
    btnAtras?.addEventListener('click', () => {
        if (!window.WizardManager) return;
        // Llamada directa: acoplado a WizardManager
        window.WizardManager.pasoAnterior();
    });
    
    btnGuardar?.addEventListener('click', async () => {
        // Flag para evitar doble-click (parche)
        if (btnGuardar.dataset.guardando === 'true') return;
        btnGuardar.dataset.guardando = 'true';
        
        // Lógica de guardado inline
        try {
            await wizardGuardarAsignacion();
            btnGuardar.dataset.guardando = 'false';
        } catch (error) {
            btnGuardar.dataset.guardando = 'false';
        }
    });
}
*/

/* CÓDIGO NUEVO - Desacoplado
const { eventBus } = wizardInstance;

// Escuchador 1: manejar "siguiente"
eventBus.subscribe('button:siguiente:clicked', async () => {
    try {
        await window.WizardManager.irPaso(
            window.StateManager.getPasoActual() + 1
        );
    } catch (error) {
        eventBus.emit('wizard:error', { action: 'siguiente', error });
    }
});

// Escuchador 2: manejar "atrás"  
eventBus.subscribe('button:atras:clicked', async () => {
    try {
        await window.WizardManager.pasoAnterior();
    } catch (error) {
        eventBus.emit('wizard:error', { action: 'atras', error });
    }
});

// Escuchador 3: manejar "guardar"
eventBus.subscribe('button:guardar:clicked', async () => {
    try {
        // El event bus previene múltiples clicks automáticamente
        // (solo emite si no está en estado SAVING)
        await wizardGuardarAsignacion();
        eventBus.emit('wizard:saved-success');
    } catch (error) {
        eventBus.emit('wizard:saving-error', { error });
    }
});

// Escuchador 4: reaccionar a error de guardado
eventBus.subscribe('wizard:saving-error', ({ error }) => {
    console.error('Error al guardar:', error);
    // Mostrar mensaje al usuario
});

// Los listeners se registran automáticamente en el bootstrap
// Se limpian automáticamente en dispose()
*/

// ============================================================
// EJEMPLO 3: Manejar Estado del Wizard
// ============================================================

/* CÓDIGO ANTIGUO - Implícito
// ¿Cuál es el estado actual del wizard?
// ¿Está guardando?
// ¿Se puede click "Atrás"?
// Tienes que inspeccionar el DOM o variables globales

if (window.StateManager?.pasoActual === 2) {
    // ... hacer algo
}

if (window.evitarInicializacionWizard) {
    // ... significa que está guardando? o cerrando?
    // No está claro
}
*/

/* CÓDIGO NUEVO - Explícito
const { stateMachine, eventBus } = wizardInstance;

// ¿Cuál es el estado?
console.log(stateMachine.getState());  // 'READY', 'USER_INPUT', 'SAVING', etc.

// ¿Puede el usuario interactuar?
if (stateMachine.isInteractable()) {
    // El wizard está listo para entrada del usuario
    // NUNCA estaremos en estado SAVING o CLOSING aquí
}

// ¿Está activo el wizard?
if (stateMachine.isActive()) {
    // No está IDLE ni DISPOSED
    // Es seguro mostrar la UI
}

// ¿Puedo hacer un transition a X?
if (stateMachine.canTransition('SAVING')) {
    // Sí, puedo guardar
    // La máquina de estados valida automáticamente
}

// Suscribirse a cambios de estado
stateMachine.on('state-changed', ({ oldState, newState }) => {
    console.log(`Transición: ${oldState} → ${newState}`);
    // Puedo actualizar la UI basado en el nuevo estado
});
*/

// ============================================================
// EJEMPLO 4: Prevenir Memory Leaks
// ============================================================

/* CÓDIGO ANTIGUO - Sin limpieza clara
// En algún lado del código:
function toggleVistaAsignacion() {
    // ... listeners registrados aquí ...
    const checkbox = document.querySelector('input.talla');
    checkbox?.addEventListener('change', handleTallaChange);
    // ... si toggleVistaAsignacion se llama múltiples veces ...
    // Los listeners se acumulan
    // Memory leak
}

// ¿Cuándo limpiar?
// No hay clear point de limpieza
*/

/* CÓDIGO NUEVO - Limpieza garantizada
// Los listeners se registran en el bootstrap y se limpian en dispose()
await wizardInstance.lifecycle.dispose();

// Ahora:
// - Todos los listeners están removidos
// - Event bus está limpio
// - State machine está destruido
// - La instancia puede ser recolectada por GC

// Si vuelves a abrir el wizard:
const newWizardInstance = await WizardBootstrap.create();
// Completamente nuevo, sin residuos del anterior
*/

// ============================================================
// EJEMPLO 5: Debugging - Ver todo lo que pasó
// ============================================================

/* CÓDIGO ANTIGUO - Imposible de debuggear
// ¿Por qué no funcionó algo?
// ¿Cuáles listeners están registrados?
// ¿En qué orden se ejecutaron?
// No hay forma de ver esto fácilmente
*/

/* CÓDIGO NUEVO - Totalmente trazable
const { stateMachine, eventBus } = wizardInstance;

// Ver todas las transiciones de estado
console.table(stateMachine.getHistory());
// [
//   { state: 'IDLE', timestamp: 1707900000000 },
//   { state: 'INITIALIZING', timestamp: 1707900000100, metadata: {...} },
//   { state: 'READY', timestamp: 1707900000150 },
//   { state: 'USER_INPUT', timestamp: 1707900000200 },
//   { state: 'SAVING', timestamp: 1707900001000 },
//   { state: 'POST_SAVE', timestamp: 1707900002000 },
//   { state: 'CLOSING', timestamp: 1707900002100 },
//   { state: 'IDLE', timestamp: 1707900002150 }
// ]

// Ver todos los eventos enviados
console.table(eventBus.getEventHistory());
// [
//   { event: 'state-changed', timestamp: ..., data: {...} },
//   { event: 'button:siguiente:clicked', timestamp: ..., data: {...} },
//   { event: 'wizard:error', timestamp: ..., data: {...} },
//   ...
// ]

// Ver número de listeners vivos para un evento
eventBus.getSubscriberCount('button:siguiente:clicked');  // 1

// Esto permite debugging rápido sin DevTools complejos
*/

// ============================================================
// EJEMPLO 6: Testing la Nueva Arquitectura
// ============================================================

/* CÓDIGO ANTIGUO - Casi imposible de testear
describe('toggleVistaAsignacion', () => {
    // ¿Cómo hago mock de window.WizardManager?
    // ¿Cómo hago mock de window.StateManager?
    // ¿Cómo reseteo el estado entre tests?
    // Es un desastre
});
*/

/* CÓDIGO NUEVO - Fácil de testear
describe('WizardNewArchitecture', () => {
    it('debe mostrar wizard cuando está IDLE', async () => {
        const sm = new WizardStateMachine();
        const bus = new WizardEventBus();
        const lifecycle = new WizardLifecycleManager({
            stateMachine: sm,
            eventBus: bus,
            domSelectors: { required: [] },
            hooks: { /* mocks */ }
        });

        expect(lifecycle.getState()).toBe('IDLE');
        
        // Simula: usuario hace click en "Asignar Colores"
        await lifecycle.show();
        
        expect(lifecycle.getState()).toBe('READY');
    });

    it('debe prevenir mostrar wizard si ya está mostrado', async () => {
        const sm = new WizardStateMachine();
        const bus = new WizardEventBus();
        const lifecycle = new WizardLifecycleManager({
            stateMachine: sm,
            eventBus: bus,
            domSelectors: { required: [] },
            hooks: {}
        });

        await lifecycle.show();  // Primera vez: OK
        await expect(lifecycle.show()).rejects.toThrow();  // Segunda vez: Error
    });

    it('debe limpiar todos los listeners en dispose()', async () => {
        const bus = new WizardEventBus();
        bus.subscribe('test', () => {});
        
        expect(bus.getSubscriberCount('test')).toBe(1);
        
        bus.clear();
        
        expect(bus.getSubscriberCount('test')).toBe(0);
    });
});
*/

// ============================================================
// RESUMEN: Beneficios de la Migración
// ============================================================

/*
✅ ANTES DE MIGRACIÓN:
- Flag global mágico: window.evitarInicializacionWizard
- Listeners acumulados sin limpieza clara
- Estados implícitos (¿dónde está el estado real?)
- Acoplamiento fuerte entre módulos
- Memory leaks en recorrer componentes
- Testing imposible sin mocks complejos
- Debugging lento y manual

✅ DESPUÉS DE MIGRACIÓN:
- Máquina de estados clara y validada
- Listeners registrados y removidos explícitamente
- Estados en stateMachine (una fuente de verdad)
- Desacoplamiento vía event bus
- Limpieza garantizada en dispose()
- Testing fácil (cada componente independiente)
- Debugging con historial de transiciones y eventos

LÍNEA DE FONDO:
Cambio de "parches amontonados" a "arquitectura profesional"
Sin breaking changes en el código existente hasta que se migre
Coexistencia pacífica de código viejo y nuevo
*/
