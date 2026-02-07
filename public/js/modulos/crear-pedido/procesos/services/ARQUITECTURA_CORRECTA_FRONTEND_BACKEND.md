# Análisis: Separación Frontend/Backend - PrendaEditor

## 🔴 PROBLEMA IDENTIFICADO

El `PrendaEditorService` que creé contiene **lógica de negocio** que debería estar en el **BACKEND**:

```javascript
// ❌ ESTO DEBE IR AL BACKEND
aplicarOrigenAutomaticoDesdeCotizacion(prenda) {
    // Si es Reflectivo o Logo → FUERZA bodega
    // Esta es una REGLA DE NEGOCIO
}

validarPrenda(datosPrenda) {
    // Validación de datos
    // Debe estar en el Backend
}

procesarProcesos(procesos) {
    // Transformación/normalización de datos
    // Debe estar en el Backend
}
```

---

## 📊 SEPARACIÓN CORRECTA

### ✅ AL BACKEND LE CORRESPONDE:

```javascript
// Endpoints que deberían existir en el backend

POST /api/prendas/aplicar-origen
// Input: { prenda, cotizacion_id }
// Output: { prenda con origen ya aplicado }
// Lógica de negocio: decidir origen según cotización

POST /api/prendas/validar
// Input: { datosPrenda }
// Output: { valido, errores }
// Lógica: reglas de validación

POST /api/prendas/procesar-datos
// Input: { prendaRaw }
// Output: { prendaProcesada, procesos[], telas[], tallas[] }
// Lógica: transformación y normalización de datos

GET /api/prendas/{id}/preparar-para-edicion
// Output: datos listos para llenar el formulario
// Todo ya procesado, normalizado, validado

POST /api/prendas/{id}/guardar
// Input: datosPrenda
// Output: resultado operación
```

---

## 🎯 LO QUE DEBERÍA QUEDAR EN FRONTEND

```javascript
// Solo ORQUESTACIÓN y PRESENTACIÓN

class PrendaEditor {
    async cargarPrendaEnModal(prendaId) {
        // 1. Llamar al backend para obtener datos
        const prendaProcesada = await this.api.obtenerPrendaParaEdicion(prendaId);
        
        // 2. SOLO presentar en el formulario
        this.domAdapter.llenarFormulario(prendaProcesada);
        
        // 3. Abrir modal
        this.domAdapter.abrirModal();
    }

    async guardarPrenda(datosFormulario) {
        // 1. Validación básica de UI (formulario no vacío, etc)
        if (!datosFormulario.nombre) {
            this.mostrarNotificacion('Complete el nombre', 'error');
            return;
        }
        
        // 2. Enviar al backend (que hace validación completa)
        const resultado = await this.api.guardarPrenda(datosFormulario);
        
        // 3. Solo mostrar resultado
        if (resultado.exito) {
            this.mostrarNotificacion('Guardado', 'success');
            this.resetearFormulario();
        } else {
            this.mostrarNotificacion(resultado.mensaje, 'error');
        }
    }
}
```

---

## 🏗️ ARQUITECTURA CORRECTA

```
┌─────────────────────────────────────────────────────┐
│ FRONTEND - PrendaEditor                             │
├─────────────────────────────────────────────────────┤
│ Responsabilidades:                                  │
│ • Orquestar flujos UI                               │
│ • Presentar datos                                   │
│ • Recopilar input del usuario                       │
│ • Mostrar errores/éxitos                            │
│                                                      │
│ NO HACE: lógica de negocio, validaciones complejas  │
└────────────┬──────────────────────────────────────┬─┘
             │  HTTP                                │
             │  JSON                                │
             ▼                                      ▼
┌──────────────────────────────┐    ┌──────────────────────┐
│ BACKEND - PrendaController   │    │ BD                   │
├──────────────────────────────┤    ├──────────────────────┤
│ POST /prendas/{id}/editar    │    │ prendas              │
│ → Obtener prenda             │    │ cotizaciones         │
│ → Aplicar origen automático  │    │ procesos             │
│ → Normalizar datos           │    │ telas                │
│ → Validar reglas negocio     │    │ variaciones          │
│ → Retornar LISTO para UI     │    └──────────────────────┘
│                              │
│ POST /prendas/guardar        │
│ → Validar datos nuevamente   │
│ → Aplicar reglas negocio     │
│ → Guardar BD                 │
│ → Retornar resultado         │
└──────────────────────────────┘
```

---

## 🔄 FLUJO CORRECTO

### Cargar prenda para editar

```
Usuario abre prenda
        ↓
Frontend: domAdapter.abrirModal()
        ↓
Frontend: api.obtenerPrendaParaEdicion(id)
        ↓
Backend: 
  • Obtener prenda de BD
  • Si es cotización Reflectivo → aplicar origen = 'bodega'
  • Procesar telas
  • Procesar procesos
  • Normalizar variaciones
  • Validar que todo sea consistente
        ↓
Backend: Retorna prendaProcesada {
  nombre_prenda: "...",
  origen: "bodega",
  telasAgregadas: [...],
  procesosSeleccionados: {...},
  variacionesActuales: {...},
  // TODO YA LISTO
}
        ↓
Frontend: domAdapter.llenarFormulario(prendaProcesada)
        ↓
Usuario ve formulario listo
```

---

## ✨ REFACTORIZACIÓN NECESARIA

### Lo que creé (INCORRECTO):
```
PrendaEditorService → Lógica de negocio en Frontend ❌
PrendaDOMAdapter → Acceso a DOM ✓
PrendaAPI → Llamadas HTTP ✓
PrendaEventBus → Eventos ✓
```

