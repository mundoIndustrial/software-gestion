# Flujo de Edición de Items - Documentación

## Resumen
Se ha implementado completamente la funcionalidad de editar items existentes en los pedidos de producción. El flujo permite:
1. Clickear el botón "Editar" (en el menú 3-puntos)
2. Abre el modal con todos los datos del item cargados
3. Realizar cambios
4. Guardar cambios (reemplaza el item anterior)

---

## Flujo Técnico Completo

### 1. **Click en Botón Editar** 
`Ubicación: item-card-interactions.js (líneas 117-123)`

```javascript
if (e.target.closest('.btn-editar-item')) {
  e.stopPropagation();
  const button = e.target.closest('.btn-editar-item');
  const itemIndex = button.dataset.itemIndex;
  const menu = button.closest('.menu-dropdown');
  if (menu) menu.style.display = 'none';
  handleEditarItem(itemIndex);
}
```

**Qué hace:**
- Detecta click en botón `.btn-editar-item`
- Obtiene el índice del item desde `data-item-index`
- Cierra el dropdown menu
- Llama a `handleEditarItem(itemIndex)`

---

### 2. **Función handleEditarItem()**
`Ubicación: item-card-interactions.js (líneas 156-178)`

```javascript
function handleEditarItem(itemIndex) {
  console.log('✏️ [ITEM-CARD-INTERACTIONS] Editando item:', itemIndex);
  
  if (!window.itemsPedido || !window.itemsPedido[itemIndex]) {
    console.error('❌ [EDITAR] Item no encontrado');
    return;
  }

  const item = window.itemsPedido[itemIndex];
  
  if (window.cargarItemEnModal && typeof window.cargarItemEnModal === 'function') {
    window.cargarItemEnModal(item, itemIndex);
  } else {
    window.abrirModalPrendaNueva();
  }
}
```

**Qué hace:**
- Obtiene el item del array `window.itemsPedido`
- Valida que el item exista
- Llama a `cargarItemEnModal(item, itemIndex)` para cargar los datos en el modal

---

### 3. **Función cargarItemEnModal()**
`Ubicación: prendas.js (líneas 669-821)`

Esta es la función clave que carga todos los datos del item en el modal. Hace lo siguiente:

#### 3.1 Abre el modal
```javascript
window.abrirModalPrendaNueva();
```

#### 3.2 Cambia el título y botón
```javascript
modalTitle.textContent = `Editar Prenda (Item ${itemIndex + 1})`;
btnAgregar.textContent = 'Guardar Cambios';
```

#### 3.3 Carga datos básicos
```javascript
document.getElementById('nueva-prenda-nombre-input').value = prenda.nombre || '';
document.getElementById('nueva-prenda-descripcion-input').value = prenda.descripcion || '';
document.getElementById('nueva-prenda-origen-select').value = item.origen || 'bodega';
```

#### 3.4 Carga tallas
```javascript
item.tallas.forEach(t => {
  const genero = t.genero;
  const talla = t.talla;
  const cantidad = t.cantidad;
  // ... poblando window.tallasSeleccionadas
});
```

#### 3.5 Carga variaciones (Manga, Bolsillos, Broche, Reflectivo)
```javascript
// Manga
if (variaciones.manga && variaciones.manga.tipo) {
  document.getElementById('aplica-manga').checked = true;
  document.getElementById('manga-input').value = variaciones.manga.tipo;
  // ...
}

// Bolsillos
if (variaciones.bolsillos && variaciones.bolsillos.tiene) {
  document.getElementById('aplica-bolsillos').checked = true;
  // ...
}

// Broche
if (variaciones.broche && variaciones.broche.tipo) {
  document.getElementById('aplica-broche').checked = true;
  // ...
}
```

#### 3.6 Guarda el índice para referencia posterior
```javascript
window.itemEnEdicion = itemIndex;
```

---

### 4. **Usuario modifica datos y clica "Guardar Cambios"**

El botón "Guardar Cambios" tiene el mismo onclick que antes: `agregarPrendaNueva()`

---

### 5. **Función agregarPrendaNueva() - Modo EDICIÓN**
`Ubicación: prendas.js (líneas 939-1129)`

La función ahora detecta si estamos editando:

```javascript
const editandoIndex = typeof window.itemEnEdicion !== 'undefined' ? window.itemEnEdicion : null;

if (editandoIndex !== null && window.itemsPedido[editandoIndex]) {
  // MODO EDICIÓN: Actualizar item existente
  window.itemsPedido[editandoIndex] = {
    tipo: 'nuevo',
    prenda: prendaData,
    origen: origen,
    procesos: procesos,
    es_proceso: procesos.length > 0,
    tallas: tallas,
    variaciones: variaciones,
    imagenes: window.imagenesPrendaStorage.obtenerImagenes()
  };
  
  window.itemEnEdicion = null;  // Limpiar flag
} else {
  // MODO AGREGAR: Crear nuevos items (código anterior)
}
```

**Diferencias respecto a modo AGREGAR:**
- Reemplaza el item en su posición original (en lugar de agregar uno nuevo)
- No duplica procesos (solo crea 1 item con procesos si existen)
- Limpia `window.itemEnEdicion` después de guardar

---

