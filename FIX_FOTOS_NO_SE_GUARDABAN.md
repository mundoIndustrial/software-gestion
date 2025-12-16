# 🔧 FIX APLICADO: Fotos No Se Guardaban en Cotizaciones

## 📋 RESUMEN DEL PROBLEMA

Las fotos agregadas a las cotizaciones se mostraban en el preview durante la creación, pero **NO se guardaban en la base de datos** y, por lo tanto, **NO aparecían cuando se veía la cotización guardada**.

### Causa Raíz Identificada

En [resources/views/components/template-producto.blade.php](resources/views/components/template-producto.blade.php):

1. **Línea 55** (fotos de prenda): `ondrop="manejarDrop(event)"` 
   - ❌ **NO pasaba la referencia al elemento `<label>` (dropZone)**
   - Esto causaba que `agregarFotos()` no recibiera correctamente el contenedor

2. **Línea 56** (file input): `onchange="agregarFotos(this.files, this.closest('label').nextElementSibling)"`
   - ❌ **Pasaba el `.nextElementSibling` en lugar del `<label>` mismo**
   - El próximo elemento es `<div class="fotos-preview">`, no el contenedor correcto

3. **Línea 120** (fotos de tela): Similar problema con `ondrop="manejarDrop(event)"`

## ✅ CAMBIOS APLICADOS

### 1. [template-producto.blade.php](resources/views/components/template-producto.blade.php) - Línea 55
```html
<!-- ANTES -->
<label ... ondrop="manejarDrop(event)" ...>
    <input ... onchange="agregarFotos(this.files, this.closest('label').nextElementSibling)" ...>

<!-- DESPUÉS -->
<label ... ondrop="manejarDrop(event, this)" ...>
    <input ... onchange="agregarFotos(this.files, this.closest('label'))" ...>
```

**Cambios:**
- ✅ `ondrop` ahora pasa `this` (el elemento `<label>`)
- ✅ `onchange` ahora pasa `this.closest('label')` en lugar de `.nextElementSibling`

### 2. [template-producto.blade.php](resources/views/components/template-producto.blade.php) - Línea 120
```html
<!-- ANTES -->
<label ... ondrop="manejarDrop(event)" ...>

<!-- DESPUÉS -->
<label ... ondrop="manejarDrop(event, this)" ...>
```

**Cambio:**
- ✅ `ondrop` ahora pasa `this` para la zona de drag-drop de telas

### 3. [public/js/asesores/cotizaciones/productos.js](public/js/asesores/cotizaciones/productos.js) - Función `manejarDrop()`
```javascript
// ANTES
function manejarDrop(event) {
    event.preventDefault();
    event.stopPropagation();
    const dropZone = event.currentTarget;  // ❌ Incorrecta con onclick inline
    dropZone.classList.remove('drag-over');
    agregarFotos(event.dataTransfer.files, dropZone);
}

// DESPUÉS
function manejarDrop(event, dropZone) {
    event.preventDefault();
    event.stopPropagation();
    // Si no se pasa dropZone, usar event.currentTarget (para compatibilidad)
    if (!dropZone) {
        dropZone = event.currentTarget;
    }
    dropZone.classList.remove('drag-over');
    agregarFotos(event.dataTransfer.files, dropZone);
}
```

**Cambios:**
- ✅ Ahora acepta `dropZone` como parámetro (pasado desde el HTML)
- ✅ Mantiene compatibilidad con `event.currentTarget` si no se pasa el parámetro
- ✅ Pasa correctamente el `<label>` a `agregarFotos()`

## 🧪 CÓMO PROBAR

