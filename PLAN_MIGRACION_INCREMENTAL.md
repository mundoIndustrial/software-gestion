# 🚀 PLAN DE MIGRACIÓN INCREMENTAL - MODAL SYSTEM ERP

## 📊 DIAGNÓSTICO (MINIMALISTA)

| Problema | Impacto | Causa Raíz |
|----------|--------|-----------|
| Doble fetch API |  Alto | `cargarCatalogosModal()` llamado desde múltiples puntos sin deduplicación |
| Listeners duplicados | 🟡 Medio | No hay limpieza de listeners entre aperturas |
| Race condition | 🟡 Medio | Flags globales `_telasCargadas` sin sincronización |
| Inicialización múltiple | 🟡 Medio | `DragDropManager.inicializar()` no tiene guard clause real |
| Global scope pollution | 🟡 Medio | 15+ variables en `window.*` sin encapsulación |

**Riesgo actual:** Degradación de performance, bugs intermitentes, usuario espera sin feedback claro.

---

## 🎯 ESTRATEGIA DE MIGRACIÓN (4 FASES)

```
ACTUAL                    FASE 1              FASE 2              FASE 3
(Caos)                   (Estable)           (Controlado)        (Modular)

Legacy             →     Promise Dedup   →   FSM Simple      →   ModalSystem
Global Scope       →     + Guard Clauses     + Listeners         Encapsulado
Múltiples puntos   →     Único Entry Point   Control Estado
Sin control        →     Logging Creado
```

---

##  FASE 1: ESTABILIZACIÓN INMEDIATA (1-2 días)

**Objetivo:** Eliminar doble fetch y listeners duplicados. **Riesgo: MÍNIMO**

### Cambios a Implementar

#### 1️⃣ Crear `PromiseCache` Simple (no clase, solo factory)

Archivo nuevo muy pequeño que actúa como cache de promises en flight:

```javascript
// public/js/modulos/crear-pedido/prendas/promise-cache.js
const PromiseCache = (() => {
    const cache = new Map();
    
    return {
        set: (key, promise) => {
            cache.set(key, promise);
        },
        
        get: (key) => {
            return cache.get(key);
        },
        
        has: (key) => {
            return cache.has(key);
        },
        
        delete: (key) => {
            cache.delete(key);
        },
        
        clear: () => {
            cache.clear();
        },
        
        size: () => {
            return cache.size;
        }
    };
})();
```

**¿Por qué así?** No es una clase (menos cambios), es un singleton closure (hermético).

#### 2️⃣ Refactorizar `cargarCatalogosModal()` 

En `manejadores-variaciones.js`, reemplazar:

```javascript
//  ANTES
window.cargarCatalogosModal = async function() {
    if (!window._telasCargadas) {
        await cargarTelasDisponibles();
        window._telasCargadas = true;
    }
    if (!window._coloresCargados) {
        await cargarColoresDisponibles();
        window._coloresCargados = true;
    }
};
```

```javascript
//  DESPUÉS
window.cargarCatalogosModal = async function() {
    // Guard: si hay una promise en flight, retornarla (deduplicación)
    if (PromiseCache.has('catalogs')) {
        console.log('[Catálogos] Promise en flight, reutilizando...');
        return PromiseCache.get('catalogs');
    }

    const promise = (async () => {
        try {
            const [telas, colores] = await Promise.all([
                cargarTelasDisponibles(),
                cargarColoresDisponibles()
            ]);
            
            console.log('[Catálogos]  Cargados exitosamente', {
                telas: telas?.length,
                colores: colores?.length
            });
            
            return { telas, colores };
        } catch (error) {
            console.error('[Catálogos]  Error:', error);
            throw error;
        } finally {
            // Eliminar de cache cuando se resuelve
            PromiseCache.delete('catalogs');
        }
    })();

    // Guardar en cache
    PromiseCache.set('catalogs', promise);
    return promise;
};
```

**¿Qué cambia?**
-  Múltiples llamadas simultáneas reutilizan la MISMA promise
-  No hay flags globales (`_telasCargadas`)
-  El cache se auto-limpia cuando termina
-  El código legacy sigue funcionando igual

#### 3️⃣ Guard Clause en `DragDropManager.inicializar()`

En `drag-drop-manager.js`, línea ~43:

