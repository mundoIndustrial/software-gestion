# 📊 ANÁLISIS EXHAUSTIVO: crear-pedido-nuevo.blade.php

## 🔍 Estadísticas Globales
- **Total de líneas:** 768
- **Estructura:** Blade template + @push('scripts')
- **Estado:** POST-LIMPIEZA (eliminado bloque guardarComoBorradorLegacy)

---

## 🗂️ ESTRATIFICACIÓN DEL ARCHIVO

### **Capa 1: HTML/Blade - Líneas 1-204**
```
1-11:        @extends + @section('extra_styles') - 11 líneas CSS links
12-204:      Markup HTML del formulario - 193 líneas
```
✅ ESTADO: Limpio. No hay deuda técnica.

---

### **Capa 2: JavaScript (@push scripts) - Líneas 206-768** (563 líneas)

#### **2.1 Sección: Inline Script #1 - Conditional Data (Líneas 33-100)**
```javascript
@if($modoEdicion ?? false)
    <script>
        window.modoEdicion = true
        window.pedidoEditarId = {{ $pedidoEditarId }}
        window.pedidoEditarData = {!! json_encode([...]) !!}
```
**Líneas:** 33-100 (67 líneas)
**Tipo:** Configuration/data injection
**Severidad:** LOW - Es necesario en Blade (datos del servidor)
**Acción:** MANTENER

---

#### **2.2 Sección: Defer Scripts - Bases (Líneas 213-223)**
```javascript
logger-app.js
event-bus.js              ← Shared services
format-detector.js
shared-prenda-*.js (4 servicios)
initialization-helper.js
```
**Líneas:** 6 scripts defer
**Tipo:** Utilities y servicios compartidos
**Severidad:** LOW - Bien organizados
**Acción:** MANTENER

---

#### **2.3 Sección: Inline Script #2 - PrendasEditorHelper (Líneas 226-233)**
```javascript
document.addEventListener('DOMContentLoaded', function() {
    PrendasEditorHelper.inicializar().catch(err => {
        console.error('[crear-nuevo] Error:', err);
    });
});
```
**Líneas:** 8
**Tipo:** Helper initialization
**Descripción:** Inicializa PrendasEditorHelper cuando el DOM está listo
**Severidad:** LOW - Único responsable
**Acción:** MANTENER (es legítimo inicializar un helper aquí)

---

#### **2.4 Sección: Defer Scripts - EPP & Core (Líneas 235-356)**
```javascript
EPP Services:         ~20 scripts defer
Core (Tallas, Telas): ~15 scripts defer
Total:                ~35 scripts defer
```
**Líneas:** 122 líneas (solo declaraciones)
**Tipo:** Modular services (bien organizados)
**Severidad:** LOW
**Acción:** MANTENER

---

#### **2.5 Sección: Inline Script #3 - Image Storage Init (Líneas 242-256)**
```javascript
document.addEventListener('DOMContentLoaded', function() {
    if (!window.imagenesPrendaStorage) {
        window.imagenesPrendaStorage = new ImageStorageService(3);
    }
    if (!window.imagenesTelaStorage) { ... }
    if (!window.imagenesReflectivoStorage) { ... }
});
```
**Líneas:** 15
**Tipo:** Storage initialization
**Severidad:** MEDIUM - El código es simple pero repetitivo
**Acción:** CANDIDATO - Se puede extraer a `image-storage-initializer.js`

---

#### **2.6 Sección: Inline Script #4 - EPP Menu Handlers (Líneas 301-309)**
```javascript
document.addEventListener('DOMContentLoaded', function() {
    console.log('[crear-pedido-nuevo] Inicializando EPP Menu Handlers...');
    if (typeof window.eppMenuHandlerTarjeta !== 'undefined') {
        console.log('[crear-pedido-nuevo] EPP Menu Handlers inicializado correctamente');
    } else {
        console.error('[crear-pedido-nuevo] eppMenuHandlerTarjeta no está disponible');
    }
});
```
**Líneas:** 9
**Tipo:** Initialization check
**Severidad:** LOW - Solo verifica disponibilidad
**Acción:** MANTENER (opcional: mover a debug.js si hay modo debug)

---

#### **2.7 Sección: HYBRID LAYER #1 - Borrador Serializer (Líneas 360-601)**
```javascript
if (!window.DraftPedidoSerializer) {
    window.sincronizarPrendaModalAntesDeGuardarBorrador = function() { 
        // 62 líneas
    }
    window.serializarPrendaExistenteParaBorrador = function(prenda, prendaIndex, formData) {
        // ~200 líneas
    }
}
```

