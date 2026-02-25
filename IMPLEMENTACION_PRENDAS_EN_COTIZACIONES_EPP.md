# Implementación: Guardar Prendas en Cotizaciones (Tipo PARA_CLIENTE)

## Resumen ejecutivo

Se ha implementado un sistema para **guardar prendas en la misma cotización de EPPs**, utilizando la estructura existente de cotizaciones tipo "PARA_CLIENTE". Las prendas ahora se guardan igual que los EPPs en tablas separadas:

- `prenda_items_cot` - Datos de la prenda
- `prenda_img_cot` - Imágenes de la prenda
- `prenda_valor_unitario` - Precio unitario de la prenda

## Cambios realizados

### 1. Frontend - Eliminación de guardado inmediato de prendas
**Archivo:** `resources/views/asesores/pedidos/modals/modal-agregar-epp.blade.php`

**Problema:** Las prendas intentaban guardarse antes de crear la cotización, causando error `No se puede obtener el ID de cotización`.

**Solución:** 
- Se removió la llamada a `guardarPrendaEnBD()` que intentaba guardar las prendas individualmente
- Las prendas ahora se almacenan en memoria (en `window.itemsPedido`) junto con los EPPs
- Se guardan cuando se envía la cotización completa

**Cambios:**
```javascript
// ANTES
guardarPrendaEnBD(prendaData);  // ❌ Error: sin cotización_id

// AHORA
// NOTA: El guardado se realiza cuando se envía la cotización (junto con EPPs)
// No se intenta guardar la prenda individualmente aquí
```

### 2. Frontend - Actualización de validación de items
**Archivo:** `resources/views/asesores/cotizaciones/epp/create.blade.php` (líneas 796-828)

**Cambios:**
- Se cambió de filtrar solo `epps` a filtrar tanto `epps` como `prendas`
- Se agregó el campo `tipo` a cada item para identificarlo correctamente
- El mensaje de validación ahora dice "Agrega al menos un artículo (EPP o Prenda)"

```javascript
// ANTES
let epps = itemsPedido.filter(i => (i?.tipo || '').toLowerCase() === 'epp');

// AHORA
let items = itemsPedido.filter(i => {
    const tipo = (i?.tipo || '').toLowerCase();
    return tipo === 'epp' || tipo === 'prenda';
});
```

### 3. Frontend - Procesamiento de prendas en payload
**Archivo:** `resources/views/asesores/cotizaciones/epp/create.blade.php` (líneas 912-957)

**Cambios:**
- Se incluye el campo `tipo` en el payload de cada item
- Los iteradores ahora usan `items` en lugar de `epps`
- El tipo se preserva en el array `itemsPayload` para identificar en el backend

```javascript
const itemsPayload = items.map((item) => ({
    tipo: item.tipo || 'epp',  // ✨ Nuevo: preservar tipo
    imagenes_keep: [...],
    // ... resto de campos
}));
```

### 4. Backend - Separación de items por tipo
**Archivo:** `app/Infrastructure/Http/Controllers/CotizacionEppController.php` (línea 212)

**Cambios:**
- Se separan los items en dos categorías: `$epps` y `$prendas`
- Se procesan independientemente pero con la misma lógica
- Se mantienen arrays separados para IDs en edición: `$keptItemIds` y `$keptPrendaIds`

```php
$epps = array_filter($items, function($item) {
    $tipo = strtolower($item['tipo'] ?? 'epp');
    return $tipo === 'epp';
});

$prendas = array_filter($items, function($item) {
    $tipo = strtolower($item['tipo'] ?? 'epp');
    return $tipo === 'prenda';
});
```

### 5. Backend - Procesamiento de prendas
**Archivo:** `app/Infrastructure/Http/Controllers/CotizacionEppController.php` (líneas 350-475)

**Cambios:**
- Nuevo bloque `// ==================== PROCESAR PRENDAS ====================`
- Usa tablas `prenda_items_cot`, `prenda_img_cot`, `prenda_valor_unitario`
- Las imágenes se guardan en carpeta `cotizaciones/{id}/PRENDA/`
- Incluye soporte para edición (actualizar existentes, eliminar descartadas)

```php
$prendaId = DB::table('prenda_items_cot')->insertGetId([
    'cotizacion_id' => $cotizacion->id,
    'descripcion' => $nombre,
    'cantidad' => $cantidad,
    'observaciones' => $observ,
    'created_at' => now(),
    'updated_at' => now(),
]);
```

### 6. Backend - Limpieza en edición de prendas
**Archivo:** `app/Infrastructure/Http/Controllers/CotizacionEppController.php` (líneas 476-512)

**Cambios:**
- Se eliminan prendas que ya no vienen en la edición
- Se borran sus imágenes del storage
- Se usan los mismos tipos de validación que para EPPs

```php
$prendasToDelete = DB::table('prenda_items_cot')
    ->where('cotizacion_id', $cotizacion->id)
    ->when(count($keptPrendaIds) > 0, fn($q) => $q->whereNotIn('id', $keptPrendaIds))
    ->pluck('id');
```

