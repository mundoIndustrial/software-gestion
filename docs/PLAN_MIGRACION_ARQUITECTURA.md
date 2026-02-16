# Plan de Migración: De Código Actual a Arquitectura Limpia

## Resumen Ejecutivo
- **Tiempo estimado**: 1-2 días (prueba manual)
- **Riesgo**: Bajo (cambios fuera del código crítico)
- **Beneficio**: Arquitectura profesional, mantenible, testeable

---

## Fase 1: Validación de Arqutiectura (SIN cambios en código)

### Objetivo
Verificar que la arquitectura propuesta es compatible con el código existente.

### Acciones
1. **Crear archivo de tests conceptual**
   - ¿Funciona WizardStateMachine?
   - ¿Funciona WizardEventBus?
   - ¿Funciona WizardLifecycleManager?
   - ¿Se pueden integrar juntos?

2. **Validar en consola del navegador**
   ```javascript
   // Abrir DevTools
   // Importar los scripts (ya creados)
   <script src="/js/arquitectura/WizardStateMachine.js"></script>
   <script src="/js/arquitectura/WizardEventBus.js"></script>
   <script src="/js/arquitectura/WizardLifecycleManager.js"></script>
   <script src="/js/arquitectura/WizardBootstrap.js"></script>

   // En consola:
   const sm = new WizardStateMachine();
   sm.transition('INITIALIZING');  // Debe funcionar
   sm.transition('READY');          // Debe funcionar
   sm.transition('SAVING');         // Debe lanzar error
   ```

3. **Decidir punto de integración**
   - ¿Integrar en ColoresPorTalla.js?
   - ¿Integrar en un archivo nuevo?
   - ¿Crear WizardFacade para compatibilidad?

---

## Fase 2: Integración Gradual

### Opción A: Integración Paralela (Recomendada)
Código nuevo usa arquitectura nueva, código viejo sigue funcionando.

```javascript
// En ColoresPorTalla.js

// NUEVO: La arquitectura limpia
let wizardInstance = null;

async function inicializarWizardNuevo() {
    wizardInstance = await WizardBootstrap.create({
        onReady: () => console.log('Wizard listo'),
        onClosed: () => console.log('Wizard cerrado')
    });
    
    return wizardInstance;
}

// VIEJO: Código existente sigue funcionando
// pero ahora tira eventos al event bus también
function toggleVistaAsignacion() {
    // ... código existente ...
    
    // Además, emitir evento para que nueva arquitectura se entere
    if (wizardInstance?.eventBus) {
        wizardInstance.eventBus.emit('vista:asignacion:mostrada');
    }
}
```

### Opción B: Integración Completa (Para más adelante)
Reemplazar ColoresPorTalla.js completamente.

---

## Fase 3: Capa de Compatibilidad (Bridge Pattern)

Crear un adaptador que haga que el código viejo y nuevo se entiendan:

```javascript
/**
 * WizardCompatibilityBridge
 * Adaptador entre arquitectura antigua y nueva
 */
class WizardCompatibilityBridge {
    constructor(wizardInstance) {
        this.lifecycle = wizardInstance.lifecycle;
        this.eventBus = wizardInstance.eventBus;
        this.stateMachine = wizardInstance.stateMachine;
        
        this._setupBackwardCompatibility();
    }

    _setupBackwardCompatibility() {
        // Cuando código viejo llama a window.toggleVistaAsignacion(),
        // traducir a la nueva arquitectura
        window.toggleVistaAsignacion = async () => {
            try {
                if (this.lifecycle.getState() === 'IDLE') {
                    await this.lifecycle.show();
                } else {
                    await this.lifecycle.close();
                }
            } catch (error) {
                console.error('Error toggling vista:', error);
            }
        };

        // Cuando código viejo quiere saber el estado
        window.wizardGetState = () => this.stateMachine.getState();
        window.wizardIsReady = () => this.lifecycle.getState() === 'READY';
    }
}

// Uso:
const { lifecycle, eventBus, stateMachine } = await WizardBootstrap.create();
const bridge = new WizardCompatibilityBridge({ lifecycle, eventBus, stateMachine });

// Ahora el código viejo puede seguir usando:
window.toggleVistaAsignacion();  // Funciona!
```

---

## Fase 4: Eliminar Deuda Técnica Paso a Paso

### Eliminar Flag Global: `evitarInicializacionWizard`

ANTES:
```javascript
// En ColoresPorTalla.js
window.evitarInicializacionWizard = true;
setTimeout(() => {
    toggleVistaAsignacion();
});
```

DESPUÉS:
```javascript
// El WizardLifecycleManager maneja esto automáticamente
// No se necesita el flag
await lifecycle.close();
// Internamente ya se encarga de NOT reinicializar si vuelves a abrir
```

### Eliminar Flag Global: `data-guardando`

