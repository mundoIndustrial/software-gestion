# 🎯 EDICIÓN DINÁMICA DE PROCESOS EN PRENDAS

## Flujo Implementado

### 1️⃣ **Iniciación de Edición**

Cuando el usuario hace clic en "Editar" en una tarjeta de proceso:

```javascript
window.editarProcesoDesdeModal(tipo)
├─ Obtener datos del proceso existente
├─ Iniciar window.gestorEditacionProcesos.iniciarEdicion(tipo, false)
├─ Iniciar window.procesosEditor.iniciarEdicion(tipo, datosActuales)
├─ Cargar datos en el modal
└─ Abrir modal en modo EDICIÓN (data-modo-edicion="true")
```

### 2️⃣ **Captura de Cambios**

Dentro del modal, cuando el usuario realiza cambios:

```
Usuario edita ubicaciones/imágenes/observaciones/tallas
    ↓
Modal llama a window.agregarProcesoAlPedido()
    ↓
SI es MODO EDICIÓN:
    ├─ window.procesosEditor.registrarCambio*(...)
    ├─ window.procesosEditor.guardarEnWindowProcesos()
    └─ Marca proceso como "editado" en gestorEditacionProcesos
    
SI es MODO CREAR:
    ├─ Guarda directamente en window.procesosSeleccionados[tipo]
    └─ Comportamiento actual (sin cambios)
```

### 3️⃣ **Estados de Procesos**

En `window.procesosSeleccionados`:

```javascript
{
    'reflectivo': {
        datos: {
            id: 123,                    // ID en BD
            tipo: 'reflectivo',
            tipo_proceso_id: 1,
            ubicaciones: ['Pecho', 'Espalda'],  // REEMPLAZADAS (no merge)
            imagenes: ['url1.jpg'],             // REEMPLAZADAS (no merge)
            observaciones: 'Nueva obs',         // ACTUALIZADA
            tallas: { dama: {S: 5}, caballero: {} }
        },
        _editado: true   // 🚩 MARCA: Este proceso fue editado
    }
}
```

### 4️⃣ **Guardado de Prenda**

Cuando el usuario hace clic en "Guardar Cambios" de la prenda:

```
Clic "Guardar Cambios"
    ↓
PRE-GUARDADO: Aplicar cambios de procesos editados
    ├─ Obtener procesos editados: window.gestorEditacionProcesos.obtenerProcesosEditados()
    ├─ Validar cambios
    └─ SI hay procesos editados:
        └─ Hacer PATCH individual para CADA proceso editado
           (ANTES de actualizar la prenda)
    
GUARDADO PRENDA: Guardar prenda (sin duplicar procesos)
    ├─ window.procesosSeleccionados ahora contiene:
    │   - Procesos nuevos (creados en CREAR)
    │   - Procesos editados (actualizados con cambios)
    ├─ Enviar a /api/prendas-pedido/{id}
    └─ Marca proceso como "no editado"
```

### 5️⃣ **POST-GUARDADO**

```
Prenda guardada exitosamente
    ↓
window.gestorEditacionProcesos.limpiar()
    └─ Resetea tracking de procesos editados
```

---

## 🔑 Conceptos Clave

### ✅ NO SE DUPLICAN PROCESOS

Porque:
- El proceso tiene un `id` único en BD
- Al actualizar procesos editados, se identifica por `id`
- El endpoint PATCH `/api/prendas-pedido/{id}/procesos/{proceso_id}` actualiza, no crea

### ✅ NO AFECTA OTRAS PRENDAS

Porque:
- Las actualizaciones son por prenda específica: `/api/prendas-pedido/{prendaId}/procesos/{procesoId}`
- Solo se modifican procesos dentro de esa prenda
- Otros procesos en otras prendas no se tocan

### ✅ UBICACIONES E IMÁGENES SE REEMPLAZAN (NO MERGE)

```javascript
// ANTES
ubicaciones: ['Pecho', 'Espalda', 'Mangas']

// USUARIO ELIMINA "Espalda" y "Mangas"
// EN EL EDITOR:
ubicacionesProcesoSeleccionadas = ['Pecho']

// GUARDAR CAMBIOS:
procesosEditor.registrarCambioUbicaciones(['Pecho'])
    ↓
Backend recibe: { ubicaciones: ['Pecho'] }
    ↓
// DESPUÉS (BD actualizada)
ubicaciones: ['Pecho']  // ✅ Eliminadas las otras
```

### ✅ TALLAS DEL PROCESO SON INDEPENDIENTES

```javascript
// Prenda tiene tallas: S, M, L (cantidad: 10, 20, 5)

// Proceso Reflectivo: solo aplica a S y M
window.tallasCantidadesProceso = {
    dama: { S: 10, M: 20 }
}

// Cuando se guarda, se actualiza la BD con SOLO estas tallas
// Las otras tallas de la prenda NO se afectan
```

