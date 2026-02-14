#  ERRORES CRÍTICOS A EVITAR & SEÑALES DE ESTABILIDAD

## ERRORES CRÍTICOS QUE ROMPEN TODO

###  ERROR 1: No esperar a que el DOM esté listo

**MALO:**
```javascript
// En DOMContentLoaded, ANTES de que modal-mini-fsm.js cargue
window.DragDropManager.inicializar();
```

**POR QUÉ FALLA:**
- `window.__MODAL_FSM__` no existe todavía
- Los handlers de DragDrop intentan acceder a elementos que no existen

**CORRECTO:**
- DragDropManager SOLO se inicializa en `abrirModalAgregarPrendaNueva()` **DESPUÉS** de que modal-mini-fsm.js esté cargado

###  ERROR 2: Múltiples handlers del mismo evento

**MALO:**
```javascript
// En Blade
modal.addEventListener('shown.bs.modal', () => { ... });
modal.addEventListener('shown.bs.modal', () => { ... });  // DUPLICADO
```

**POR QUÉ FALLA:**
- Se ejecutan múltiples veces
- Los listeners no se limpian al cerrar

**CORRECTO:**
- Usar `ModalMiniFSM.onStateChange()` para escuchar cambios
- Registrar listeners en un punto único: `_registrarListenersModal()`

###  ERROR 3: Crear múltiples instancias de DragDropManager

**MALO:**
```javascript
// En archivo A
window.DragDropManager = new DragDropManager();

// En archivo B
window.DragDropManager = new DragDropManager();  // SOBRESCRIBE
```

**POR QUÉ FALLA:**
- Guard clause de inicializado se pierde
- Se intenta inicializar handlers ya inicializados

**CORRECTO:**
```javascript
// Crear UNA SOLA vez
if (!window.DragDropManager) {
    window.DragDropManager = new DragDropManager();
}

// Luego solo llamar a inicializar()
window.DragDropManager.inicializar();  // Guard clause lo detiene si ya está inicializado
```

###  ERROR 4: No sincronizar catálogos con apertura del modal

**MALO:**
```javascript
abrirModalAgregarPrendaNueva() {
    // Abrir modal PRIMERO
    this.prendaEditor.abrirModal(false, null);
    
    // Cargar catálogos DESPUÉS (todavía no estaban en DOM cuando se abrió)
    await window.cargarCatalogosModal();
}
```

**POR QUÉ FALLA:**
- El modal intenta renderizar campos de datalist que aún no tienen opciones
- Se cargan opciones de telas/colores DESPUÉS de que el usuario puede interactuar

**CORRECTO:**
```javascript
abrirModalAgregarPrendaNueva() {
    // Cargar catálogos PRIMERO
    await window.cargarCatalogosModal();
    
    // Abrir modal DESPUÉS (datalists ya tienen datos)
    this.prendaEditor.abrirModal(false, null);
}
```

###  ERROR 5: Usar setTimeout para sincronización

**MALO:**
```javascript
setTimeout(() => {
    window.DragDropManager.inicializar();
}, 100);  // ¿Por qué 100? ¿Y si es cliente lento?
```

**POR QUÉ FALLA:**
- Timeout arbitrario, no garantiza que DOM esté listo
- En clientes lentos, 100ms es insuficiente
- En clientes rápidos, espera innecesariamente

**CORRECTO:**
```javascript
// Esperar a que DOM esté REALMENTE listo
await this._esperarModalVisible(1500);  // timeout seguro pero con fallback
```

###  ERROR 6: No limpiar listeners al cerrar modal

**MALO:**
```javascript
modalElement.addEventListener('shown.bs.modal', handler1);
modalElement.addEventListener('shown.bs.modal', handler2);
// ... cerrar modal sin remover listeners ...
// Abrir modal de nuevo
// Todos los listeners VUELVEN A EJECUTARSE (x2)
```

**POR QUÉ FALLA:**
- Memory leak
- Listeners se acumulan
- Doble/triple ejecución en aperturas sucesivas

**CORRECTO:**
```javascript
// Guardar referencias
const listeners = [];

// Registrar
const handler = () => { ... };
modalElement.addEventListener('shown.bs.modal', handler);
listeners.push({ element: modalElement, event: 'shown.bs.modal', handler });

// Desregistrar al cerrar
listeners.forEach(({ element, event, handler }) => {
    element.removeEventListener(event, handler);
});
```

###  ERROR 7: Depender del orden de carga de scripts

**MALO:**
```html
<!-- prenda-editor.js intenta usar DragDropManager -->
<script src="prenda-editor.js"></script>
<!-- Pero DragDropManager todavía no existe -->
<script src="drag-drop-manager.js"></script>
```

**POR QUÉ FALLA:**
- Errores "undefined is not a function"
- Comportamiento impredecible

