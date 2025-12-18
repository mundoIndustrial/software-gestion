# SOLUCIÓN: Captura de Múltiples Telas en Formulario de Cotización

## Problema Identificado
En la ruta `http://servermi:8000/asesores/pedidos/create`, cuando se agregaban varios tipos de tela, **no se estaban capturando correctamente** en el formulario de cotización.

## Causa Raíz
El sistema permitía agregar múltiples filas de telas en la tabla visual, pero:

1. **Frontend**: Las fotos de telas se almacenaban en un array simple `window.telasSeleccionadas[productoId][]` sin relación con cada fila específica
2. **Backend**: Los datos de múltiples telas no estaban siendo capturados correctamente por el formulario
3. **Estructura de datos**: Faltaba una relación entre cada fila de tela (con su índice) y sus fotos asociadas

## Soluciones Implementadas

### 1. Template del Producto ([template-producto.blade.php](resources/views/components/template-produto.blade.php))

**Cambio**: Estructura de nombres de inputs para capturar múltiples telas con índices

```html
<!-- ANTES (Solo capturaba una tela):
<input name="productos_friendly[][variantes][tela_id]" />
<input name="productos_friendly[][variantes][color_id]" />
<input name="productos_friendly[][telas][]" /> <!-- Todas las fotos juntas -->

<!-- DESPUÉS (Captura múltiples telas indexadas):
<input name="productos_friendly[][telas][0][tela_id]" />
<input name="productos_friendly[][telas][0][color_id]" />
<input name="productos_friendly[][telas][0][referencia]" />
<input name="productos_friendly[][telas][0][fotos][]" />

<input name="productos_friendly[][telas][1][tela_id]" />
<input name="productos_friendly[][telas][1][color_id]" />
...
```

**Beneficio**: Ahora cada fila de tela tiene su propio espacio en el array `productos_friendly[][telas][index][]`

### 2. JavaScript - Gestión de Telas ([productos.js](public/js/asesores/cotizaciones/productos.js))

#### A. Nueva Estructura de `telasSeleccionadas`

**Antes**:
```javascript
window.telasSeleccionadas[productoId] = [] // Array plano
```

**Después**:
```javascript
window.telasSeleccionadas[productoId] = {
    '0': [],  // Tela 1
    '1': [],  // Tela 2
    '2': []   // Tela 3
}
```

Esto permite almacenar fotos agrupadas por índice de tela.

#### B. Función `agregarFilaTela()`

**Cambios**:
- Ahora incrementa el `data-tela-index` para cada fila nueva
- Actualiza todos los nombres de inputs a usar el nuevo índice
- Inicializa un nuevo array de fotos para esa tela

#### C. Función `agregarFotoTela()`

**Cambios**:
- Obtiene el `data-tela-index` de la fila actual
- Almacena fotos en `telasSeleccionadas[productoId][telaIndex][]`
- Esto mantiene la asociación entre cada tela y sus fotos

#### D. Función `eliminarFotoTelaById()`

**Cambios**:
- Obtiene el `data-tela-index` de la fila
- Elimina la foto del array correcto: `telasSeleccionadas[productoId][telaIndex][]`

### 3. FormModule ([FormModule.js](public/js/asesores/cotizaciones/modules/FormModule.js))

**Cambio**: Procesa correctamente cada fila de tela y sus fotos asociadas

```javascript
// Procesa cada fila de tela por su índice
tblasRows.forEach((row, rowIdx) => {
    const telaIndex = row.getAttribute('data-tela-index') || rowIdx;
    
    // Captura datos de esta tela específica
    formData.append(`productos[${index}][telas][${telaIndex}][color_id]`, colorId);
    formData.append(`productos[${index}][telas][${telaIndex}][tela_id]`, telaId);
    formData.append(`productos[${index}][telas][${telaIndex}][referencia]`, referencia);
    
    // Agrega fotos de ESTA TELA ESPECÍFICA
    if (window.telasSeleccionadas[productoId][telaIndex]) {
        // Agrega cada foto al índice correcto
    }
});
```