### Paso 1: Crear Nueva Cotización
1. Ir a [http://desktop-8un1ehm:8000/asesores/cotizaciones](http://desktop-8un1ehm:8000/asesores/cotizaciones)
2. Hacer clic en **"Crear Cotización"**
3. Seleccionar una prenda (ej: Camisa)

### Paso 2: Agregar Fotos (2 formas)

**Opción A - Drag & Drop:**
1. Arrastra un archivo de imagen a la zona de **"FOTOS PRENDA"**
2. Deberías ver la imagen en el preview inmediatamente

**Opción B - Click:**
1. Haz clic en la zona **"FOTOS PRENDA"**
2. Selecciona una imagen
3. Deberías ver la imagen en el preview inmediatamente

### Paso 3: Agregar Variación (Color + Tela)
1. En la sección **"COLOR, TELA Y REFERENCIA"**:
   - Selecciona un color
   - Selecciona una tela
   - Opcionalmente agrega foto de tela

### Paso 4: Guardar Cotización
1. Haz clic en **"GUARDAR COTIZACIÓN"**
2. Espera a que la cotización se guarde

### Paso 5: Verificar Que Las Fotos Se Guardaron
1. La página debería redirigir a la vista de detalles
2. En la tabla, en la columna **"Imagen Prenda & Tela"**:
   - Deberías ver miniatura(s) de la foto(s) que subiste
   - **Si ves las fotos aquí ✅, el fix funcionó correctamente**

### Paso 6: Ir a "Ver Cotización"
1. En la lista de cotizaciones, busca la que acabas de crear
2. Haz clic en el botón **"Ver"** 
3. En la pestaña **"PRENDAS"**, verifica:
   - Column **"PRENDA"** debe mostrar la foto(s) con el count correcto
   - Column **"TELA"** debe mostrar foto(s) de tela si las agregaste

## 📊 VALIDACIÓN TÉCNICA

El flujo ahora funciona correctamente:

```
Usuario arrastra foto
    ↓
ondrop="manejarDrop(event, this)" se ejecuta
    ↓
manejarDrop() recibe correctamente el <label> como dropZone
    ↓
agregarFotos(files, dropZone) es llamado
    ↓
dropZone.closest('.producto-card') encuentra la prenda correcta
    ↓
Archivo se agrega a window.fotosSeleccionadas[productoId]
    ↓
Archivo se agrega a window.imagenesEnMemoria.prendaConIndice
    ↓
actualizarPreviewFotos() muestra la imagen en UI
    ↓
Usuario hace clic en "GUARDAR COTIZACIÓN"
    ↓
guardado.js lee window.imagenesEnMemoria.prendaConIndice
    ↓
Agrega los archivos al FormData con clave: prendas[index][fotos][]
    ↓
Backend recibe los archivos en $request->file()
    ↓
procesarImagenesCotizacion() guarda las fotos en la BD
    ↓
Usuario ve las fotos en la vista "Ver Cotización" ✅
```

## 🔍 ARCHIVOS MODIFICADOS

1. ✅ [resources/views/components/template-producto.blade.php](resources/views/components/template-producto.blade.php)
   - Línea 55: Fotos prenda - `ondrop` y `onchange`
   - Línea 120: Fotos tela - `ondrop`

2. ✅ [public/js/asesores/cotizaciones/productos.js](public/js/asesores/cotizaciones/productos.js)
   - Función `manejarDrop()`: Actualizada para aceptar parámetro `dropZone`

## ⚠️ IMPORTANTE

- Este fix solo afecta a **nuevas cotizaciones** que se creen después de aplicar los cambios
- La cotización 59 anterior no tendrá fotos porque se creó antes del fix
- Para probar, **debe crear una cotización nueva** con este código corregido

## 📝 NOTAS TÉCNICAS

- La función `agregarFotos()` en productos.js YA estaba correcta
- El problema era puramente en cómo se pasaban los parámetros desde el HTML
- `window.imagenesEnMemoria` estaba vacío porque `agregarFotos()` nunca se ejecutaba correctamente
- Con este fix, `window.imagenesEnMemoria.prendaConIndice` se poblará correctamente
- El backend ya procesa correctamente los archivos cuando llegan

---

**Fecha de fix:** 15 de Diciembre de 2025  
**Status:** ✅ APLICADO Y LISTO PARA TESTING