**CORRECTO:**
```html
<!-- Orden correcto: dependencias primero -->
<script src="drag-drop-manager.js"></script>
<script src="prenda-editor.js"></script>
<script src="modal-mini-fsm.js"></script>
```

###  ERROR 8: No validar estado de FSM antes de inicializar

**MALO:**
```javascript
async abrirModal() {
    // No comprobar si ya está abierto
    await cargarCatalogos();
    abrirModalVisualmente();
}

// Usuario hace doble clic
abrirModal();  // Llamada 1: OPENING
abrirModal();  // Llamada 2: También OPENING (sin guard)
```

**POR QUÉ FALLA:**
- Cargas de catálogo duplicadas
- Dos "aperturas" visuales simultáneas

**CORRECTO:**
```javascript
async abrirModal() {
    const fsm = window.__MODAL_FSM__;
    
    // Guard: verificar estado
    if (!fsm.puedeAbrir()) {
        console.warn('Modal ya está abriendo/abierto');
        return;  // Detener llamada duplicada
    }
    
    fsm.cambiarEstado('OPENING');
    // ... resto ...
}
```

---

##  SEÑALES DE QUE EL SISTEMA ESTÁ ESTABLE

### Verde 1: Logs limpios sin duplicados

**Frecuencia:** Primera apertura del modal

```
[ModalFSM]  Transición: CLOSED → OPENING
[abrirModalAgregarPrendaNueva] FASE 1: Iniciando
[abrirModalAgregarPrendaNueva] FASE 2: Cargando catálogos...
[Telas] Iniciando carga de telas disponibles...
[Telas] Respuesta de API: { success: true, count: 48 }
[Telas] Telas cargadas en memoria: 48  ← UNA SOLA VEZ
[Colores] Iniciando carga de colores disponibles...
[Colores] Respuesta de API: { success: true, count: 32 }
[Colores] Colores cargados en memoria: 32  ← UNA SOLA VEZ
[abrirModalAgregarPrendaNueva]  Catálogos cargados
[abrirModalAgregarPrendaNueva] FASE 4: Esperando visible...
[_esperarModalVisible]  Modal visible
[abrirModalAgregarPrendaNueva] FASE 5: Inicializando DragDropManager
[DragDropManager]  Ya inicializado (ignorando) ← O solo "inicializado"
[abrirModalAgregarPrendaNueva]  DragDropManager inicializado
[ModalFSM]  Transición: OPENING → OPEN
[abrirModalAgregarPrendaNueva]  ÉXITO
```

**Cosas a validar:**
-  Cada log aparece 1 sola vez (no repetido)
-  Transiciones FSM son ordenadas: CLOSED → OPENING → OPEN
-  Telas/Colores cargan una sola vez cada uno
-  No hay `[Error]` o `[]` en rojo

---

### Verde 2: Guard clause previene dobles aperturas

**Cómo reproducir:**
1. Abrir modal
2. Mientras se abre, hacer clic otra vez (INMEDIATAMENTE)

```
[ModalFSM]  CLOSED → OPENING
[abrirModalAgregarPrendaNueva] INICIO
[ModalFSM]  Modal no puede abrir ahora (estado: OPENING)
[abrirModalAgregarPrendaNueva]  Modal ya está en estado: OPENING. Ignorando llamada.
```

**Cosas a validar:**
-  Segunda llamada NO dispara `[abrirModalAgregarPrendaNueva] INICIO`
-  Solo aparece UNA carga de catálogos (no dos)
-  No hay error, solo warning

---

### Verde 3: Drag & Drop funciona correctamente

**Cómo reproducir:**
1. Abrir modal
2. Hacer drag/drop en cualquier zona (Prenda, Tela, Proceso)
3. O pegar imagen (Ctrl+V)

**Esperado en consola:**
```
[DragDrop]  EVENTO PASTE DETECTADO
[DragDrop] Modal visible: true
[DragDrop]  Procesando pegado global...
```

**Cosas a validar:**
-  Se reconoce la imagen
-  Se carga sin errores
-  Preview se actualiza

---

### Verde 4: No hay memory leaks

**Cómo reproducir:**
1. Abrir DevTools → Memory tab
2. Tomar snapshot (Take snapshot)
3. Abrir modal
4. Cerrar modal
5. Repetir pasos 3-4 diez veces (10 aperturas/cierres)
6. Tomar otro snapshot

**Esperado:**
- Memoria se mantiene estable
- No hay crecimiento logarítmico

**Si hay memory leak:**
```
Heap size después de 10 ciclos: 150 MB (debería mantenerse en ~40 MB)
```

**Causa probable:**
- Listeners no se removieron
- Referencias a DOM no se limpiaron

---

### Verde 5: Error rate en logs del servidor

