# Arquitectura del Wizard: Justificación Detallada

## 1. Decisión: State Machine Formal

### ¿Por qué?
El código actual usa flags globales (`evitarInicializacionWizard`, `data-guardando`) que son:
- **Frágiles**: cualquier place puede setearlos incorrectamente
- **Implícitos**: el comportamiento no es obvio leyendo el código
- **Propensos a bugs**: estados contradictorios posibles
- **Imposibles de testear**: side effects globales

### La Solución: Máquina de Estados
```javascript
// ANTES (frágil):
window.evitarInicializacionWizard = true;
// ... código que asume this flag ...
delete window.evitarInicializacionWizard;
// Si algo falla en medio, flag queda en estado inconsistente

// DESPUÉS (explícito):
stateMachine.transition(WizardStateMachine.STATES.SAVING);
// Las transiciones válidas están predefinidas
// El estado es siempre consistente
// Los estados finales son inalcanzables desde el estado actual
```

### Ventajas
1. **Prevención de estados inválidos**: La máquina rechaza transiciones imposibles
2. **Debugging trivial**: Sé exactamente qué estado debe tener el widget en cada momento
3. **Testeable**: Puedo testear todas las transiciones permitidas y rechazadas
4. **Documentación viva**: El diagrama de estados es la especificación

---

## 2. Decisión: Event Bus Centralizado

### ¿Por qué no callbacks directo?
```javascript
// ANTI-PATRÓN: Acoplamiento directo
btnSiguiente.onclick = () => {
    WizardManager.irPaso(paso + 1);  // Acoplado
    UIRenderer.update();              // Acoplado
    StateManager.save();              // Acoplado
    // Añadir una nueva funcionalidad requiere editar esta función
};

// PATRÓN: Event Bus
eventBus.emit('button:siguiente:clicked');
// Múltiples handlers pueden escuchar sin saber unos de otros
eventBus.subscribe('button:siguiente:clicked', () => WizardManager.irPaso());
eventBus.subscribe('button:siguiente:clicked', () => UIRenderer.update());
eventBus.subscribe('button:siguiente:clicked', () => Analytics.track());
// Nuevo feature, sin modificar código existente
```

### Ventajas
1. **Desacoplamiento**: Los módulos no conocen unos de otros
2. **Principio Open/Closed**: Nuevo comportamiento sin modificar código existente
3. **Mantenibilidad**: Cada listener es responsable de UNA cosa
4. **Testing**: Puedo testear listeners individualmente

---

## 3. Decisión: Presupuesto en Ciclo de Vida (Show → Close → Dispose)

### Tres niveles diferentes de "cerrar"

```javascript
// NIVEL 1: close()
// Oculta el wizard, pero mantiene estado
// El próximo show() puede restaurar el estado anterior
await lifecycle.close();
// ... usuario vuelve a hacer clic en "Asignar Colores" ...
await lifecycle.show();  // Recupera estado

// NIVEL 2: cleanup()
// Desregistra listeners, pero la máquina de estados sigue viva
// Para uso interno durante transiciones
await lifecycle.cleanup();

// NIVEL 3: dispose()
// Limpieza FINAL: libera todos los recursos
// La máquina de estados se destruye
// El wizard no se puede reutilizar
await lifecycle.dispose();
// lifecycle.show() aquí lanzaría error "wizard está disposed"
```

### Por qué esto importa: Memory Leaks
```javascript
// SIN dispose(): En una SPA que reutiliza la página múltiples veces
const wizard = await WizardBootstrap.create();
await wizard.lifecycle.show();
await wizard.lifecycle.close();
// ... evento: usuario navega a otra página y vuelve ...
const newWizard = await WizardBootstrap.create();
// Los listeners del wizard anterior siguen vivos
// Memory leak: listeners acumulados

// CON dispose():
const wizard = await WizardBootstrap.create();
await wizard.lifecycle.show();
await wizard.lifecycle.close();
// ... evento: usuario navega a otra página ...
if (wizard) {
    await wizard.lifecycle.dispose();
    wizard = null;
}
// Listeners completamente limpios cuando se navega
```

---

## 4. Decisión: Hooks Inyectables vs Código Hardcodeado