```javascript
//  ANTES
inicializar() {
    if (this.inicializado) {
        UIHelperService.log('DragDropManager', 'Sistema ya inicializado', 'warn');
        return this;  // ← Retorna aquí pero luego continúa abajo
    }
    // ... sigue inicializando
}
```

```javascript
//  DESPUÉS
inicializar() {
    if (this.inicializado) {
        UIHelperService.log('DragDropManager', ' Ya inicializado, ignorando llamada duplicada');
        return this;  // ← Guard clause real
    }

    // ... resto del código de inicialización
    this.inicializado = true;
    return this;
}
```

**¿Por qué?** El guard clause actual no funciona. Necesita garantizar que el código después NO se ejecute.

#### 4️⃣ Único Punto de Entrada para Abrir Modal

En `gestion-items-pedido.js`, línea ~298:

```javascript
//  ANTES (múltiples puntos de entrada)
abrirModalAgregarPrendaNueva() {
    if (typeof window.cargarCatalogosModal === 'function') {
        window.cargarCatalogosModal().catch(error => { ... });
    }
    if (esEdicion) {
        this.prendaEditor.cargarPrendaEnModal(...);
    } else {
        this.prendaEditor.abrirModal(false, null);
    }
}
```

```javascript
//  DESPUÉS (orquestación clara)
async abrirModalAgregarPrendaNueva() {
    try {
        // Paso 1: Cargar catálogos (deduplicado)
        console.log('[Modal] Abriendo, cargando catálogos...');
        await window.cargarCatalogosModal();
        
        // Paso 2: Preparar modal según modo
        const esEdicion = this.prendaEditIndex !== null && this.prendaEditIndex !== undefined;
        
        if (esEdicion) {
            const prendaAEditar = this.prendas[this.prendaEditIndex];
            if (prendaAEditar && this.prendaEditor) {
                this.prendaEditor.cargarPrendaEnModal(prendaAEditar, this.prendaEditIndex);
            }
        } else {
            if (this.prendaEditor) {
                this.prendaEditor.abrirModal(false, null);
            }
        }
        
        console.log('[Modal]  Abierto correctamente');
    } catch (error) {
        console.error('[Modal]  Error abriendo:', error);
        // Notificar usuario
        if (typeof NotificationService !== 'undefined') {
            NotificationService.error('Error abriendo modal: ' + error.message);
        }
    }
}
```

**¿Qué cambio?** Espera explícita a que `cargarCatalogosModal()` termine ANTES de abrir el modal.

---

## 📝 IMPLEMENTACIÓN FASE 1

Voy a mostrar el código exacto para Fase 1.

---

##  RIESGOS FASE 1 Y MITIGACIÓN

| Riesgo | Probabilidad | Mitigación |
|--------|-------------|-----------|
| Modal abre sin catálogos | 🟡 Media | Cambio async en abrirModalAgregarPrendaNueva() requiere que otros puntos de entrada también hagan await |
| Listeners aún duplicados | 🟡 Media | Esto se arregla en Fase 2 - aún no tocar |
| Log noise |  Bajo | Agregar console.log ayuda a debugging, es reversible |
| Rollback fácil |  Muy fácil | Cambios son quirúrgicos, revertibles en minutos |

---

## 🛑 QUÉ NO TOCAR EN FASE 1

```javascript
 NO modificar:
  - modal-cleanup.js (se elimina en Fase 2)
  - TelaDragDropHandler.js
  - PrendaDragDropHandler.js
  - prenda-editor.js
  - Estructura de localStorage
  - HTML del modal
  - Variables globales existentes (excepto agregar flags)

 SÍ modificar:
  - manejadores-variaciones.js (cargarCatalogosModal)
  - gestion-items-pedido.js (abrirModalAgregarPrendaNueva)
  - drag-drop-manager.js (inicializar guard clause)
  - Crear promise-cache.js (nuevo archivo mínimo)
```

---

##  FASE 2: CONTROL DE LISTENERS (3-5 días)

**Objetivo:** Limpiar listeners sin romper Bootstrap Modal

### Cambios a Implementar

#### Patrón: Listener Registry

```javascript
// Crear registro centralizado de listeners
const ModalListenerRegistry = (() => {
    const listeners = [];
    
    return {
        register: (element, event, handler) => {
            element.addEventListener(event, handler);
            listeners.push({ element, event, handler });
            console.log('[ModalListeners] Registrado:', event);
        },
        
        unregisterAll: () => {
            listeners.forEach(({ element, event, handler }) => {
                element.removeEventListener(event, handler);
            });
            listeners.length = 0;
            console.log('[ModalListeners] Todos limpios');
        },
        
        count: () => listeners.length
    };
})();
```

