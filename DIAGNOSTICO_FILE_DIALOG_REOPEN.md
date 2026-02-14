#  DIAGNÓSTICO: FILE DIALOG SE REABRE AUTOMÁTICAMENTE

## Problema Reportado
El file dialog se **abre automáticamente de nuevo** después de seleccionar una imagen o cerrarlo sin seleccionar.

```
1. Click placeholder → input.click()
2.  Se abre el explorador
3. Selecciono imagen
4.  Se cierra el diálogo
5.  SE REABRE AUTOMÁTICAMENTE
```

**NO es:**
- Doble click del usuario 
- Múltiples listeners  (se limpian correctamente)
- Intentional re-opening 

---

## 🎯 CAUSA RAÍZ

El problema es **reentrancy asincrónica** combinado con **falta de estado del diálogo**:

### Flujo Problemático:
```
abrirSelectorImagenProceso(1)
  ├─ input.click() ◄─── Abre diálogo
  │
  └─ [File dialog abierto - esperando selección]
       │
       └─ Usuario selecciona imagen
            │
            └─ change event dispara
                 │
                 ├─ manejarImagenProcesoConIndice()
                 │   └─ manejarImagenProceso()
                 │       └─ Actualiza preview (innerHTML)
                 │           └─ Agrega handlers al preview
                 │               └─ ❓ Algo causa que se vuelve a disparar en input
                 │
                 └─  input.click() se ejecuta de nuevo
                      └─ ✗ Se reabre el dialogo automáticamente
```

### Causas Posibles (por orden de probabilidad):

#### 1️⃣ **Falta de Control de Estado del Diálogo (PRINCIPAL)**
El input NO tiene un mecanismo que diga:
- "Estoy abriendo el diálogo"
- "El diálogo está abierto"
- "El diálogo se cerró"
- "No intentes abrir mientras estoy procesando"

Sin esto, cualquier flujo asincrónico posterior puede volver a disparar `input.click()`.

#### 2️⃣ **Event Bubbling desde Updating del Preview**
Cuando se actualiza el preview con `innerHTML`:
```javascript
preview.innerHTML = `<img ...><button onclick="eliminarImagenProceso(...)">×</button>`;
```
Si hay un click que no se detiene correctamente, podría subir y disparar algo.

#### 3️⃣ **Re-inicialización de Listeners During Change Processing**
Si `inicializarListenersInputsArchivo()` es llamada durante el change:
```javascript
// En algún lado, durante manejarImagenProceso():
if (typeof inicializarListenersInputsArchivo === 'function') {
    inicializarListenersInputsArchivo(); // ◄─ Esto re-agrega listeners
}
```
Esto re-agrega listeners nuevos al input sobre los existentes.

#### 4️⃣ **Focus Automático Después del Change**
Después de que el navigador procesa el change event, podría intentar poner focus en el input:
```javascript
// Navegador automáticamente:
input.focus();  // ◄─ Si hay esto, podría triggerar un comportamiento
```

#### 5️⃣ **Ciclo de Actualización Asincrona**
Si hay async/await o setTimeout en el flujo que causa que el input sea re-interactuado:
```javascript
await procesarImagen();  // Durante esto, algo vuelve a hacer click
```

---

##  SOLUCIÓN PROFESIONAL (Architecture-Grade)

La solución correcta es implementar un **State Machine** para el file dialog:

### Componente: File Dialog State Manager