### ¿Por qué hooks?
```javascript
// ANTI-PATRÓN: Lógica hardcodeada
WizardLifecycleManager.prototype._restoreState = function() {
    if (window.StateManager) {
        const paso = window.StateManager.getPasoActual();
        // ... lógica específica del dominio ...
    }
};
// Problema: La clase está acoplada a StateManager
// No se puede usar sin tener StateManager

// PATRÓN: Hooks inyectables
const lifecycle = new WizardLifecycleManager({
    hooks: {
        'restoreState': async () => {
            // El cliente decide QUÉ hacer
            if (window.StateManager) {
                const paso = window.StateManager.getPasoActual();
                console.log('Restaurado paso:', paso);
            }
        }
    }
});
// La clase NO depende de StateManager
// Se puede usar en distintos contextos
```

### Beneficio: Testear sin dependencias reales
```javascript
// En tests
const mockLifecycle = new WizardLifecycleManager({
    hooks: {
        'restoreState': async () => {
            console.log('Mock restore state');
        }
    }
});
// No necesito mock para StateManager, EventBus, etc.
```

---

## 5. Decisión: Listeners Registrados vs Event Delegation

### El problema con event delegation
```javascript
// PROBLEMA: Event delegation en contenedor global
document.addEventListener('click', (e) => {
    if (e.target.matches('#btn-siguiente')) {
        handleSiguiente();
    }
});
// Este listener se ejecuta en CADA click de la página
// Acumula work innecesario
// Difícil de limpiar selectivamente
```

### La solución: Registro explícito y limpieza garantizada
```javascript
// SOLUCIÓN: Registrar listeners específicos
const unsubscribe = element.addEventListener('click', handler);
// Guardar la función handler para limpieza
listeners.push({ element, event: 'click', handler });

// En cleanup():
listeners.forEach(({ element, event, handler }) => {
    element.removeEventListener(event, handler);
});
// Garantizado: ningún listener queda vivo
```

### Ventaja de performance
```javascript
// Escenario: Usuario abre/cierra wizard 10 veces
// CON event delegation: 10 listeners + 10 no removidos = 20 listeners
// CON registro explícito: siempre 1 listener + removido = 0 listeners
```

---

## 6. Decisión: Configuración Centralizada en Bootstrap

### ¿Por qué no hacer new WizardLifecycleManager() directamente?

```javascript
// ANTI-PATRÓN: Instanciación dispersa
// En ColoresPorTalla.js
const lifecycle = new WizardLifecycleManager(config1);

// En otro archivo
const otherWizard = new WizardLifecycleManager(config2);

// En tests
const testWizard = new WizardLifecycleManager(config3);

// Problema: 3 formas distintas de crear el wizard
// Inconsistencia, bugs, mantenimiento difícil

// PATRÓN: Factory centralizado
const { lifecycle, eventBus, stateMachine } = await WizardBootstrap.create(config);
// Una forma de crear el wizard
// Configuración consistente
// Fácil de cambiar globalmente
```

### Bootstrap también:
- Valida precondiciones (telas existen?)
- Inyecta dependencias explícitamente
- Registra listeners iniciales
- Documenta el flujo de inicialización

---

## 7. Buenas Prácticas para Testing

### Test de máquina de estados
```javascript
describe('WizardStateMachine', () => {
    it('debe permitir transición IDLE -> INITIALIZING', () => {
        const sm = new WizardStateMachine();
        expect(sm.canTransition('INITIALIZING')).toBe(true);
        sm.transition('INITIALIZING');
        expect(sm.getState()).toBe('INITIALIZING');
    });

    it('debe RECHAZAR transición IDLE -> SAVING', () => {
        const sm = new WizardStateMachine();
        expect(() => sm.transition('SAVING')).toThrow();
    });

    it('debe llevar registro de historial', () => {
        const sm = new WizardStateMachine();
        sm.transition('INITIALIZING');
        sm.transition('READY');
        expect(sm.getHistory()).toHaveLength(3);  // IDLE, INITIALIZING, READY
    });
});
```