#### Modificar bootstrap modal cleanup

En `modal-cleanup.js`, agregar esta función:

```javascript
static limpiarListenersModal() {
    if (typeof ModalListenerRegistry !== 'undefined') {
        ModalListenerRegistry.unregisterAll();
    }
}
```

Y llamarla desde `limpiarTodo()`:

```javascript
static limpiarTodo() {
    this.limpiarFormulario();
    this.limpiarStorages();
    this.limpiarListenersModal(); // ← NUEVO
    // ... resto
}
```

#### Cambiar shown.bs.modal listener

En el archivo que tiene `shown.bs.modal`:

```javascript
//  ANTES
modal.addEventListener('shown.bs.modal', function() {
    // ... inicialización
});

modal.addEventListener('shown.bs.modal', function() {
    // ... otra inicialización (DUPLICADO)
});
```

```javascript
//  DESPUÉS
const onModalShown = function() {
    console.log('[Modal] shown.bs.modal disparado');
    // Inicializar drag & drop si no está
    if (window.DragDropManager && !window.DragDropManager.inicializado) {
        window.DragDropManager.inicializar();
    }
};

// Registrar una única vez
if (!modal.hasAttribute('data-listener-registered')) {
    ModalListenerRegistry.register(modal, 'shown.bs.modal', onModalShown);
    modal.setAttribute('data-listener-registered', 'true');
}
```

**¿Por qué?** El attribute `data-listener-registered` previene registros duplicados.

---

## 🔄 FASE 3: REFACTOR ESTRUCTURAL (1-2 semanas)

**Objetivo:** Introducir FSM simple sin tocar código legacy

### Cambios Mínimos

#### Mini FSM (versión reducida para Fase 3)

```javascript
// public/js/modulos/crear-pedido/prendas/modal-state-machine-lite.js
const ModalStateMachineLight = (() => {
    let state = 'CLOSED';
    const VALID_TRANSITIONS = {
        'CLOSED': ['OPENING'],
        'OPENING': ['OPEN', 'CLOSED'],
        'OPEN': ['CLOSING'],
        'CLOSING': ['CLOSED']
    };
    
    return {
        getState: () => state,
        
        transition: (newState) => {
            const allowed = VALID_TRANSITIONS[state] || [];
            if (!allowed.includes(newState)) {
                console.warn(`[FSM] Transición inválida: ${state} → ${newState}`);
                return false;
            }
            console.log(`[FSM] ${state} → ${newState}`);
            state = newState;
            return true;
        },
        
        reset: () => {
            state = 'CLOSED';
        }
    };
})();
```

#### Integrar en abrirModalAgregarPrendaNueva():

```javascript
async abrirModalAgregarPrendaNueva() {
    try {
        ModalStateMachineLight.transition('OPENING');
        
        await window.cargarCatalogosModal();
        
        // ... abrir modal
        
        ModalStateMachineLight.transition('OPEN');
    } catch (error) {
        ModalStateMachineLight.transition('CLOSED');
        throw error;
    }
}
```

---

## 🛠️ ERRORES COMUNES A EVITAR

###  Error 1: Hacer async/await sin verificar TODOS los callers

```javascript
//  PELIGRO
async abrirModalAgregarPrendaNueva() {
    await window.cargarCatalogosModal(); // ← Si un caller no hace await...
    // ... modal abre sin esperar
}

// Llamador antiguo (sin await)
window.gestionItemsUI.abrirModalAgregarPrendaNueva(); // ← Modal abre inmediatamente
```

**Solución:** Auditar TODOS los puntos que llaman esta función y agregar await.

###  Error 2: Eliminar modal-cleanup.js muy rápido

```javascript
//  PELIGRO: Si eliminas modal-cleanup.js en Fase 1/2
// Otros archivos lo importan y el sistema rompe

//  CORRECTO: Mantenerlo hasta Fase 3 cuando integres todo en ModalSystemFacade
```

###  Error 3: Confundir guard clauses con return temprano

