# 🔧 FIX: Fotos No Se Guardan en Cotizaciones

## 📋 Problema Identificado

Las fotos agregadas a las cotizaciones **se mostraban en el preview durante la creación** pero **nunca se guardaban** en la base de datos. Cuando el usuario volvía a ver la cotización guardada, las fotos desaparecían.

### Síntomas
- ✅ Fotos visibles en preview mientras se agregan
- ✅ El usuario ve que se agregaron correctamente
- ❌ Al guardar la cotización, las fotos NO llegan al backend (`all_files_keys: []`)
- ❌ Las fotos NO se guardan en la base de datos
- ❌ Al recargar, las fotos desaparecen

### Causa Raíz

En el archivo [template-producto.blade.php](resources/views/components/template-producto.blade.php), el **drag-drop handler NO estaba recibiendo correctamente el reference del elemento `<label>`** (dropZone).

**Código original (INCORRECTO):**
```html
<label ondrop="manejarDrop(event)" ...>
    <input onchange="agregarFotos(this.files, this.closest('label').nextElementSibling)" ...>
</label>
```

**Problemas:**
1. **Línea 1**: `ondrop="manejarDrop(event)"` no pasaba una referencia a `this` (el label)
2. **Línea 2**: El `onchange` pasaba `.nextElementSibling` (el div fotos-preview) en lugar de el `<label>`
3. **Resultado**: Cuando `agregarFotos()` intentaba encontrar `.producto-card` desde el dropZone, fallaba

---

## ✅ Solución Implementada

### Archivos Modificados

#### 1. [template-producto.blade.php](resources/views/components/template-producto.blade.php)

**Cambio en FOTOS PRENDA (línea ~56):**

```diff
- <label ... ondrop="manejarDrop(event)" ...>
-     <input ... onchange="agregarFotos(this.files, this.closest('label').nextElementSibling)" ...>
+ <label ... ondrop="manejarDrop(event, this)" ...>
+     <input ... onchange="agregarFotos(this.files, this.closest('label'))" ...>
```

**Cambio en FOTOS TELA (línea ~120):**

```diff
- <label ... ondrop="manejarDrop(event)" ...>
-     <input ... onchange="agregarFotoTela(this)" ...>
+ <label ... ondrop="manejarDrop(event, this)" ...>
+     <input ... onchange="agregarFotoTela(this)" ...>
```

#### 2. [productos.js](public/js/asesores/cotizaciones/productos.js)

**Actualizar función `manejarDrop()` (línea ~126):**

```javascript
// ANTES:
function manejarDrop(event) {
    event.preventDefault();
    event.stopPropagation();
    const dropZone = event.currentTarget;
    dropZone.classList.remove('drag-over');
    agregarFotos(event.dataTransfer.files, dropZone);
}

// DESPUÉS:
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

---

## 🧪 Verificación de la Solución

### Cómo Probar

1. **Crear una nueva cotización:**
   - Ir a crear cotización
   - Agregar una prenda
   - Arrastra una foto a la zona "FOTOS PRENDA" (drag-drop)
   - Verifica que aparezca en el preview ✅

2. **Verificar que se guarda en memoria:**
   - Abre la consola de desarrollador (F12)
   - Ejecuta: `console.log(window.imagenesEnMemoria.prendaConIndice)`
   - Deberías ver un array con los archivos

3. **Guardar la cotización:**
   - Haz clic en "Guardar" o "Guardar Borrador"
   - Abre la consola
   - Debería haber un log: `✅ Foto de prenda guardada`

4. **Verificar en Base de Datos:**
   - Ejecuta: `SELECT COUNT(*) FROM prenda_fotos WHERE prenda_id IN (SELECT id FROM prendas WHERE cotizacion_id = 59);`
   - Debería mostrar > 0 registros

5. **Recargar la cotización:**
   - Cierra la página
   - Abre la cotización nuevamente
   - Las fotos deberían aparecer ✅

---

## 🔍 Cómo Funciona Ahora

### Flow de Adición de Fotos

1. **Usuario arrastra foto** → `ondrop="manejarDrop(event, this)"`
2. **Se pasa el `<label>` correctamente** como parámetro `dropZone`
3. **`agregarFotos()` recibe:**
   - `files`: Array de archivos
   - `dropZone`: El elemento `<label>` que contiene el input
4. **Se encuentra el `.producto-card` padre** → `dropZone.closest('.producto-card')`
5. **Se calcula el `prendaIndex`** → posición de la prenda en el formulario
6. **Se guarda en `window.imagenesEnMemoria.prendaConIndice`:**
   ```javascript
   window.imagenesEnMemoria.prendaConIndice.push({
       file: file,
       prendaIndex: prendaIndex
   });
   ```
7. **Se muestra en preview** → Inmediatamente visible para el usuario
8. **Cuando se guarda** → `guardado.js` lee de `window.imagenesEnMemoria` y crea FormData
9. **Backend procesa** → `procesarImagenesCotizacion()` recibe los archivos

---

## 📊 Cambios Resumidos

| Aspecto | Antes | Después |
|---------|-------|---------|
| **Drag-drop handler** | `manejarDrop(event)` | `manejarDrop(event, this)` |
| **Reference pasada** | Solo event | event + dropZone explícito |
| **Compatibilidad** | Solo drag-drop | Drag-drop + fallback a currentTarget |
| **Fotos en memoria** | ❌ Vacío cuando se guarda | ✅ Lleno correctamente |
| **Guardado en BD** | ❌ No | ✅ Sí |

---

## ⚠️ Notas Importantes

- **Compatibilidad hacia atrás:** Se agregó un fallback para compatibilidad
- **No afecta otras funciones:** Las funciones de eliminación y preview no se modificaron
- **Validación de límites:** Se mantiene el límite de 3 fotos por prenda

---

## 📝 Registro de Cambios

- **Fecha:** 2024-12-15
- **Cambios:** 
  - ✅ Corregido `ondrop` en `template-producto.blade.php` (línea 56, 120)
  - ✅ Actualizado `manejarDrop()` en `productos.js` (línea 126)
- **Archivos modificados:** 2
- **Líneas de código:** ~15
- **Pruebas:** Manual

---

## 🐛 Si Aún No Funciona

Si después de estos cambios las fotos aún no se guardan:

1. **Limpia el cache del navegador** (Ctrl+Shift+Del)
2. **Recarga la página** (Ctrl+F5)
3. **Verifica la consola** para errores
4. **Ejecuta en consola:**
   ```javascript
   console.log('window.fotosSeleccionadas:', window.fotosSeleccionadas);
   console.log('window.imagenesEnMemoria:', window.imagenesEnMemoria);
   ```
5. Si el problema persiste, revisa [ANALISIS_CAMPOS_COTIZACIONES_PARA_TESTS.md](ANALISIS_CAMPOS_COTIZACIONES_PARA_TESTS.md)