---

## 📋 Estructura de Archivos Nuevos

### Frontend

1. **`proceso-editor.js`**
   - Clase `ProcesosEditor`
   - Maneja buffer de edición individual
   - Registra cambios específicos

2. **`gestor-edicion-procesos.js`**
   - Clase `GestorEditacionProcesos`
   - Orquesta múltiples ediciones
   - Trackea qué procesos fueron editados

3. **`servicio-procesos.js`**
   - Clase `ServicioProcesos`
   - Comunica con backend
   - Envía cambios de procesos al servidor

### Backend

**Endpoint a crear:**
```
PATCH /api/prendas-pedido/{prendaId}/procesos/{procesoId}
```

Body:
```json
{
  "tipo_proceso_id": 1,
  "ubicaciones": ["Pecho"],
  "imagenes": ["url.jpg"],
  "observaciones": "texto",
  "tallas": {
    "dama": {"S": 5},
    "caballero": {}
  }
}
```

---

## 🚀 Cómo Usar

### Para Usuario

1. Editar prenda → Seleccionar proceso → Clic "Editar" en tarjeta
2. Modal se abre en **modo EDICIÓN**
3. Cambiar ubicaciones, imágenes, observaciones, tallas
4. Clic "Guardar cambios"
5. Modal se cierra, cambios se aplican en memoria
6. Clic "Guardar cambios" en prenda
7. Se actualiza prenda con cambios de procesos

### Para Developer

```javascript
// Verificar si hay procesos editados
const editados = window.gestorEditacionProcesos.obtenerProcesosEditados();
console.log(editados);  // [{tipo, id, cambios}, ...]

// Obtener datos de un proceso en edición
const proceso = window.procesosEditor.obtenerProcesoenEdicion();
console.log(proceso);  // {tipo, datos}

// Obtener solo cambios
const cambios = window.procesosEditor.obtenerCambios();
console.log(cambios);  // {ubicaciones, imagenes, ...}
```

---

## 🔍 Flujo Detallado: Editar Reflectivo

### Escenario
Prenda "Camiseta" tiene:
- Proceso Reflectivo con ubicaciones: ['Pecho', 'Espalda']
- El usuario quiere cambiar a ['Pecho']

### Pasos

1. **Clic Editar en tarjeta Reflectivo**
   ```javascript
   editarProcesoDesdeModal('reflectivo')
   → Abre modal con datos actuales cargados
   → Marca como "modo edición"
   ```

2. **Usuario modifica (elimina "Espalda")**
   ```javascript
   window.ubicacionesProcesoSeleccionadas = ['Pecho']
   ```

3. **Clic "Guardar cambios" en modal**
   ```javascript
   window.agregarProcesoAlPedido()
   → modoActual === 'editar'
   → procesosEditor.registrarCambioUbicaciones(['Pecho'])
   → procesosEditor.guardarEnWindowProcesos()
   → window.procesosSeleccionados['reflectivo'].datos.ubicaciones = ['Pecho']
   ```

4. **Modal se cierra**

5. **Usuario clic "Guardar cambios" en prenda**
   ```javascript
   Aquí se detecta que reflectivo fue editado
   → Hacer PATCH /api/prendas-pedido/1/procesos/123
      Body: { ubicaciones: ['Pecho'] }
   → Esperar respuesta exitosa
   → Guardar prenda normalmente
   ```

6. **BD actualizada**
   ```sql
   UPDATE procesos_prendas 
   SET ubicaciones = '["Pecho"]'
   WHERE id = 123 AND prenda_id = 1
   ```

---

## ⚠️ Validaciones Críticas

- ✅ Ubicaciones: Array no vacío (al menos 1)
- ✅ Imágenes: Array válido (puede estar vacío)
- ✅ Observaciones: String (puede estar vacío)
- ✅ Tallas: Objeto {dama: {}, caballero: {}}
- ✅ ID proceso: Debe existir en BD
- ✅ Prenda ID: Debe ser válida

---

## 📊 Estados Posibles

| Estado | CREAR | EDITAR | Desc |
|--------|-------|--------|------|
| Modal abierto | `modoActual='crear'` | `modoActual='editar'` | Flag global |
| Datos procesosEditor | No inicia | Inicia | Captura estado original |
| Guardado | Directo a window.procesosSeleccionados | Buffer en procesosEditor | Donde se guardan |
| Al cerrar modal | Checkbox se deselecciona si no guardó | Nada | Comportamiento |
| Duplic posible | NO (checkbox) | NO (actualiza ID) | Seguridad |