**Líneas:** 241 líneas
**Tipo:** LEGACY/HYBRID - Funciones que debían estar en un módulo
**Severidad:** HIGH 🚨 - Código de lógica de negocio en Blade
**¿Se usan en múltiples lugares?:** 
   - `sincronizarPrendaModalAntesDeGuardarBorrador()` → Usada en `orchestrator.js`
   - `serializarPrendaExistenteParaBorrador()` → Usada en `orchestrator.js` (probablemente)
**Acción:** ⚠️ **REFACTOR CRÍTICO** → Mover a `draft-pedido-serializer-helpers.js`

**¿Por qué está acá?** Probablemente porque se definió en Blade para ser accesible globalmente, pero ahora que existe orchestrator, debería estar en módulo.

---

#### **2.8 Sección: Global Variables Config (Líneas 361-362)**
```javascript
window.routeGuardarBorradorUrl = '{{ route("...") }}';
window.routePedidosIndexUrl = '{{ route("...") }}';
```
**Líneas:** 2
**Tipo:** Route configuration
**Severidad:** LOW - Necesarias en Blade (server routes)
**Acción:** MANTENER (o mover a data attribute en HTML si se quiere ser purista)

---

#### **2.9 Sección: Global Variables - Utils (Línea 602)**
```javascript
window.asesorActualNombre = '{{ Auth::user()->name ?? '' }}';
```
**Líneas:** 1
**Tipo:** User configuration
**Severidad:** LOW
**Acción:** MANTENER

---

#### **2.10 Sección: MEGA DOMCONTENTLOADED (Líneas 604-756)**
```javascript
document.addEventListener('DOMContentLoaded', function() {
    // 1. Asesora setup (2 líneas)
    // 2. Uppercase input handler (65 líneas) ← EXTRACTABLE
    // 3. Mostrar botones (6 líneas)
    // 4. Ocultar loading (10 líneas)
    // 5. Gestión de items (60+ líneas) ← EXTRACTABLE
    // 6. Event listeners inline (50+ líneas) ← EXTRACTABLE
    // Total: 153 líneas
});
```

**Líneas:** 153
**Tipo:** MIXED - Múltiples responsabilidades
**Severidad:** HIGH 🚨 - "God function"
**Acción:** REFACTOR URGENTE → Split en 3-4 módulos

---

#### **2.11 Sección: Script de Borrador Orchestrator (Líneas 750-757)**
```javascript
// Asignar evento al botón cuando se cargue el DOM
document.addEventListener('DOMContentLoaded', function() {
    if (window.DraftPedidoOrchestrator && typeof window.DraftPedidoOrchestrator.registrarBotonGuardarBorrador === 'function') {
        window.DraftPedidoOrchestrator.registrarBotonGuardarBorrador();
    }
});
```

**Líneas:** 8
**Tipo:** Orchestrator binding
**Severidad:** LOW - Bien hecho ✅
**Acción:** MANTENER

---

#### **2.12 Sección: Final UI Script Loads (Líneas 760-767)**
```javascript
3 defer scripts:
- item-card-interactions.js
- prenda-editor-modal.js
- drag-drop-procesos-estilo-prenda.js

Debug script (condicional):
- prenda-editor-test.js
```

**Líneas:** 8
**Tipo:** Final UI module loading
**Severidad:** LOW
**Acción:** MANTENER

---

## 📈 RESUMEN DE DEUDA TÉCNICA

| Bloque | Líneas | Severidad | Tipo | Acción |
|--------|--------|-----------|------|--------|
| 2.7 - Serializer Helpers | 241 | 🔴 HIGH | Lógica en Blade | Mover a módulo |
| 2.10 - Mega DOMContentLoaded | 153 | 🔴 HIGH | God function | Separar en 3-4 |
| 2.5 - Image Storage Init | 15 | 🟡 MEDIUM | Repetitivo | Extraer (opcional) |
| **TOTAL DEUDA** | **409** | | |  |

---

## 🎯 PLAN DE REFACTORING (Prioridad)

### **Fase 1 (CRÍTICA - 1-2 hrs)**
```
✅ Extraer 2.7 (Serializer Helpers) a draft-pedido-serializer-helpers.js
  - sincronizarPrendaModalAntesDeGuardarBorrador()
  - serializarPrendaExistenteParaBorrador()
```