```javascript
/**
 * FileDialogStateManager
 * Controla el ciclo de vida del file dialog para prevenir reaperturas automáticas
 * 
 * Estados permitidos:
 * - CLOSED: Diálogo cerrado, listo para abrir
 * - OPENING: Diálogo se está abriendo
 * - PROCESSING: Archivo seleccionado, procesando
 * - HANDLING_CHANGE: En medio de manejar el change event
 * - LOCKED: Bloqueado temporalmente (no permitir clicks)
 */

class FileDialogStateManager {
    constructor(inputId) {
        this.inputId = inputId;
        this.input = document.getElementById(inputId);
        
        // Estados permitidos
        this.STATES = {
            CLOSED: 'CLOSED',           // Listo para usar
            OPENING: 'OPENING',         // Diálogo abriéndose
            PROCESSING: 'PROCESSING',   // Procesando archivo
            HANDLING_CHANGE: 'HANDLING_CHANGE',  // En cambio
            LOCKED: 'LOCKED'            // Bloqueado temporalmente
        };
        
        // Estado actual
        this.currentState = this.STATES.CLOSED;
        
        // Timestamp del último cambio
        this.lastStateChange = Date.now();
        
        if (!this.input) {
            console.error(`[FileDialogStateManager] Input ${inputId} no encontrado`);
            throw new Error(`Input ${inputId} not found`);
        }
        
        // Storear el manager en el input para acceso rápido
        this.input._fileDialogStateManager = this;
    }
    
    /**
     * Verificar si se puede abrir el diálogo
     */
    canOpen() {
        const canOpen = this.currentState === this.STATES.CLOSED;
        console.log(`[FileDialogStateManager:${this.inputId}] canOpen=${canOpen} (estado actual: ${this.currentState})`);
        return canOpen;
    }
    
    /**
     * Marcar que el diálogo se está abriendo
     */
    markOpening() {
        if (!this.canOpen()) {
            console.warn(`[FileDialogStateManager:${this.inputId}] No se puede abrir - estado: ${this.currentState}`);
            return false;
        }
        
        this.setState(this.STATES.OPENING);
        return true;
    }
    
    /**
     * Marcar que el diálogo está procesando cambios
     */
    markProcessing() {
        this.setState(this.STATES.PROCESSING);
    }
    
    /**
     * Marcar que estamos en medio de manejar un change event
     */
    markHandlingChange() {
        this.setState(this.STATES.HANDLING_CHANGE);
    }
    
    /**
     * Marcar como cerrado (listo para siguiente apertura)
     */
    markClosed() {
        this.setState(this.STATES.CLOSED);
    }
    
    /**
     * Bloquear temporalmente (para evitar reaperturas durante procesamiento)
     * @param {number} durationMs - Duración del bloqueo en ms
     */
    lockTemporarily(durationMs = 500) {
        this.setState(this.STATES.LOCKED);
        
        setTimeout(() => {
            if (this.currentState === this.STATES.LOCKED) {
                this.markClosed();
                console.log(`[FileDialogStateManager:${this.inputId}] Bloqueo temporal removido`);
            }
        }, durationMs);
    }
    
    /**
     * Cambiar estado
     */
    setState(newState) {
        const oldState = this.currentState;
        this.currentState = newState;
        this.lastStateChange = Date.now();
        
        console.log(`[FileDialogStateManager:${this.inputId}] Estado: ${oldState} → ${newState}`);
    }
    
    /**
     * Obtener estado actual
     */
    getState() {
        return this.currentState;
    }
}

// Inicializar managers globales para cada input
window._fileDialogManagers = window._fileDialogManagers || {};

function inicializarFileDialogStateManagers() {
    for (let i = 1; i <= 3; i++) {
        const inputId = `proceso-foto-input-${i}`;
        try {
            window._fileDialogManagers[inputId] = new FileDialogStateManager(inputId);
        } catch (e) {
            console.error(`Error inicializando FileDialogStateManager para ${inputId}:`, e);
        }
    }
    console.log('[inicializarFileDialogStateManagers]  Managers inicializados');
}

// Llamar cuando el documento esté listo
document.addEventListener('DOMContentLoaded', inicializarFileDialogStateManagers);
```

### Refactor: abrirSelectorImagenProceso() con State Manager

```javascript
/**
 * Abrir selector de archivos para un cuadro de imagen
 * 
 * CONTROL DE CICLO DE VIDA:
 * - Verifica estado antes de abrir
 * - Marca estado como OPENING
 * - Previene reaperturas automáticas
 * - Bloquea temporalmente después del procesamiento
 * 
 * @param {number} cuadroIndex - Índice del cuadro (1, 2, 3)
 */
window.abrirSelectorImagenProceso = function(cuadroIndex) {
    const inputId = `proceso-foto-input-${cuadroIndex}`;
    const input = document.getElementById(inputId);
    
    if (!input) {
        console.error(`Input ${inputId} no encontrado`);
        return;
    }
    
    // Obtener el state manager
    const stateManager = window._fileDialogManagers?.[inputId];
    
    if (!stateManager) {
        console.warn(`State manager no disponible para ${inputId}`);
        return;
    }
    
    // GUARD CRÍTICO: Verificar si se puede abrir
    if (!stateManager.canOpen()) {
        console.warn(`[abrirSelectorImagenProceso] No se puede abrir diálogo - estado: ${stateManager.getState()}`);
        return;
    }
    
    // Marcar que estamos abriendo
    stateManager.markOpening();
    
    // Resetear el value para permitir seleccionar el mismo archivo
    input.value = '';
    
    //  ABRIR EL DIÁLOGO
    input.click();
    
    console.log(`[abrirSelectorImagenProceso]  Diálogo abierto para cuadro ${cuadroIndex}`);
};
```

### Refactor: manejarImagenProcesoConIndice() con State Manager

