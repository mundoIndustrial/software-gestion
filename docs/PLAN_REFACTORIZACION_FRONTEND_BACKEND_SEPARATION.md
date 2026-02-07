# 📋 PLAN DE REFACTORIZACIÓN: Separación Frontend/Backend (DDD)

**Fecha:** 7 Febrero 2026  
**Objetivo:** Eliminar lógica de negocio acoplada en el frontend  
**Estado:** ✅ Backend LISTO | ⚠️ Frontend ACOPLADO

---

## 📊 ANÁLISIS ACTUAL

### ✅ BACKEND - YA TIENE TODA LA LÓGICA (DDD Implementado)

| Funcionalidad | Backend | Ubicación | Estado |
|---------------|---------|-----------|--------|
| **Crear Prenda** | ✅ Sí | `GuardarPrendaApplicationService` | Completo |
| **Actualizar Prenda** | ✅ Sí | `ActualizarPrendaCompletaUseCase` | Completo |
| **Validar Prenda** | ✅ Sí | `ValidarPrendaDomainService` | Completo |
| **Crear Tipos Manga** | ✅ Sí | `PedidoController::crearObtenerTipoManga()` | Completo |
| **Aplicar Origen Automático** | ✅ Sí | `AplicarOrigenAutomaticoDomainService` | Completo |
| **Normalizar Datos** | ✅ Sí | `NormalizarDatosPrendaDomainService` | Completo |
| **Guardar Novedades** | ✅ Sí | `ActualizarPrendaCompletaUseCase::guardarNovedad()` | Completo |
| **Gestionar Imágenes** | ✅ Sí | `ActualizarPrendaCompletaUseCase` | Completo |

### ❌ FRONTEND - TIENE LÓGICA QUE NO DEBERÍA

| Código | Líneas | Lógica Acoplada | ¿Backend? | Severidad |
|--------|--------|-----------------|-----------|-----------|
| **gestion-items-pedido.js** | 497-527 | ⚠️ Crear tipos de manga | ✅ Existe ruta | MEDIA |
| **gestion-items-pedido.js** | 568-582 | ⚠️ Determinar CREATE vs EDIT | ✅ Backend debería hacerlo | ALTA |
| **gestion-items-pedido.js** | 613-625 | ⚠️ Lógica manipulación imágenes | ✅ Backend maneja | ALTA |
| **gestion-items-pedido.js** | 485-488 | ⚠️ Validación de tallas | ✅ Backend valida | MEDIA |
| **gestion-items-pedido.js** | 580-595 | ⚠️ Lógica de novedades | ✅ Backend maneja | ALTA |

---

## 🔴 PROBLEMAS ENCONTRADOS

### 1️⃣ **Creación de Tipos de Manga en Frontend**
```javascript
// ❌ PROBLEMA: Frontend hace llamada directa
fetch('/asesores/api/tipos-manga', { method: 'POST', ... })
```

**Impacto:** 
- Acoplamiento a ruta de API
- Lógica de creación duplicada (backend + frontend)
- Difícil de mantener

**Backend YA lo hace:** ✅ `PedidoController::crearObtenerTipoManga()`

---

### 2️⃣ **Determinación de CREATE vs EDIT en Frontend**
```javascript
// ❌ PROBLEMA: Frontend decide la operación
const esNuevaDesdeCotz = this.prendaEditor?.esNuevaPrendaDesdeCotizacion === true;
const esEdicionReal = this.prendaEditIndex !== null;
const vamosAEditar = esEdicionReal && !esNuevaDesdeCotz;
```

**Impacto:**
- Lógica de negocio en UI
- Frágil a cambios en estructura de datos
- Difícil de testear

**Backend YA lo hace:** ✅ Detecta automáticamente en `GuardarPrendaApplicationService`

---

### 3️⃣ **Manipulación de Imágenes Según Estado**
```javascript
// ❌ PROBLEMA: Frontend decide eliminación
if (esModoCreate && seEliminaronTodasLasImagenes) {
    prendaData.imagenes = [];  // Manipular en crear
}
```

