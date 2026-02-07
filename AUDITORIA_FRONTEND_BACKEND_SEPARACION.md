# Auditoría: Lógica de Negocio en Frontend que Debe Migrarse al Backend

## 🚨 Problemas Identificados en `gestion-items-pedido.js`

### 1. ⚠️ GESTIÓN DE ORDENAMIENTO DE ITEMS (CRÍTICO)
**Ubicación:** Líneas 93-123, 128-137, 145-157
```javascript
obtenerItemsOrdenados()      // Orquesta items
agregarPrendaAlOrden()       // Gestiona índices
agregarEPPAlOrden()         // Gestiona índices
```

**Problema:** El frontend mantiene 3 estructuras paralelas:
- `this.prendas[]` - array de prendas
- `this.epps[]` - array de EPPs
- `this.ordenItems[]` - array con referencias {tipo, index}

**Por qué es un problema:**
- ❌ Difícil de sincronizar con backend
- ❌ Si el usuario actualiza la página, se pierden los datos
- ❌ No hay source of truth (verdad única)
- ❌ Lógica duplicada en gettor/setter

**Recomendación:** ✅
```
1. Backend retorna: { items: [{id, tipo, nombre, ...}] }
2. Frontend solo hace: this.items = response.items (almacenamiento simple)
3. Ordenamiento: Delegarlo al backend o mantener solo en frontend si es puramente UI
```

---

### 2. ⚠️ ELIMINACIÓN CON RECONSTRUCCIÓN DE ÍNDICES (CRÍTICO)
**Ubicación:** Líneas 258-325 (método `eliminarItem`)
```javascript
// Búsqueda del tipo
// Búsqueda del índice
// Eliminación del array
// Reconstrucción de índices
let prendaIdx = 0, eppIdx = 0;
this.ordenItems.forEach(item => {
    if (item.tipo === 'prenda') {
        item.index = prendaIdx;
        prendaIdx++;
    } else if (item.tipo === 'epp') {
        item.index = eppIdx;
        eppIdx++;
    }
});
```

**Problema:**
- ❌ Lógica de manipulación de arrays complicada en frontend
- ❌ Sincronización con `gestorPrendaSinCotizacion?.eliminar()` (olor a código)
- ❌ Requiere conocimiento del modelo de datos interno

**Recomendación:** ✅
```
Frontend: 
  - eliminarItem(itemId)
  
Backend:
  - DELETE /api/items/{itemId}
  - Retorna: { success: true, items: [...] }
  - Frontend: this.items.splice(...)
```

---

### 3. ⚠️ VALIDACIÓN DE DATOS DE NEGOCIO (IMPORTANTE)
**Ubicación:** Líneas 476-482
```javascript
const tieneTallas = prendaData.cantidad_talla && 
    Object.values(prendaData.cantidad_talla).some(genero => 
        Object.keys(genero).length > 0
    );

if (!tieneTallas) {
    this.notificationService?.advertencia('Por favor selecciona al menos una talla');
    return;
}
```

**Problema:**
- ❌ Validación de regla de negocio en frontend (puede ser bypasseada)
- ❌ Backend también hace validación (duplicada)
- ⚠️ Inconsistencia si cambian reglas de negocio

**Recomendación:** ✅
```
Frontend: Validación UI básica (campos requeridos, longitud, etc)

Backend: Validación de reglas de negocio
  - Debe tener al menos una talla
  - Validar procesos requeridos
  - Validar variantes compatibles
  
Respuesta backend:
  {
    "success": false,
    "errors": [
      "Debe seleccionar al menos una talla",
      "Debe agregar ubicaciones de proceso"
    ]
  }
```

---

### 4. ⚠️ CONSTRUCCIÓN DE DATOS DE FORMULARIO (IMPORTANTE)
**Ubicación:** Línea 472
```javascript
const prendaData = window.prendaFormCollector.construirPrendaDesdeFormulario(
    this.prendaEditIndex,
    this.prendas
);
```