### 6. **Actualización de Vista**

```javascript
if (window.actualizarVistaItems && typeof window.actualizarVistaItems === 'function') {
  window.actualizarVistaItems();
}
```

Re-renderiza el item con los cambios.

---

### 7. **Cierre del Modal - Limpieza**
`Ubicación: prendas.js (líneas 817-876)`

Cuando se cierra el modal, se limpian y restauran valores:

```javascript
window.cerrarModalPrendaNueva = function() {
  // ...
  
  // Restaurar título y botón
  modalTitle.textContent = 'Agregar Prenda Nueva';
  btnAgregar.textContent = 'Agregar Prenda';
  
  // Limpiar flag de edición
  window.itemEnEdicion = null;
  
  // ... limpieza de telas, imágenes, etc.
}
```

---

## Flujo Visual

```
Usuario Click en "Editar"
         ↓
    handleEditarItem(index)
         ↓
    cargarItemEnModal(item, index)
         ↓
    Modal se abre con datos cargados
         ↓
    Usuario modifica datos
         ↓
    Usuario clica "Guardar Cambios"
         ↓
    agregarPrendaNueva() detects itemEnEdicion
         ↓
    Reemplaza item en window.itemsPedido[index]
         ↓
    actualizarVistaItems() re-renderiza
         ↓
    cerrarModalPrendaNueva() limpia y restaura
         ↓
    ✅ Item actualizado
```

---

## Logs de Depuración Disponibles

Se han añadido logs extensivos para ayudar con debugging:

### En item-card-interactions.js:
```
🎯 [ITEM-CARD-INTERACTIONS] Click en btn-menu-expandible detectado
📦 [ITEM-CARD-INTERACTIONS] Wrapper encontrado? true/false
🔄 [ITEM-CARD-INTERACTIONS] Dropdown abierto actualmente? true/false
✏️ [ITEM-CARD-INTERACTIONS] Editando item: [index]
```

### En updateItemCardInteractions():
```
🔍 [UPDATE-ITEM-CARD] Menu buttons encontrados: [count]
🔍 [UPDATE-ITEM-CARD] Menu wrappers encontrados: [count]
🔍 [UPDATE-ITEM-CARD] Menu dropdowns encontrados: [count]
🔎 [UPDATE-ITEM-CARD] Wrapper [idx]: estructura válida?
```

### En cargarItemEnModal():
```
📋 [CARGAR ITEM EN MODAL] Cargando item para editar
✅ [CARGAR ITEM] Tallas cargadas
✅ [CARGAR ITEM] Item cargado en modal, índice guardado
```

### En agregarPrendaNueva():
```
⭐ [AGREGAR PRENDA] Iniciando agregar/actualizar prenda
✏️ [AGREGAR PRENDA] ¿Estamos editando? true/false
✏️ [AGREGAR PRENDA] EDITANDO ITEM [index]
✅ [AGREGAR PRENDA] Item [index] actualizado
```

---

## Validaciones Implementadas

1. **Verificación de existencia del item:** El item debe existir en `window.itemsPedido[index]`
2. **Validación de modal:** El modal debe estar presente en el DOM
3. **Limpieza de flag:** `window.itemEnEdicion` se limpia después de cada operación
4. **Restauración de UI:** Título y botón se restauran cuando se cierra el modal

---

## Casos de Uso

### Caso 1: Editar prenda existente
1. Usuario vió que falta una variación en manga
2. Clica "Editar" en el menú 3-puntos
3. Modal abre con manga desmarcado
4. Usuario marca manga y selecciona tipo
5. Clica "Guardar Cambios"
6. Item se actualiza con la nueva variación

### Caso 2: Cambiar tallas
1. Usuario realizó error al seleccionar tallas
2. Clica "Editar"
3. Modal abre con tallas mostradas
4. Usuario cambia cantidades
5. Clica "Guardar Cambios"
6. Item se actualiza con nuevas tallas

### Caso 3: Cambiar origen
1. Usuario marcó origen incorrecto
2. Clica "Editar"
3. Modal abre con origen mostrado
4. Usuario cambia origen de "bodega" a "cliente"
5. Clica "Guardar Cambios"
6. Item se actualiza

---

## Notas Técnicas

- La edición reemplaza el item en su posición original, manteniendo el índice
- No se crean ítems duplicados como en modo "agregar con procesos"
- Las imágenes se cargan nuevamente desde `window.imagenesPrendaStorage`
- Las tallas se preservan durante el cierre del modal (no se limpian)
- El menú dropdown se cierra automáticamente al hacer click en editar/eliminar

---

## Archivos Modificados

1. **prendas.js**
   - Agregada función `cargarItemEnModal(item, itemIndex)`
   - Modificada función `agregarPrendaNueva()` para detectar modo edición
   - Modificada función `cerrarModalPrendaNueva()` para limpiar flag

2. **item-card-interactions.js**
   - Modificada función `handleEditarItem()` para llamar a `cargarItemEnModal()`
   - Añadidos logs de depuración en `initializeItemCardInteractions()`
   - Mejorada función `updateItemCardInteractions()` con validaciones detalladas

3. **prendas.js**
   - Añadido logging en `actualizarVistaItems()` para depuración de estructura HTML