**Impacto:**
- Backend recibe datos inconsistentes
- Duplicación de lógica
- Riesgo de inconsistencias

**Backend YA lo hace:** ✅ `ActualizarPrendaCompletaUseCase` maneja `imagenesAEliminar`

---

### 4️⃣ **Validación de Tallas en Frontend**
```javascript
// ❌ PROBLEMA: Frontend valida
const tieneTallas = Object.values(cantidad_talla).some(...);
if (!tieneTallas) return error;
```

**Impacto:**
- Validaciones duplicadas
- API puede recibir datos inválidos sin validar

**Backend YA lo hace:** ✅ `ValidarPrendaDomainService::validar()`

---

### 5️⃣ **Lógica de Novedades en Frontend**
```javascript
// ❌ PROBLEMA: Frontend maneja modal + lógica
await window.modalNovedadEditacion.mostrarModalYActualizar(...)
```

**Impacto:**
- Lógica de cambios dispersa
- Difícil de auditar
- Inconsistencias en registro

**Backend YA lo hace:** ✅ `ActualizarPrendaCompletaUseCase::guardarNovedad()`

---

## ✅ SOLUCIÓN PROPUESTA

### FASE 1: Eliminar Lógica de Tipos de Manga (MEDIA, 1hr)

**Cambio:**
```javascript
// ❌ ANTES: Frontend crea tipo de manga
if (prendaData.variantes?.tipo_manga_crear) {
    await fetch('/asesores/api/tipos-manga', { ... })
}

// ✅ DESPUÉS: Backend maneja TODO
prendaData.variantes.tipo_manga_crear = true;  // Solo indicio
prendaData.variantes.tipo_manga = "Corta";      // Nombre sugerido

// Enviar directamente a guardarPrenda
await ItemAPIService.guardarPrenda(prendaData);  
// Backend detecta y crea si no existe
```

**Backend:** Modificar `GuardarPrendaApplicationService` para crear tipos de manga

---

### FASE 2: Unificar CREATE/EDIT (ALTA, 2hrs)

**Cambio:**
```javascript
// ❌ ANTES: Frontend decide
const vamosAEditar = esEdicionReal && !esNuevaDesdeCotz;
if (vamosAEditar) {
    // Editar...
} else {
    // Crear...
}

// ✅ DESPUÉS: Frontend solo envía datos, backend decide
const response = await ItemAPIService.guardarPrenda(prendaData);
// Backend retorna { operacion: 'create'|'update', ... }
```

**Backend:** Ya lo hace en `GuardarPrendaApplicationService`

---

### FASE 3: Remover Manipulación de Imágenes (ALTA, 1.5hrs)

**Cambio:**
```javascript
// ❌ ANTES: Frontend manipula arrays
if (esModoCreate && seEliminaronTodasLasImagenes) {
    prendaData.imagenes = [];
}

// ✅ DESPUÉS: Solo marcar para eliminación
prendaData.imagenesAEliminar = [id1, id2, id3];  // IDs a eliminar
// Backend maneja la lógica
```

**Backend:** Ya acepta en DTO `ActualizarPrendaCompletaDTO::imagenesAEliminar`

---

### FASE 4: Centralizar Validaciones (MEDIA, 1hr)

**Cambio:**
```javascript
// ❌ ANTES: Frontend valida
if (!tieneTallas) { notificar error; return; }

// ✅ DESPUÉS: Backend retorna errores detallados
try {
    await ItemAPIService.guardarPrenda(prendaData);
} catch(error) {
    // Backend retorna: { errores: ['No hay tallas', ...] }
    mostrarErrores(error.errores);
}
```

**Backend:** Ya valida en `ValidarPrendaDomainService`

---

### FASE 5: Simplificar Flujo de Novedades (ALTA, 2hrs)

