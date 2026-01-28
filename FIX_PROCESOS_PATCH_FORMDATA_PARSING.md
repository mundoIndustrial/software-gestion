# FIX: PATCH FormData No Se Parseaba Correctamente

## Problema Encontrado

**Síntoma en los logs:**
```
[PROCESOS-ACTUALIZAR-PATCH] Recibido PATCH {"prenda_id":3472,"proceso_id":113,"request_keys":[],"ubicaciones":null,"observaciones":null}
```

**Causa raíz:** 
Cuando se envía FormData con método `PATCH` desde JavaScript (fetch), Laravel/PHP no parsea correctamente los parámetros porque `$request->all()` no funciona adecuadamente con PATCH + FormData.

**Evidencia del cliente:**
El cliente enviaba correctamente:
```javascript
📍 Ubicaciones añadidas al PATCH: (2) ['sadasdsad', 'ewrewrwerwerwe']
📝 Observaciones añadidas al PATCH: dfsfsdrtretertreterter
```

Pero el servidor recibía `request_keys: []` (vacío).

## Solución Implementada

Archivo: [app/Infrastructure/Http/Controllers/API/PrendaPedidoEditController.php](app/Infrastructure/Http/Controllers/API/PrendaPedidoEditController.php#L400)

### Cambio Principal

```php
// ============ FIX: PARSEAR FormData CON PATCH ============
// Cuando se envía FormData con PATCH desde fetch, PHP/Laravel a veces no parsea
// los parámetros correctamente. Necesitamos usar $_POST o forzar el parseo.
$inputData = $request->all();

// Si request->all() está vacío pero hay datos en $_POST, usarlos
if (empty($inputData) && !empty($_POST)) {
    $inputData = $_POST;
}
```

### Por qué funciona

1. **`$request->all()`** intenta parsear desde el stream de entrada HTTP
2. Cuando falla (común con PATCH + FormData), caemos a **`$_POST`** directamente
3. **`$_POST`** generalmente contiene los datos parseados por PHP, independientemente del método HTTP
4. Esto garantiza que obtenemos los datos sin importar cómo Laravel los parseó

### Cambios adicionales

- Todos los usos de `$request->all()` se reemplazaron por `$inputData`
- Se añadió un log de validación posterior al parseo para confirmar que los datos se recibieron

```php
\Log::info('[PROCESOS-ACTUALIZAR-PATCH] Datos después del FIX de parseo', [
    'data_keys' => array_keys($data),
    'ubicaciones_presente' => isset($data['ubicaciones']),
    'observaciones_presente' => isset($data['observaciones']),
    'ubicaciones_valor' => $data['ubicaciones'] ?? 'NULL',
    'observaciones_valor' => substr($data['observaciones'] ?? '', 0, 100)
]);
```

## Flujo de Datos (Después del Fix)

```
Cliente (fetch PATCH FormData)
    ↓
    [ubicaciones: JSON.stringify([...])
     observaciones: "texto"
     imagenes_nuevas: File[]]
    ↓
Servidor PHP
    ↓
    request->all() → $inputData
    ↓
    Si $inputData vacío → $_POST → $inputData
    ↓
    $data = $inputData
    ↓
    Procesar ubicaciones (json_decode si es string)
    Procesar observaciones (guardar directamente)
    Procesar imágenes (subir archivos)
    ↓
    Guardar en BD
```

## Pruebas Recomendadas

1. **Editar un proceso existente** con ubicaciones y observaciones
2. **Verificar los logs** para confirmar que `request_keys` NO está vacío
3. **Confirmar en BD** que las ubicaciones y observaciones se guardaron
4. **Agregar/eliminar imágenes** junto con cambios de ubicaciones

## Archivos Modificados

- [app/Infrastructure/Http/Controllers/API/PrendaPedidoEditController.php](app/Infrastructure/Http/Controllers/API/PrendaPedidoEditController.php#L400-L421)
  - Líneas 400-421: Agregación del fix de parseo FormData
  - Línea 428: Log de validación post-parseo
  - Línea 455: Uso de `$inputData` en lugar de `$request->all()`