### 7. Backend - Carga de prendas en edición
**Archivo:** `app/Infrastructure/Http/Controllers/Asesores/AsesoresController.php` (línea 263-309)

**Cambios:**
- Se cargan prendas junto con EPPs cuando se edita una cotización
- Se combinan en el array `$itemsUi` que se envía a la vista
- Se incluye el campo `tipo: 'prenda'` para identificarlas en el frontend

```php
$prendasUi = $prendas->map(function ($prenda) use ($valoresPrendas, $imagenesPrendas) {
    return [
        'tipo' => 'prenda',  // ✨ Identificador para el frontend
        'id' => (int)$prenda->id,
        'nombre' => $prenda->descripcion,
        // ... resto de campos
    ];
})->values()->all();

// Combinar EPPs y prendas
$itemsUi = array_merge($itemsUi, $prendasUi);
```

## Flujo de datos

```
┌─────────────────────────────────────────────────────────────┐
│ 1. Usuario abre modal "Agregar Prenda"                      │
│    - Ingresa: descripción, cantidad, valor unitario, fotos  │
├─────────────────────────────────────────────────────────────┤
│ 2. Click "Finalizar" → finalizarAgregarPrenda()             │
│    - Crear objeto prenda con sus fotos                      │
│    - Agregar a window.itemsPedido                           │
│    - Renderizar en tabla principal                          │
│    ❌ NO guardar en BD (no existe cotización_id todavía)    │
├─────────────────────────────────────────────────────────────┤
│ 3. Usuario agrega más prendas/EPPs (repetir paso 2)         │
├─────────────────────────────────────────────────────────────┤
│ 4. Click "Enviar" → enviarCotizacionEpp()                   │
│    - Validar cliente, tipo_venta, items                     │
│    - Crear FormData con items (EPP + PRENDA)                │
│    - Incluir field "tipo" en cada item                      │
│    - POST a /asesores/cotizaciones-epp                      │
├─────────────────────────────────────────────────────────────┤
│ 5. Backend - CotizacionEppController::store()               │
│    - Crear cotización                                       │
│    - Separar items: $epps y $prendas                        │
│    - Procesar EPPs → tablas epp_*                           │
│    - Procesar PRENDAS → tablas prenda_*                     │
│    - Guardar imágenes en storage/public/cotizaciones/{id}/  │
│    - Retornar success con número de cotización              │
├─────────────────────────────────────────────────────────────┤
│ 6. Frontend - Mostrar confirmación y redirigir              │
│    - Mensaje: "Cotización enviada - Número: XXX"            │
│    - Redirigir a lista de cotizaciones                      │
└─────────────────────────────────────────────────────────────┘
```

## Edición de cotizaciones

Cuando se edita una cotización existente:

1. **Cargar prendas existentes:**
   ```php
   $prendasUi = DB::table('prenda_items_cot')
       ->where('cotizacion_id', $cotizacion->id)
       // Cargar con imagenes y valores
       // Mapear a estructura UI
   ```

2. **Mostrar en frontend:**
   - Se cargan todos los items (EPP + PRENDA) en `window.itemsPedido`
   - Se renderiza cada one en la tabla según su `tipo`

3. **Actualizar:**
   - Si el item existe (tiene `id`): actualizar en BD
   - Si es nuevo: insertar en BD
   - Eliminar items que ya no vienen en el payload

## Estructura de tablas

### `prenda_items_cot`
```sql
id: bigint (PK)
cotizacion_id: bigint (FK → cotizaciones.id)
descripcion: varchar(255)
cantidad: int (default 1)
observaciones: text
created_at: timestamp
updated_at: timestamp
```

### `prenda_img_cot`
```sql
id: bigint (PK)
prenda_item_id: bigint (FK → prenda_items_cot.id)
ruta: varchar(255)
created_at: timestamp
updated_at: timestamp
```

### `prenda_valor_unitario`
```sql
id: bigint (PK)
prenda_item_id: bigint (FK → prenda_items_cot.id, UNIQUE)
valor_unitario: decimal(15,2)
created_at: timestamp
updated_at: timestamp
```

## Mejoras futuras

1. **Combinar totales:** Incluir suma de EPPs + PRENDAS en el total final de cotización
2. **Reportes:** Diferenciar prendas de EPPs en reportes/PDFs
3. **Validación:** Asegurar que al menos haya un EPP O una PRENDA (no vacío)
4. **Facturación:** Considerar si prendas deben afectar la facturación diferente

## Testing checklist

- ✅ Agregar solo prendas (sin EPPs)
- ✅ Agregar solo EPPs (sin prendas)
- ✅ Agregar mix de  EPPs y prendas
- ✅ Subir imágenes para prendas
- ✅ Editar cotización: cambiar prendas
- ✅ Editar cotización: eliminar prendas
- ✅ Editar cotización: agregar prendas nuevas
- ✅ Ver cotización completa (EPP + PRENDA) en lista