**Problema:**
- ❌ Lógica de transformación de datos en frontend
- ❌ Difícil de mantener si cambia estructura de datos
- ⚠️ Frontend tiene conocimiento de estructura de negocio

**Recomendación:** ✅
```
Frontend: Recolectar datos del formulario
const rawData = {
  nombre: document.getElementById('nombre').value,
  descripcion: ...,
  tallas: this.tallasSeleccionadas,
  ...
}

Backend: Procesar y validar
POST /api/prendas
{
  "prenda": rawData
}

Backend valida, transforma, persiste
```

---

### 5. ⚠️ ERROR: Variable `esEdicion` sin definir (BUG)
**Ubicación:** Línea 490
```javascript
console.log('[agregarPrendaNueva] 🎯 Operación:', esEdicion ? '✏️ ACTUALIZAR' : '✨ CREAR NUEVA');
```

**Problema:** `esEdicion` nunca está definida en este scope
- Debería ser: `this.prendaEditIndex !== null && this.prendaEditIndex !== undefined`

---

### 6. ⚠️ SINCRONIZACIÓN CON GESTORES EXTERNOS (OLOR A CÓDIGO)
**Ubicación:** Línea 318
```javascript
if (tipoBuscado === 'prenda' && window.gestorPrendaSinCotizacion?.eliminar) {
    window.gestorPrendaSinCotizacion.eliminar(indiceBuscado);
}
```

**Problema:**
- ❌ Acoplamiento entre componentes via window
- ❌ Duplicación de estado en múltiples gestores
- ❌ Difícil de hacer testing

**Recomendación:** ✅
```
Usar EventBus/PubSub centralizado:
  
eliminarItem(index):
  - Backend: DELETE /api/items/{id}
  - Frontend: this.eventBus.emit('item:deleted', {id, type})
  
Todos los gestores escuchan 'item:deleted' y actualizan su propio estado
```

---

## 📋 Resumen de Cambios Recomendados

| Responsabilidad | Actual ❌ | Recomendado ✅ |
|---|---|---|
| Gestionar orden de items | Frontend | Backend (o frontend si es puramente UI) |
| Reconstruir índices | Frontend | Backend |
| Validar reglas de negocio | Frontend + Backend | Solo Backend |
| Transformar datos de formulario | Frontend | Backend |
| Manejar eliminación cascada | Frontend | Backend |
| Sincronizar múltiples gestores | Via window (acoplado) | EventBus centralizado |

---

## 🔧 Pasos de Refactorización

### Paso 1: Simplificar estructura de items
```javascript
// Antes (complejo):
this.prendas = [];
this.epps = [];
this.ordenItems = []; // {tipo, index}

// Después (simple):
this.items = []; // Backend retorna items con tipo definido
```

### Paso 2: Delegar validación al backend
```javascript
// Frontend: solo recolecta datos
const formData = this.formCollector.recolectarFormulario();

// Backend: valida e retorna errores
const resultado = await this.apiService.agregarPrenda(formData);
if (!resultado.success) {
  mostrarErrores(resultado.errors); // Backend define mensajes
}
```

### Paso 3: Usar EventBus para sincronización
```javascript
// En lugar de: window.gestorPrendaSinCotizacion?.eliminar()
this.eventBus.emit('item:deleted', { itemId, type });

// Los gestores escuchan:
this.eventBus.on('item:deleted', (data) => {
  this.actualizarEstado();
});
```

---

## 🎯 Archivos Relacionados a Revisar

1. **Backend:**
   - `app/Http/Controllers/PrendaController.php` - Validación de reglas
   - `app/Services/PrendaService.php` - Lógica de negocio
   - `app/Repositories/PrendaRepository.php` - Persistencia

2. **Frontend:**
   - `ItemFormCollector` - Simplificar, solo recolecta
   - `ItemAPIService` - Reestructurar respuestas del backend
   - `ItemRenderer` - Solo renderiza lo que recibe
   - `PrendaEditorOrchestrator` - Revisar qué hace

3. **Sincronización:**
   - `EventBus` - Centralizar comunicación entre gestores
   - `PubSub pattern` - Implementar si no existe