```javascript
//  INCORRECTO
inicializar() {
    if (this.inicializado) return this;
    
    // Código aquí se ejecuta SIEMPRE aunque haya retornado
    this.prendaHandler = new PrendaDragDropHandler(); // ← Se ejecuta igual
}

//  CORRECTO
inicializar() {
    if (this.inicializado) {
        console.log('Ya inicializado');
        return this; // ← Sale completamente, no ejecuta nada más
    }
    
    // Código aquí SOLO se ejecuta si no estaba inicializado
    this.prendaHandler = new PrendaDragDropHandler();
}
```

###  Error 4: Asumir que flags globales son seguros

```javascript
//  NO HAGAS
if (!window._modalAbierto) {
    abrirModal();
    window._modalAbierto = true;
}
// Race condition: dos llamadas simultáneas pueden ambas pasar el if

//  MEJOR
if (ModalStateMachineLight.getState() === 'CLOSED') {
    ModalStateMachineLight.transition('OPENING');
    abrirModal();
}
// Una transición invalida es rechazada atomicamente
```

---

## 🎯 SEÑALES DE QUE EL SISTEMA ESTÁ ESTABLE

### Después de Fase 1:
```
 Console logs muestran:
  "[Catálogos] Promise en flight, reutilizando..." (solo 1 vez, no 2)
  "[Modal]  Abierto correctamente"
  "Sistema ya inicializado, ignorando llamada duplicada"

 Network tab (DevTools):
  /api/public/telas - aparece 1 vez (no 2)
  /api/public/colores - aparece 1 vez (no 2)

 Para usuario:
  Modal abre más rápido (catálogos cargados)
  Sin flickering de listeners duplicados
  Sin errores en console
```

### Después de Fase 2:
```
 console logs muestran:
  "[ModalListeners] Todos limpios" (cada cierre)
  "[ModalListeners] Registrado: shown.bs.modal" (solo 1 vez)

 Memory profiler (Chrome DevTools):
  Detached DOM nodes disminuye cuando cierras modal
  Listeners count es estable (no crece con cada apertura)

 Para usuario:
  Modal puede abrirse/cerrarse 10 veces sin lentitud
```

### Después de Fase 3:
```
 console logs muestran:
  "[FSM] CLOSED → OPENING → OPEN"
  "[FSM] Transición inválida: OPEN → OPENING" (rechazada correctamente)

 Para usuario:
  Código está preparado para refactor a ModalSystemFacade
  Arquitectura es clara y documentada
```

---

##  CHECKLIST DE IMPLEMENTACIÓN

### Fase 1 (Hoy)
- [ ] Crear `promise-cache.js`
- [ ] Refactorizar `cargarCatalogosModal()` con deduplicación
- [ ] Agregar guard clause real en `DragDropManager.inicializar()`
- [ ] Hacer `abrirModalAgregarPrendaNueva()` async
- [ ] Auditar TODOS los callers de `abrirModalAgregarPrendaNueva()`
- [ ] Agregar await donde sea necesario
- [ ] Testing en desarrollo (abrir/cerrar modal 5 veces)
- [ ] Deploy a producción
- [ ] Monitorear console logs por 24h

### Fase 2 (La semana siguiente)
- [ ] Crear `ModalListenerRegistry`
- [ ] Agregar `limpiarListenersModal()` a ModalCleanup
- [ ] Reemplazar listeners duplicados con registry
- [ ] Testing de múltiples aperturas/cierres
- [ ] Verificar memory leaks con Chrome DevTools
- [ ] Deploy a producción

### Fase 3 (Semana 3)
- [ ] Crear `ModalStateMachineLight`
- [ ] Integrar FSM en ciclo de vida
- [ ] Documentar flujo de estados
- [ ] Preparar para refactor a ModalSystemFacade

---

## 🚨 ROLLBACK STRATEGY

Si algo falla en producción:

**Fase 1 Rollback (5 minutos):**
```bash
git checkout manejadores-variaciones.js
git checkout gestion-items-pedido.js
git checkout drag-drop-manager.js
rm promise-cache.js
# Servidor recarga automáticamente
```

**Fase 2 Rollback (5 minutos):**
```bash
git checkout modal-cleanup.js
rm ModalListenerRegistry
```

---

**Estado:** Ready para Fase 1  
**Timeline:** 3 semanas para 3 fases  
**Riesgo General:** 🟢 BAJO (cambios quirúrgicos, reversibles)
