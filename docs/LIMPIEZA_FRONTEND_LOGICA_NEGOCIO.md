# 🧹 LIMPIEZA FRONTEND - Remover Lógica de Negocio Duplicada

## Situación Actual

El backend DDD ya tiene implementado:
- ✅ `Origen::segunTipoCotizacion()` - Regla de origen (Reflectivo/Logo → BODEGA)
- ✅ `ValidarPrendaDomainService` - Todas las validaciones
- ✅ `NormalizarDatosPrendaDomainService` - Todas las transformaciones

Pero el **frontend VIEJO** aún contiene:
- ❌ `aplicarOrigenAutomaticoDesdeCotizacion()` - DUPLICADA en backend
- ❌ `cargarTelasDesdeCtizacion()` - Lógica que debe venir de API
- ❌ `procesarProcesos()` - DUPLICADA en backend
- ❌ Validaciones varias - DUPLICADAS en backend

---

## 📋 Archivos Fronted que Necesitan Limpieza

### 1. `prenda-editor.js` (2438 líneas)
**Estado:** Contiene lógica vieja y duplicada

**Métodos a ELIMINAR:**
- `aplicarOrigenAutomaticoDesdeCotizacion()` - líneas 74-124
  - ✅ Ahora en: `app/Domain/Prenda/DomainServices/AplicarOrigenAutomaticoDomainService.php`
  - ✅ Implementado en: `Origen::segunTipoCotizacion()`
  
- `cargarTelasDesdeCtizacion()` - líneas ~130-250
  - ✅ Ahora: Backend retorna todo vía `GET /api/prendas/{id}`
  - ✅ Ya normalizadas en: `NormalizarDatosPrendaDomainService`

- `procesarProcesos()` - Si existe
  - ✅ Backend lo maneja: `Procesos::desdeArray()`

- Validaciones de negocio (origen, telas, procesos)
  - ✅ Todos en: `ValidarPrendaDomainService::validar()`

**Llamadas a ELIMINAR:**
- Línea 453: `const prendaProcesada = this.aplicarOrigenAutomaticoDesdeCotizacion(prenda);`
- Línea 2367: `const procesada = this.aplicarOrigenAutomaticoDesdeCotizacion(prenda);`

**Plan de Actualización:**
```javascript
// ❌ ANTES: Lógica en frontend
const prenda = { nombre_prenda: "Polo", tipo_cotizacion: "REFLECTIVO" };
const prendaProcesada = prendaEditor.aplicarOrigenAutomaticoDesdeCotizacion(prenda);
// prenda.origen ahora es "bodega" (CALCULADO EN FRONTEND)

// ✅ DESPUÉS: El backend lo retorna ya procesado
const respuesta = await fetch('POST /api/prendas', { data: prenda });
// respuesta.datos.origen ya es "bodega" (CALCULADO EN BACKEND)
```

---

### 2. `prenda-editor-service.js`
**Estado:** Intermedio entre viejo y nuevo

**Verificar si contiene:**
- Lógica de origen → ELIMINAR
- Llamadas a métodos viejos → ACTUALIZAR a API

---

### 3. `prenda-editor-refactorizado.js`
**Estado:** Parcialmente actualizado

**Verificar si delega correctamente a:**
- ✅ `PrendaEditorService` → debe usar `PrendaAPI`
- ✅ `PrendaDOMAdapter` → para UI
- ✅ `PrendaEventBus` → para eventos

---

### 4. `inicializador-origen-automatico.js`
**Estado:** DEBE ELIMINARSE COMPLETAMENTE

**Razón:** Su ÚNICA responsabilidad era aplicar origen automático
- Línea 106: `new PrendaEditor({ cotizacionActual })` - No necesario
- La regla YA está en backend con `Origen::segunTipoCotizacion()`
- El frontend NO debe calcular origen

---

### 5. `item-orchestrator.js`
**Estado:** Usa `PrendaEditor` viejo

**Cambio:** Debe usar `PrendaEditorOrchestrator` en su lugar

```javascript
// ❌ ANTES
this.prendaEditor = new PrendaEditor({ notificationService: ... });

// ✅ DESPUÉS
this.prendaEditor = new PrendaEditorOrchestrator({
    api: new PrendaAPI(),
    eventBus: new PrendaEventBus(),
    domAdapter: new PrendaDOMAdapter()
});
```

---

### 6. `gestion-items-pedido.js`
**Estado:** Usa `PrendaEditor` viejo

**Cambio:** Actualizar a usar `PrendaEditorOrchestrator`

---

## 🎯 Estrategia de Limpieza

### Fase 1: Documentar referencias
- [x] Archivos que usan `PrendaEditor` identificados
- [ ] Archivos que usan métodos específicos identificados

### Fase 2: Actualizar backends de referencias  
```javascript
// PrendaEditorOrchestrator ya está listo:
// - Carga datos del backend ✅
// - No tiene lógica de negocio ✅
// - Maneja eventos ✅
// - Presenta en UI ✅

// Cambio:
// prenda-editor.js → PrendaEditorOrchestrator.js
```