**Cómo monitorear:**
```bash
# En servidor
tail -f storage/logs/laravel.log | grep -i "error"  # Dev
# En producción, revisar herramientas de logging (Sentry, etc)
```

**Esperado:**
- No hay nuevos errores relacionados con modal
- Error rate se mantiene en línea base histórica

---

### Verde 6: Performance acceptable

**Benchmark:**
```javascript
// En consola:
const start = performance.now();
// Hacer clic en "Agregar prenda"
// Esperar a que modal esté completamente visible
// En consola, ejecutar:
console.log('Tiempo total:', performance.now() - start, 'ms');
```

**Esperado:**
- < 500ms en computadora normal
- < 800ms en cliente lento

**Si es > 1000ms:**
- Problema de performance (probablemente en catálogos)
- Revisar speed de API

---

### Verde 7: Tests manuales en navegadores

**Navegadores a validar:**
- Chrome (última versión)
- Firefox (última versión)
- Edge (última versión)
- Safari (si es posible)
- Mobile (Chrome Android)

**Test en cada navegador:**
1. Abrir modal
2. Hacer drag/drop
3. Editar prenda
4. Abrir/cerrar múltiples veces
5. Cambiar de pestaña y volver

**Esperado:** Todo funciona sin errores específicos del navegador

---

## 🚨 SIGNOS DE QUE ALGO ESTÁ MAL

###  Rojo 1: Fetch duplicado

```
[Telas] Respuesta de API...
[Telas] Respuesta de API...  ← DUPLICADO
```

**Causa probable:**
- `cargarCatalogosModal()` se llama múltiples veces
- FSM no está bloqueando bien

**Solución:**
- Verificar que `window.__MODAL_FSM__` existe
- Verificar que guard clause en `abrirModalAgregarPrendaNueva()` funciona

---

###  Rojo 2: "Sistema ya inicializado" pero sigue inicializando

```
[DragDropManager]  Ya inicializado
[DragDropManager] Iniciando inicialización...  ← ¿qué?
```

**Causa probable:**
- Guard clause de `inicializar()` no retorna correctamente
- Código después del guard se ejecuta de todas formas

**Solución:**
```javascript
inicializar() {
    if (this.inicializado) {
        console.log('Ya inicializado');
        return this;  // ← DEBE RETORNAR
    }
    // ... resto ...
}
```

---

###  Rojo 3: Modal no abre

```
[ModalFSM]  CLOSED → OPENING
[abrirModalAgregarPrendaNueva] FASE 1
[ModalFSM] Modal no puede abrir
```

**Causa probable:**
- `_esperarModalVisible()` timed out
- `this.prendaEditor` no existe
- PrendaModalManager no disponible

**Solución:**
- Verificar que PrendaEditor.js está cargado
- Aumentar timeout a 2000ms
- Revisar que `new GestionItemsUI()` inicializa `this.prendaEditor`

---

###  Rojo 4: Listeners se duplican (se ejecutan x2)

```
// Primera apertura: OK
// Segunda apertura: handler se ejecuta 2 veces
// Tercera apertura: handler se ejecuta 3 veces
```

**Causa probable:**
- Listeners se registran pero no se limpian
- Modal reutiliza el mismo elemento sin limpiar listeners anteriores

**Solución:**
- Usar `ModalListenerRegistry.unregisterAll()` al cerrar modal
- O remover listeners manualmente:
```javascript
element.removeEventListener(event, handler);
```

---

## 📊 COMANDO DE DEBUGGING COMPLETO

Copiar/pegar en consola (se ejecuta todo de una vez):

```javascript
(function() {
    console.group('=== AUDITORÍA SISTEMA MODAL ===');
    
    const fsm = window.__MODAL_FSM__;
    const ddm = window.DragDropManager;
    
    console.table({
        'FSM cargado': !!fsm,
        'FSM estado': fsm?.obtenerEstado?.() || 'N/A',
        'DragDropManager cargado': !!ddm,
        'DragDropManager.inicializado': ddm?.inicializado || false,
        'telasDisponibles.length': window.telasDisponibles?.length || 0,
        'coloresDisponibles.length': window.coloresDisponibles?.length || 0,
        'PrendaEditor disponible': typeof window.PrendaEditor !== 'undefined',
        'GestionItemsUI disponible': typeof window.GestionItemsUI !== 'undefined'
    });
    
    if (fsm) {
        console.log('🔍 Estado FSM:');
        console.table(fsm.obtenerDebug());
    }
    
    if (fsm?._historialCambios) {
        console.log('📜 Historial FSM (últimos 5 cambios):');
        console.table(fsm._historialCambios.slice(-5));
    }
    
    console.groupEnd();
})();
```

---

**Documento:** Guía de Debugging y Validación  
**Última actualización:** 2026-02-13  
**Nivel:** Production Ready
