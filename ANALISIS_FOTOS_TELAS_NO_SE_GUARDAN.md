# 🔍 ANÁLISIS: POR QUÉ NO SE GUARDAN LAS FOTOS DE TELAS

## 📋 PROBLEMA IDENTIFICADO

Según los **LOGS DEL SERVIDOR** (laravel.log):

```
[2025-12-18 14:27:03] local.INFO: Telas encontradas {"key":"productos.0.telas","count":0}
```

Esto significa: **FormModule buscó archivos de telas pero encontró CERO (0)**.

---

## ❓ QUÉ ESTÁ PASANDO

### En los datos que LLEGAN al servidor:
- ✅ `telas_multiples`: Hay 2 telas guardadas (`YTYRYR` y `RYTT`)
- ✅ `variantes`: Se guardaron correctamente
- ❌ `productos[0][telas][0][fotos]`: NO hay archivos
- ❌ `productos[0][telas][1][fotos]`: NO hay archivos

### Por qué NO llegan los archivos:

1. **El frontend tiene la estructura CORRECTA** para capturar fotos:
   - Tabla de "Color, Tela y Referencia"
   - Inputs `type="file"` para cada tela
   - Función `agregarFotoTela()` para guardarlas en memoria

2. **PERO** el usuario NO está cargando fotos en esos inputs, porque:
   - **Opción A**: El usuario cargó telas en otro lugar (Ej: a través de un modal o script)
   - **Opción B**: El usuario NO cargó fotos en absoluto
   - **Opción C**: El evento `onchange` no se dispara (bug del navegador)

---

## ✅ SOLUCIÓN

### Opción 1: Si el usuario SÍ intentó cargar fotos

1. **Abre la consola del navegador** (F12 → Console)
2. **Carga una foto** en la tabla de telas
3. Busca mensajes que digan:
   - `🔥 agregarFotoTela LLAMADA:`
   - `✅ Foto 1 de tela 0 guardada:`

4. Si ves estos mensajes = **El frontend funciona**. Carga todas las fotos y enviá nuevamente.

### Opción 2: Si las fotos NO se cargan

Si después de cargar fotos en los inputs NO ves los mensajes de consola:

```javascript
// Ejecuta esto en la consola del navegador
console.log('telasSeleccionadas:', window.telasSeleccionadas);
```

Si muestra `{producto-xxx: {0: [], 1: []}}` (arrays vacíos), el problema es que:
- El evento `onchange` no está funcionando
- Los archivos no se capturan

**Solución temporal**: Recarga la página y vuelve a intentar

---

## 🔧 CAMBIOS IMPLEMENTADOS

### 1. Función `agregarFilaTela()` mejorada
- **Antes**: Solo reemplazaba el primer `[número]` en los nombres
- **Ahora**: Busca específicamente `[telas][número]` y actualiza solo eso
- **Logging mejorado**: Muestra exactamente qué inputs se actualizaron

### 2. Función `agregarFotoTela()` mejorada
- **Más logging**: Ahora muestra:
  - Cuándo se llama la función
  - Cuántos archivos se cargaron
  - El estado de `telasSeleccionadas`
  - Si el contenedor se encontró

### 3. FormModule.js (sin cambios necesarios)
- Ya está correcto
- Busca archivos en `window.telasSeleccionadas[productoId][telaIndex]`
- Los envía correctamente al servidor

---

## 📊 FLUJO CORRECTO (DEL FORMULARIO)

```
1. Usuario hace clic en "+ Agregar Tela"
   ↓
2. Se crea nueva fila con data-tela-index="1"
   (Los inputs tienen nombres actualizados a [telas][1][...])
   ↓
3. Usuario carga fotos en esa fila
   ↓
4. agregarFotoTela() se dispara (evento onchange)
   ↓
5. Las fotos se guardan en:
   window.telasSeleccionadas[productoId][1][] = [File1, File2, ...]
   ↓
6. Usuario hace clic en "Enviar Cotización"
   ↓
7. FormModule construye FormData con:
   productos[0][telas][1][fotos][0] = File1
   productos[0][telas][1][fotos][1] = File2
   ↓
8. CotizacionPrendaController recibe y procesa
   ↓
9. Las fotos se guardan en:
   /storage/app/public/telas/cotizaciones/...
   BD: prenda_tela_fotos_cot
```

---

## 🎯 PRÓXIMOS PASOS

### Para el usuario:
1. Intenta nuevamente cargando las fotos
2. Si no funciona, abre la consola (F12) y verifica los mensajes

### Para el equipo técnico:
Si el usuario reporta que AÚN NO funciona:
1. Pedir screenshots de la consola
2. Verificar que `input type="file" ... onchange="agregarFotoTela(this)"`
3. Revisar si hay conflictos de JavaScript
4. Posible issue: Input dentro de un modal o elemento dinámico

---

## 🔗 ARCHIVOS INVOLUCRADOS

- `public/js/asesores/cotizaciones/productos.js` ← `agregarFilaTela()` y `agregarFotoTela()`
- `public/js/asesores/cotizaciones/modules/FormModule.js` ← Envío de datos
- `resources/views/components/template-producto.blade.php` ← Estructura HTML
- `app/Infrastructure/Http/Controllers/CotizacionPrendaController.php` ← Backend