**Cambio:**
```javascript
// ❌ ANTES: Frontend maneja modal + API
await window.modalNovedadEditacion.mostrarModalYActualizar(...)

// ✅ DESPUÉS: Backend maneja TODO
const novedad = {
    descripcion: "Se cambió color a rojo",
    usuario_id: window.usuarioActual.id,
    timestamp: new Date()
};

prendaData.novedad = novedad;
await ItemAPIService.guardarPrenda(prendaData);
// Backend lo registra automáticamente
```

**Backend:** Ya acepta.en DTO `ActualizarPrendaCompletaDTO::novedad`

---

## 📝 IMPLEMENTACIÓN

### Paso 1: Adaptar ItemAPIService

```javascript
// En item-api-service.js
async guardarPrenda(prendaData) {
    const endpoint = prendaData.id 
        ? `/api/prenda/${prendaData.id}` 
        : '/api/prenda';
    
    const method = prendaData.id ? 'PUT' : 'POST';
    
    return fetch(endpoint, {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(prendaData)
    }).then(r => r.json());
}
```

### Paso 2: Simplificar frontend `agregarPrendaNueva()`

```javascript
async agregarPrendaNueva() {
    // 1. Recolectar datos (igual)
    const prendaData = window.prendaFormCollector
        .construirPrendaDesdeFormulario(...);
    
    // 2. Guardar via API (SIMPLE)
    try {
        await this.apiService.guardarPrenda(prendaData);
        this.notificationService.exito('Prenda guardada');
        this.cerrarModalAgregarPrendaNueva();
    } catch(error) {
        this.notificationService.error(error.message);
    }
}
```

### Paso 3: Backend ya está listo

- ✅ `GuardarPrendaApplicationService` maneja CREATE/UPDATE
- ✅ `ActualizarPrendaCompletaUseCase` maneja novedades
- ✅ Validaciones en domain services
- ✅ Creación de tipos de manga

---

## 🎯 BENEFICIOS

| Aspecto | Antes | Después | Mejora |
|--------|-------|---------|--------|
| **Líneas acopladas** | ~80 | ~5 | 93% ↓ |
| **Testabilidad** | Baja | Alta | ✅ |
| **Mantenibilidad** | Difícil | Fácil | ✅ |
| **Siguiendo DDD** | No | Sí | ✅ |
| **Consistencia datos** | Baja | Alta | ✅ |
| **Seguridad** | Baja | Alta | ✅ |

---

## 📅 CRONOGRAMA

| Fase | Duración | Prioridad | Estado |
|------|----------|-----------|--------|
| **1. Tipos de Manga** | 1h | MEDIA | 🔴 POR HACER |
| **2. CREATE/EDIT** | 2h | ALTA | 🔴 POR HACER |
| **3. Imágenes** | 1.5h | ALTA | 🔴 POR HACER |
| **4. Validaciones** | 1h | MEDIA | 🔴 POR HACER |
| **5. Novedades** | 2h | ALTA | 🔴 POR HACER |
| **Testing** | 1.5h | ALTA | 🔴 POR HACER |
| **TOTAL** | **9 horas** | - | - |

---

## ⚠️ RIESGOS Y MITIGACIÓN

| Riesgo | Impacto | Mitigación |
|--------|---------|-----------|
| Breaking changes en API | Alto | Tests unitarios completos |
| Perder funcionalidad | Medio | Validación exhaustiva |
| UX con errores | Medio | Manejo de errores robusto |

---

## ✅ CHECKLIST DE COMPLETITUD

- [ ] Eliminar lógica de tipos de manga del frontend
- [ ] Unificar flujos CREATE/EDIT
- [ ] Remover manipulación de imágenes del frontend  
- [ ] Centralizar validaciones en backend
- [ ] Simplificar flujo de novedades
- [ ] Tests unitarios en backend
- [ ] Tests de integración frontend-backend
- [ ] Documentar cambios en API
- [ ] QA completo
- [ ] Deploy a producción

---

**Resultado Final:** Frontend es SOLO presentación | Backend es TODA la lógica (DDD)