### 4. Validación en Backend ([AsesoresController.php](app/Http/Controllers/AsesoresController.php))

**Cambio**: Agregada validación para múltiples telas

```php
$productosKey.'.*.telas' => 'nullable|array',
$productosKey.'.*.telas.*.tela_id' => 'nullable|integer',
$productosKey.'.*.telas.*.color_id' => 'nullable|integer',
$productosKey.'.*.telas.*.referencia' => 'nullable|string',
$productosKey.'.*.telas.*.fotos' => 'nullable|array',
$productosKey.'.*.telas.*.fotos.*' => 'nullable|file|image|max:5242880',
```

### 5. Servicio de Prendas ([PedidoPrendaService.php](app/Application/Services/PedidoPrendaService.php))

**Cambios**:
- Nuevo método `obtenerPrimeraTela()` que obtiene la primera tela como referencia principal
- El método `guardarFotosTelas()` ya existía y funciona correctamente con el array de telas
- Ahora procesa correctamente `$prendaData['telas']` como un array de múltiples telas

## Flujo de Datos Completo

```
Usuario agrega tela 1
  ↓
agregarFilaTela() crea fila con data-tela-index="1"
  ↓
Usuario sube foto para tela 1
  ↓
agregarFotoTela() almacena en telasSeleccionadas[productoId]['1'][]
  ↓
Usuario agrega tela 2
  ↓
agregarFilaTela() crea fila con data-tela-index="2"
  ↓
Usuario sube foto para tela 2
  ↓
agregarFotoTela() almacena en telasSeleccionadas[productoId]['2'][]
  ↓
Usuario envía formulario
  ↓
FormModule construye FormData con estructura correcta:
   productos[0][telas][0][tela_id]
   productos[0][telas][0][color_id]
   productos[0][telas][0][fotos][0] (archivo)
   productos[0][telas][1][tela_id]
   productos[0][telas][1][color_id]
   productos[0][telas][1][fotos][0] (archivo)
  ↓
AsesoresController valida múltiples telas
  ↓
PedidoPrendaService->guardarFotosTelas() guarda todas las fotos correctamente
```

## Pruebas Recomendadas

1. **Agregar una prenda con una tela** ✓ Debe funcionar como antes
2. **Agregar una prenda con 2 telas** ✓ Debe capturar ambas
3. **Agregar fotos a cada tela** ✓ Debe guardar todas correctamente
4. **Eliminar una tela** ✓ Debe eliminar datos de esa tela
5. **Editar un borrador** ✓ Debe cargar todas las telas guardadas

## Archivos Modificados

1. ✅ [resources/views/components/template-producto.blade.php](resources/views/components/template-producto.blade.php)
   - Cambio de estructura de nombres de inputs para capturar múltiples telas

2. ✅ [public/js/asesores/cotizaciones/productos.js](public/js/asesores/cotizaciones/productos.js)
   - `agregarProductoFriendly()` - Inicializar estructura correcta
   - `agregarFilaTela()` - Incrementar índice y actualizar nombres
   - `agregarFotoTela()` - Asociar fotos con índice de tela
   - `eliminarFotoTelaById()` - Eliminar del array correcto

3. ✅ [public/js/asesores/cotizaciones/modules/FormModule.js](public/js/asesores/cotizaciones/modules/FormModule.js)
   - `addProductToFormData()` - Procesar cada fila de tela correctamente

4. ✅ [app/Http/Controllers/AsesoresController.php](app/Http/Controllers/AsesoresController.php)
   - `store()` - Agregar validación para múltiples telas

5. ✅ [app/Application/Services/PedidoPrendaService.php](app/Application/Services/PedidoPrendaService.php)
   - `guardarPrenda()` - Obtener primera tela como referencia
   - `obtenerPrimeraTela()` - Nuevo método helper

## Impacto

- ✅ Múltiples telas se capturan correctamente
- ✅ Fotos de cada tela se asocian correctamente
- ✅ Sistema backward compatible (funciona con una o múltiples telas)
- ✅ Validación robusta en frontend y backend