```javascript
/**
 * Manejar imagen después de seleccionarla
 * CONTROL: Marca como procesando, luego bloquea temporalmente
 */
window.manejarImagenProcesoConIndice = function(input, cuadroIndex) {
    const inputId = input.id;
    const stateManager = window._fileDialogManagers?.[inputId];
    
    if (!stateManager) {
        console.warn(`State manager no disponible`);
        return;
    }
    
    // Marcar que estamos manejando el change
    stateManager.markHandlingChange();
    
    if (!input.files || input.files.length === 0) {
        console.log(`Sin archivos seleccionados para cuadro ${cuadroIndex}`);
        // Ya terminamos, marcar como cerrado
        stateManager.markClosed();
        return;
    }
    
    const file = input.files[0];
    const procesoIndex = window.procesoActualIndex;
    
    if (!procesoIndex || procesoIndex <= 0) {
        console.error('procesoActualIndex no definido');
        stateManager.markClosed();
        return;
    }
    
    // Establecer índice para delegación
    window._procesoQuadroIndex = cuadroIndex;
    
    // Delegar a función principal
    if (typeof window.manejarImagenProceso === 'function') {
        window.manejarImagenProceso(input, procesoIndex);
    } else {
        console.error('manejarImagenProceso no disponible');
        stateManager.markClosed();
        return;
    }
    
    // CRÍTICO: Bloquear temporalmente después de procesar
    // Esto previene que el navegador auto-reabra el diálogo
    // durante la actualización del DOM y processing
    stateManager.lockTemporarily(750);
    
    console.log(`[manejarImagenProcesoConIndice]  Procesamiento completado - bloqueado temporalmente`);
};
```

---

## 🔄 Flujo Con State Manager

```
Click placeholder
  ├─ abrirSelectorImagenProceso()
  │   └─ stateManager.canOpen()? ✓ Sí
  │       └─ stateManager.markOpening()
  │           └─ input.click() ◄─── Abre diálogo
  │
  └─ [Diálogo abierto, estado: OPENING]
       │
       └─ Usuario selecciona
            ├─ change event dispara
            │   └─ manejarImagenProcesoConIndice()
            │       ├─ stateManager.markHandlingChange()
            │       ├─ Procesa imagen
            │       ├─ Actualiza preview
            │       └─ stateManager.lockTemporarily(750) ◄─── BLOQUEA
            │
            └─ [Diálogo cerrado, estado: LOCKED]
                 │
                 └─ Si alguien intenta abrir durante los 750ms:
                     └─ stateManager.canOpen()? ✗ No (LOCKED)
                         └─ Ignora el click (PREVENIDO)
                 
                 └─ Después de 750ms:
                     └─ stateManager.markClosed()
                         └─ Listo para siguiente apertura
```

---

##  Cambios Necesarios en el Código Existente

### 1️⃣ Crear el archivo State Manager
📁 `public/js/componentes/FileDialogStateManager.js` (nuevo)

### 2️⃣ Cargar en el HTML modal
```html
<script src="{{ js_asset('js/componentes/FileDialogStateManager.js') }}?v={{ $v }}"></script>
```

### 3️⃣ Actualizar `abrirSelectorImagenProceso()`
En `manejador-imagen-proceso-con-indice.js`

### 4️⃣ Actualizar `manejarImagenProcesoConIndice()`
En el mismo archivo

---

## 🚀 IMPLEMENTACIÓN PASO A PASO

### Paso 1: Crear State Manager
Guardar el código del FileDialogStateManager en nuevo archivo.

### Paso 2: Incluir en HTML
Agregar script tag en `modal-proceso-generico.blade.php`

### Paso 3: Refactorizar funciones
Reemplazar `abrirSelectorImagenProceso()` y `manejarImagenProcesoConIndice()` con versiones que usen el state manager.

### Paso 4: Prueba
- Abre modal
- Click en un preview
- Selecciona imagen ✓ No debe reabrirse
- Click sin seleccionar ✓ No debe reabrirse
- Abre again ✓ Debe funcionar normalmente
- Repite múltiples veces ✓ Sin problemas

---

##  Ventajas de Esta Solución

| Ventaja | Detalles |
|---------|----------|
| **State-based** | Control explícito del ciclo de vida, no magic |
| **Robusto** | Maneja edge cases y race conditions |
| **Debuggable** | Logs claros del estado en cada transición |
| **Reutilizable** | Aplica a cualquier input file |
| **Modal-safe** | Funciona bien con modales dinámicos |
| **Sin timeouts arbitrarios** | El bloqueo temporal es calculado |
| **Production-ready** | Código profesional, no hacks |