ANTES:
```javascript
btnGuardarAsignacion.dataset.guardando = 'true';
if (btnGuardarAsignacion.dataset.guardando === 'true') return;
```

DESPUÉS:
```javascript
// El event bus previene múltiples clicks automáticamente
eventBus.emit('button:guardar:clicked');
// Solo una vez será procesado si está en estado SAVING
```

### Eliminar Listeners Registrados Manualmente

Antes, el código registraba listeners así:
```javascript
// En mostrarTallasPorTipo()
checkbox.addEventListener('change', () => { ... });  // Manual
```

Después:
```javascript
// En hooks del LifecycleManager
'registerListeners': [
    {
        selector: 'input[type=checkbox].talla-checkbox',
        event: 'change',
        handler: (e) => eventBus.emit('talla:seleccionada', { talla: e.target.value })
    }
]
// Automático, trazable, limpiable
```

---

## Fase 5: Checklist de Migración Completa

```
SEMANA 1:
- [ ] Crear archivos de arquitectura (YA HECHO)
- [ ] Tests unitarios de cada componente
- [ ] Integración paralela en ColoresPorTalla.js
- [ ] Validar que el wizard funciona

SEMANA 2:
- [ ] Crear WizardCompatibilityBridge
- [ ] Eliminar flags globales
- [ ] Validar no hay memory leaks
- [ ] Documentar cambios

SEMANA 3:
- [ ] Pruebas de cierre/apertura múltiple
- [ ] Pruebas con dev tools (ver listeners vivos)
- [ ] Performance profiling
- [ ] Documentación para equipo

SEMANA 4+:
- [ ] Refactor de ColoresPorTalla.js (gradual)
- [ ] Refactor de WizardManager.js (gradual)
- [ ] Eliminar código duplicado
- [ ] Consolidar en una sola arquitectura
```

---

## Conceptos Clave para el Equipo

### 1. La máquina de estados
"El wizard solo puede estar en ciertos estados. Si intenta hacer algo imposible, fallará rápido con error claro obviamente. No hay estados mágicos o implícitos."

### 2. El event bus
"Los botones no llaman a funciones directamente. Emiten eventos. Cualquiera puede escuchar esos eventos. Desacoplado, extensible, testeable."

### 3. El ciclo de vida
- **show()**: Mostrar wizard (IDLE → INITIALIZING → READY)
- **close()**: Cerrar wizard (READY/USER_INPUT → CLOSING → IDLE)
- **dispose()**: Destruir wizard completamente (libera memoria)

### 4. Precondiciones garantizadas
"Cuando el wizard está en READY, absolutamente todos los listeners están registrados y listos. No hay estado fantasma."

### 5. Limpieza garantizada
"Cuando llamas dispose(), va a estar 100% limpio. Listeners removidos, referencias null, memoria liberada. Puedes crear uno nuevo sin residuos."

---

## FAQ: Preguntas Frecuentes

### P: ¿Puedo usar esto sin refactorizar todo el código?
**R**: Sí. Integración paralela significa que puedes tener ambas arquitecturas conviviendo. Gradualmente vas migrando.

### P: ¿Esto va a ser más lento?
**R**: No. Es más rápido:
- Menos listeners vivos en DOM
- Menos búsquedas de elementos
- Event bus es O(1) para emit

### P: ¿Cómo testeo esto?
**R**: Cada componente es independently testeable:
```javascript
// Test de state machine: sin dependencias
const sm = new WizardStateMachine();
sm.transition('INITIALIZING');  // ✓

// Test de event bus: sin dependencias
const bus = new WizardEventBus();
bus.subscribe('test', handler);
bus.emit('test');  // ✓

// Test de lifecycle: con mockups
const lifecycle = new WizardLifecycleManager({
    stateMachine: mockSM,
    eventBus: mockBus,
    hooks: { /* mocks */ }
});
```

### P: ¿Qué pasa si algo falla durante la transición?
**R**: La máquina de estados garantiza atomicidad:
```javascript
try {
    stateMachine.transition('SAVING');
} catch (error) {
    // El estado NO cambió
    stateMachine.getState();  // Sigue siendo lo anterior
}
```

### P: ¿Cómo reporto problemas de la arquitectura?
**R**: Checa el historial:
```javascript
console.log(stateMachine.getHistory());
// [{state: 'IDLE', timestamp: ...}, {state: 'INITIALIZING'}, ...]

console.log(eventBus.getEventHistory());
// [{event: 'button:clicked', timestamp: ..., data: {...}}, ...]
```

---

## Conclusión

Esta arquitectura es un **salto** desde parches/flags globales a un diseño profesional. Cumple con estándares de la industria (State Machine, Observer, Dependency Injection) y deja el código **mantenible, testeable y escalable**.

El código antiguo no se rompe. Coexisten durante la transición. Gradualmente se migra. Cero riesgo.