### Fase 3: Eliminar archivo viejo (opcional)
- Opción A: Deprecate `prenda-editor.js` (mantener para compatibilidad)
- Opción B: Mover a `/_deprecated/` 
- Opción C: Eliminar completamente después de actualizar referencias

### Fase 4: Normalizar imports
Todos los archivos deben usar:
```javascript
// Asegurar que están en este orden:
<script src="/js/servicios/prenda-event-bus.js"></script>
<script src="/js/servicios/prenda-api.js"></script>
<script src="/js/servicios/prenda-dom-adapter.js"></script>
<script src="/js/servicios/prenda-editor-orchestrator.js"></script>
```

---

## 🔍 Checklist de Limpieza

### En `prenda-editor.js`:
- [ ] Eliminar método `aplicarOrigenAutomaticoDesdeCotizacion()` completo (línea 74-124)
- [ ] Eliminar llamada en línea 453
- [ ] Eliminar llamada en línea 2367
- [ ] Eliminar método `cargarTelasDesdeCtizacion()` si existe
- [ ] Eliminar cualquier validación de negocio
- [ ] Verificar qué métodos QUEDAN (probablemente solo UI)

### Métodos que DEBEN quedarse en prenda-editor.js (si son solo UI):
```javascript
// Ejemplo de lo que QUEDA (solo UI):
abrirModal(esEdicion, prendaIndex) { /*...*/ }
llenarFormulario(datos) { /*...*/ } 
cerrarModal() { /*...*/ }
```

### Métodos que DEBEN eliminarse (lógica de negocio):
```javascript
// ❌ ELIMINAR - Estos están en backend:
aplicarOrigenAutomaticoDesdeCotizacion()
procesarProcesos()
validarPrenda()
normalizarVariaciones()
```

---

## 📝 Resumen: Antes vs Después

### ANTES (Frontend lleno de lógica)
```
Usuario → Frontend (PrendaEditor)
  ├─ Abre modal
  ├─ Carga prenda
  ├─ Aplica origen automático ← LÓGICA DE NEGOCIO ❌
  ├─ Valida ← LÓGICA DE NEGOCIO ❌
  ├─ Normaliza datos ← LÓGICA DE NEGOCIO ❌
  ├─ Guarda con fetch()
  └─ Presenta resultado
```

### DESPUÉS (Frontend puro, Backend smart)
```
Usuario → Frontend (PrendaEditorOrchestrator)
  ├─ Abre modal
  ├─ Llama API
  │  └─ Backend (GuardarPrendaApplicationService)
  │     ├─ Crea entity
  │     ├─ Aplica origen ✅ LÓGICA DE NEGOCIO
  │     ├─ Valida ✅ LÓGICA DE NEGOCIO
  │     ├─ Normaliza ✅ LÓGICA DE NEGOCIO
  │     └─ Retorna DTO completo
  ├─ Presenta resultado
  └─ Emite evento
```

---

## 🚀 Pasos de Ejecución

### Opción 1: Limpieza Quirúrgica (Recomendado)
1. Crear versión "limpia" de `prenda-editor.js` sin lógica de negocio
2. Actualizar referencias en: `item-orchestrator.js`, `gestion-items-pedido.js`
3. Usar `PrendaEditorOrchestrator` en lugar de `PrendaEditor`
4. Dejar archivo viejo como backup
5. Tests: Verificar que todo funciona igual pero sin lógica duplicada

### Opción 2: Deprecation Gradual
1. Marcar métodos viejo como `@deprecated` con comentarios
2. Mantener funcionalidad pero dejar console.warnings
3. Actualizar archivos que los usan gradualmente
4. Después de 1-2 sprints, eliminar completamente

### Opción 3: Fork + Replace
1. Renombrar `prenda-editor.js` → `prenda-editor-legacy.js`
2. Crear `prenda-editor.js` nuevo que delegue a `PrendaEditorOrchestrator`
3. Actualizar todas las referencias
4. Eliminar `prenda-editor-legacy.js` cuando esté seguro

---

## 📊 Beneficios de la Limpieza

✅ Reglas de negocio en UN SOLO LUGAR (backend)  
✅ Frontend TESTEABLE sin DB/HTTP  
✅ API REUSABLE (same endpoint para web, mobile, CLI)  
✅ MANTENIBILIDAD: Cambios en origen → solo backend  
✅ SEGURIDAD: Validaciones en servidor, no cliente  
✅ PERFORMANCE: Menos lógica en JavaScript  
✅ CLARIDAD: Frontend = UI, Backend = Negocio  

---

## ⚠️ Cuidados Importantes

1. **No eliminar sin verificar referencias** - Usar grep primero
2. **Mantener interfaces de eventos** - Otros componentes escuchan `PRENDA_CARGADA`, etc
3. **Compatibilidad con HTML** - Formularios/inputs deben seguir siendo actualizables
4. **Tests**: Verificar que guardado/carga siga funcionando
5. **Migración gradual**: No hacer TODO de una vez

---

## 📞 Próximo Paso

¿Deseas que proceda con:
1. **Limpiar `prenda-editor.js`** - Eliminar métodos viejos
2. **Actualizar referencias** - En otros archivos
3. **Crear guía de migración** - Para otros componentes
4. Todo