### **Fase 2 (IMPORTANTE - 2-3 hrs)**
```
✅ Separar 2.10 (Mega DOMContentLoaded) en módulos:
  a) input-formatter.js - Uppercase handler (65 líneas)
  b) items-manager-init.js - Gestión de items (60+ líneas)
  c) ui-event-binders.js - Event listeners (50+ líneas)
  d) leave-button-setup.js - Botones visibility (6 líneas)
```

### **Fase 3 (OPCIONAL - 30 min)**
```
✅ Extraer 2.5 (Image Storage Init) a image-storage-initializer.js
```

---

## 📋 DETALLES DE 2.10 (Mega DOMContentLoaded)

### Subsecciones identificadas:

**A) Asesora Setup (línea ~604)**
```javascript
document.getElementById('asesora_editable').value = '{{ Auth::user()->name ?? '' }}';
```
- 1 línea trivial
- ELIMINAR (el servidor debería hacerlo en template/form)

**B) Uppercase Input Handler (línea ~609-668)**
```javascript
function setupUpperCaseInput(inputId) {
    // 65 líneas de lógica de conversión a mayúsculas
    setupUpperCaseInput('cliente_editable');
    setupUpperCaseInput('asesora_editable');
    setupUpperCaseInput('forma_de_pago_editable');
    setupUpperCaseInput('observaciones_editable');
}
```
- **EXTRACTABLE:** `js/modulos/crear-pedido/ui/input-formatter.js`
- Inicializar con: `InputFormatter.setupUpperCase(['cliente', 'asesora', 'forma_de_pago', 'observaciones'])`

**C) Botón Setup (línea ~669-677)**
```javascript
const btnSubmit = document.getElementById('btn-submit');
btnSubmit.textContent = '✓ Crear Pedido';
btnSubmit.style.display = 'block';

const tipoPedidoLoading = document.getElementById('tipo-pedido-loading');
const tipoPedidoSelect = document.getElementById('tipo_pedido_nuevo');
// ...setTimeout setup...
```
- **EXTRACTABLE:** `js/modulos/crear-pedido/ui/leave-button-setup.js`

**D) Items Section Setup (línea ~685-710)**
```javascript
const selectTipoPedidoNuevo = document.getElementById('tipo_pedido_nuevo');
const seccionItems = document.getElementById('seccion-items-pedido');
if (seccionItems) { seccionItems.style.display = 'block'; }
// ...addEventListener para btnAgregarItemTipoInline...
```
- **EXTRACTABLE:** `js/modulos/crear-pedido/init/items-dropdown-init.js`

**E) Event Listeners (línea ~710-756)**
```javascript
btnAgregarItemTipoInline.addEventListener('click', function(e) {
    // 50+ líneas de lógica
    if (tipoPedido === 'P') {
        window.abrirModalPrendaNueva();
    } else if (tipoPedido === 'EPP') {
        window.abrirModalAgregarEPP();
    }
});
window.manejarCambiaTipoPedido = function() { ... };
```
- **EXTRACTABLE:** `js/modulos/crear-pedido/init/item-type-handlers.js`

---

## 🔗 Dependencias actuales (en Blade)

```
window.gestionItemsUI
window.prendaFormCollector
window.prepararDatosParaEnvio()
window.abrirModalPrendaNueva()
window.abrirModalAgregarEPP()
window.eppMenuHandlerTarjeta
window.DraftPedidoOrchestrator
window.DraftPedidoBuilder
window.DraftPedidoSaveService
window.DraftPedidoSerializer
```

Todas están siendo inyectadas por otros módulos defer. ✅ Buen patrón.

---

## ✅ RECOMENDACIÓN FINAL

**No es necesario limpiar TODO ahora.**

### Prioridades:
1. **Fase 1:** Extraer serializer helpers (CRÍTICO - afecta a orchestrator)
2. **Fase 2:** Separar mega DOMContentLoaded (IMPORTANTE - mejor mantenimiento)
3. **Dejar:** Las inicializaciones simples (storage, helpers) están bien

### Línea de acción recomendada:

```
Hoy: ✅ Extraer 2.7 → 240 líneas sacadas
Mañana: ✅ Refactorizar 2.10 → 150 líneas mejoradas
Resultado: 390 líneas eliminadas, Blade down to ~380 líneas efectivas
```