### Lo que DEBE SER (CORRECTO):
```
PrendaEditorOrchestrator → Solo orquestación ✓
  └─ Coordina llamadas a API
  └─ Coordina actualización de DOM
  └─ Emite eventos

PrendaDOMAdapter → Acceso a DOM ✓
PrendaAPI → Llamadas HTTP (datos ya procesados) ✓
PrendaEventBus → Eventos ✓

[BACKEND] PrendaService → TODA la lógica de negocio ✓
  └─ Aplicar origen automático
  └─ Procesar/normalizar datos
  └─ Validaciones
  └─ Transformaciones
```

---

## 📋 EJEMPLOS CONCRETOS

### Caso 1: Aplicar origen automático

**ACTUAL (INCORRECTO):**
```javascript
// Frontend decide la lógica
PrendaEditorService.aplicarOrigenAutomaticoDesdeCotizacion(prenda) {
    if (prenda.cotizacion.tipo_cotizacion_id === 4) { // Reflectivo
        prenda.origen = 'bodega';
    }
}
```

**CORRECTO:**
```javascript
// Backend decide
Backend: GET /api/prendas/1/aplicar-origen
Response: {
  origen: "bodega", // Backend decidió
  razon: "Cotización es Reflectivo"
}

// Frontend solo presenta
Frontend: domAdapter.establecerOrigen('bodega');
```

### Caso 2: Validar prenda

**ACTUAL (INCORRECTO):**
```javascript
// Frontend valida
PrendaEditorService.validarPrenda(prenda) {
    if (!prenda.nombre) errores.push("..."); // ❌ Validación en frontend
}
```

**CORRECTO:**
```javascript
// Backend valida
Backend: POST /api/prendas/validar
{
    valido: false,
    errores: [
        "El nombre es obligatorio",
        "Debe agregar al menos una tela",
        "Origen debe ser bodega para cotización Reflectivo"
    ]
}

// Frontend solo muestra
Frontend: errores.forEach(err => mostrarNotificacion(err, 'error'));
```

### Caso 3: Procesar procesos

**ACTUAL (INCORRECTO):**
```javascript
// Frontend normaliza procesos
Frontend: PrendaEditorService.procesarProcesos(procesos) {
    // Convertir de formato objeto a array
    // Mapear campos
    // ❌ Lógica de transformación de datos
}
```

**CORRECTO:**
```javascript
// Backend retorna datos ya normalizados
Backend: GET /api/prendas/1/procesos
Response: {
    procesos: [
        {
            id: 1,
            tipo: "bordado",
            nombre: "Bordado",
            // YA NORMALIZADO
            ubicaciones: [],
            tallas: { DAMA: {...}, CABALLERO: {...} }
        }
    ]
}

// Frontend solo presenta
Frontend: procesos.forEach(p => domAdapter.marcarProceso(p.tipo));
```

---

## 🎯 ACCIONES NECESARIAS

### 1. **Crear endpoints en Backend** (si no existen):
```php
// Laravel example
Route::post('/api/prendas/{id}/preparar-edicion', [PrendaController::class, 'prepararParaEdicion']);
Route::post('/api/prendas/guardar', [PrendaController::class, 'guardar']);
Route::post('/api/prendas/validar', [PrendaController::class, 'validar']);
```

### 2. **Refactorizar PrendaAPI**:
```javascript
// Cambiar de:
api.cargarTelasDesdeCotizacion(cotizacionId, prendaId)

// A:
api.obtenerPrendaParaEdicion(prendaId)
// Backend retorna TODO procesado
```

### 3. **Refactorizar PrendaEditorService/Orchestrator**:
```javascript
// Solo orquestación, NO lógica de negocio
class PrendaEditorOrchestrator {
    async cargarPrenda(prendaId) {
        // 1. Obtener datos (Backend hace el trabajo)
        const prenda = await this.api.obtenerPrendaParaEdicion(prendaId);
        
        // 2. Si hay error, mostrar
        if (!prenda.valido) {
            prenda.errores.forEach(e => this.mostrarNotificacion(e, 'error'));
            return;
        }
        
        // 3. Si todo ok, presentar
        this.ui.llenarFormulario(prenda);
    }
}
```

---

## ⚡ VENTAJAS DE HACERLO BIEN

| Aspecto | Frontend Acoplado | Backend Like It Should Be |
|---------|------------------|--------------------------|
| **Validación** | Duplicada y inconsistente | Una sola fuente de verdad |
| **Reglas negocio** | Spread en UI | Centralizadas |
| **Bugs** | Aparecen si lógica en UI | Evitados en backend |
| **Testeo** | Difícil (necesita DOM) | Fácil (tests backend) |
| **Mobile/API** | Duplicar código | Reutilizar backend |
| **Performance** | Lógica en JS | Lógica en servidor |
| **Seguridad** | Validación ignorable | Segura en servidor |

---

## ✅ RECOMENDACIÓN

**Quieres que:**

1. **Opción A: Refactorizar correctamente** (RECOMENDADO)
   - Mover lógica de negocio al backend
   - Dejar frontend solo para orquestación
   - Tiempo: ~4-6 horas si el backend no está listo

2. **Opción B: Mantener refactorización actual**
   - Mejor que antes, pero no es architecture perfect
   - Al menos está desacoplado
   - Podría mejorarse después

3. **Opción C: Análisis de backend actual**
   - Primero ver qué endpoints existen
   - Luego decidir qué mover

¿Cuál prefieres?