### Test de event bus
```javascript
describe('WizardEventBus', () => {
    it('debe ejecutar handlers en orden de prioridad', async () => {
        const bus = new WizardEventBus();
        const order = [];
        
        bus.subscribe('test', () => order.push(1), { priority: 1 });
        bus.subscribe('test', () => order.push(2), { priority: 2 });
        bus.subscribe('test', () => order.push(0), { priority: 0 });
        
        bus.emit('test');
        expect(order).toEqual([2, 1, 0]);  // Mayor prioridad primero
    });

    it('debe limpiar suscriptores únicos', () => {
        const bus = new WizardEventBus();
        bus.once('test', () => {});
        expect(bus.getSubscriberCount('test')).toBe(1);
        
        bus.emit('test');
        expect(bus.getSubscriberCount('test')).toBe(0);  // Removido automáticamente
    });
});
```

### Test de ciclo de vida
```javascript
describe('WizardLifecycleManager', () => {
    it('debe hacer show() solo desde IDLE', async () => {
        const config = {
            stateMachine: new WizardStateMachine(),
            eventBus: new WizardEventBus(),
            domSelectors: { required: [] }
        };
        const lifecycle = new WizardLifecycleManager(config);
        
        await expect(lifecycle.show()).resolves.toBeDefined();
        // Ahora está en READY
        
        // Intentar show() de nuevo
        await expect(lifecycle.show()).rejects.toThrow();
    });

    it('debe cleanup TODOS los listeners', async () => {
        // ... setup ...
        const listener = jest.fn();
        eventBus.subscribe('test', listener);
        
        await lifecycle.cleanup();
        
        eventBus.emit('test');
        expect(listener).not.toHaveBeenCalled();  // Ya no escucha
    });
});
```

---

## 8. Integración con Código Existente

### Paso 1: Crear instancia en el lugar correcto
```javascript
// En ColoresPorTalla.js, cuando se abre el wizard
const { lifecycle, eventBus, stateMachine } = await WizardBootstrap.create({
    onReady: () => {
        console.log('Wizard listo para interactuar');
    },
    onClosed: () => {
        console.log('Wizard cerrado');
    }
});

window.wizardInstance = { lifecycle, eventBus, stateMachine };
```

### Paso 2: Usar el event bus en lugar de llamadas directas
```javascript
// ANTES:
btnSiguiente.onclick = () => WizardManager.irPaso(paso + 1);

// DESPUÉS:
// El Bootstrap ya registró este listener
// Solo necesitas publicar el evento
eventBus.emit('button:siguiente:clicked');
```

### Paso 3: Limpiar al finalizar
```javascript
// Cuando el usuario cierre el dialog
function toggleVistaAsignacion() {
    if (esVistaAsignacionActiva) {
        await window.wizardInstance.lifecycle.close();
    } else {
        await window.wizardInstance.lifecycle.show();
    }
}

// Cuando la página se descarga completamente
window.addEventListener('beforeunload', async () => {
    await window.wizardInstance?.lifecycle.dispose();
});
```

---

## 9. Resumen de Principios SOLID Aplicados

| Principio | Aplicación |
|-----------|-----------|
| **S**ingle Responsibility | Cada clase tiene una responsabilidad clara: StateMachine (estados), EventBus (eventos), LifecycleManager (coordinación) |
| **O**pen/Closed | Extensible vía eventos y hooks sin modificar código existente |
| **L**iskov Substitution | Todos los handlers son fungibles (función(data) => void) |
| **I**nterface Segregation | APIs pequeñas y específicas (show(), close(), dispose()) |
| **D**ependency Inversion | Inyección de dependencias, no hardcodeado |

---

## 10. Checklist: De Parches a Arquitectura Limpia

### ANTES (Problemas)
- [ ] Flags globales sin validación (`evitarInicializacionWizard`)
- [ ] Estados inconsistentes posibles
- [ ] Listeners acumulados sin limpieza garantizada
- [ ] Acoplamiento entre módulos (WizardManager → StateManager → UIRenderer)
- [ ] Efectos secundarios ocultos
- [ ] Difícil de testear
- [ ] Memoria leaks en recorrer/volver a componentes

### DESPUÉS (Soluciones)
- [x] Máquina de estados que VALIDA transiciones
- [x] Estados siempre consistentes
- [x] Listeners registrados y limpios explícitamente
- [x] Desacoplamiento vía event bus
- [x] Efectos secundarios claros en hooks
- [x] Testable: cada componente independiente
- [x] dispose() garantiza limpieza total

