# FIX COMPLETO: PATCH FormData No Se Parseaba - Ubicaciones e Imágenes No Se Guardaban

## Problema Encontrado

**Síntoma en los logs:**
```
[PROCESOS-ACTUALIZAR-PATCH] Recibido PATCH {"prenda_id":3472,"proceso_id":113,"request_keys":[],"ubicaciones":null,"observaciones":null}
```

**Error 422 posterior:**
Cuando intentábamos guardar, Laravel retornaba error 422 (Unprocessable Content) porque no reconocía la solicitud como válida.

**Causa raíz:** 
Cuando se envía FormData con método PATCH desde JavaScript (fetch), Laravel/PHP no parsea correctamente los parámetros. `$request->all()` devolvía datos vacíos, y `$_POST` también estaba vacío porque PHP/Laravel no procesa FormData en solicitudes PATCH de la misma manera que en POST.

**Evidencia del cliente:**
El cliente enviaba correctamente:
```javascript
📍 Ubicaciones añadidas al PATCH: ['sadasdsad', 'ewrewrwerwerwe']
📝 Observaciones añadidas al PATCH: dfsfsdrtretertreterter
```

Pero el servidor recibía `request_keys: []` (vacío).

## Solución Implementada - Cuatro Cambios Estratégicos

### 1️⃣ CLIENTE: Usar POST con `_method=PATCH` en FormData

**Archivo:** `public/js/componentes/modal-novedad-edicion.js`

```javascript
// Agregar _method=PATCH al FormData PRIMERO
patchFormData.append('_method', 'PATCH');

// IMPORTANTE: Ahora usar POST en lugar de PATCH
const patchResponse = await fetch(`/api/prendas-pedido/${prendaIdInt}/procesos/${procesoEditado.id}`, {
    method: 'POST',  // ✅ POST no PATCH
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
    },
    body: patchFormData
});
```

**Por qué funciona:**
- FormData se parsea perfectamente con POST
- Laravel reconoce automáticamente `_method=PATCH` dentro del POST via middleware
- Laravel routea la solicitud como si fuera PATCH (va al mismo handler)
- Los archivos se suben correctamente

### 2️⃣ SERVIDOR: Aceptar POST además de PATCH en la ruta

**Archivo:** `routes/web.php` (línea ~612)

```php
// ✅ ANTES:
Route::patch('/{prendaId}/procesos/{procesoId}', ...);

// ✅ DESPUÉS:
Route::match(['patch', 'post'], '/{prendaId}/procesos/{procesoId}', ...);
```

Esto permite que la ruta acepte TANTO POST como PATCH, cubriendo ambos casos.

### 3️⃣ SERVIDOR: Fallback robusto de parseo

**Archivo:** `app/Infrastructure/Http/Controllers/API/PrendaPedidoEditController.php` (línea ~410)

```php
// Intentar parseo normal primero
$inputData = $request->all();

// Fallback: Si está vacío, intentar $_POST directamente
if (empty($inputData) && !empty($_POST)) {
    $inputData = $_POST;
}

// Usar $inputData en lugar de $request->all() para todo lo demás
$data = $inputData;
```

### 4️⃣ CLIENTE: Mejor manejo de errores con detalles

**Archivo:** `public/js/componentes/modal-novedad-edicion.js` (línea ~546)

```javascript
if (!patchResponse.ok) {
    console.error('[modal-novedad-edicion] 🚨 Error del servidor:', {
        status: patchResponse.status,
        message: patchResult.message,
        errors: patchResult.errors  // Mostrar errores específicos
    });
    
    // Construir mensaje detallado que el usuario puede entender
    let errorMsg = `Error ${patchResponse.status}: ${patchResult.message || 'Desconocido'}`;
    if (patchResult.errors) {
        const errorDetails = Object.entries(patchResult.errors)
            .map(([field, msgs]) => `${field}: ${Array.isArray(msgs) ? msgs.join(', ') : msgs}`)
            .join('\n');
        errorMsg += `\n\nDetalles:\n${errorDetails}`;
    }
    throw new Error(errorMsg);
}
```

## Flujo de Datos (Después del Fix)

```
┌─────────────────────────────────────┐
│ Cliente (JavaScript - fetch)        │
│  POST /api/prendas-pedido/...       │
│  FormData {                         │
│    _method: 'PATCH',               │
│    ubicaciones: JSON.stringify(...) │
│    observaciones: "texto",          │
│    imagenes_nuevas: File[]          │
│  }                                  │
└──────────────┬──────────────────────┘
               ↓
┌─────────────────────────────────────┐
│ Laravel Middleware                  │
│  Detecta _method=PATCH              │
│  Transforma a método virtual PATCH  │
└──────────────┬──────────────────────┘
               ↓
┌─────────────────────────────────────┐
│ Router (web.php)                    │
│  Route::match(['patch', 'post'], ...)│
│  Routea a método PATCH handler      │
└──────────────┬──────────────────────┘
               ↓
┌─────────────────────────────────────┐
│ Controller::actualizarProcesoEspecifico│
│  $inputData = $request->all() ✅    │
│  FormData parsed correctamente!     │
│  POST parsea FormData perfectamente │
└──────────────┬──────────────────────┘
               ↓
┌─────────────────────────────────────┐
│ Procesar datos                      │
│  • Ubicaciones: json_decode(...)    │
│  • Observaciones: guardar           │
│  • Imágenes: $request->file(...)    │
│  • Tallas: procesar                 │
└──────────────┬──────────────────────┘
               ↓
┌─────────────────────────────────────┐
│ Base de datos                       │
│  ✅ ubicaciones guardadas           │
│  ✅ observaciones guardadas         │
│  ✅ imágenes guardadas              │
│  ✅ tallas guardadas                │
└─────────────────────────────────────┘
```

## Archivos Modificados

### 1. `public/js/componentes/modal-novedad-edicion.js`
- **Línea 475-478:** Agregar `patchFormData.append('_method', 'PATCH');`
- **Línea 540:** Cambiar `method: 'PATCH'` a `method: 'POST'`
- **Línea 546-560:** Mejorar error handling con detalles específicos

### 2. `routes/web.php`
- **Línea 612:** Cambiar `Route::patch(...)` a `Route::match(['patch', 'post'], ...)`

### 3. `app/Infrastructure/Http/Controllers/API/PrendaPedidoEditController.php`
- **Línea 410-420:** Agregar fallback de parseo con `$_POST`
- **Línea 425:** Usar `$inputData` en lugar de `$request->all()`
- **Línea 430-437:** Mejorar logs con información de método y `_method`

## Resultado Esperado

✅ Las ubicaciones se guardan en `pedidos_procesos_prenda_detalles.ubicaciones`
✅ Las observaciones se guardan correctamente
✅ Las imágenes se suben y guardan en `pedidos_procesos_imagenes`
✅ Los logs muestran que `request_keys` contiene los parámetros reales
✅ Errores de validación 422 muestran detalles específicos al usuario

## Por qué fue difícil

1. FormData + PATCH es una combinación problemática en PHP/Laravel
2. Laravel espera FormData principalmente con POST, no PATCH
3. El middleware de `_method` no es evidente para desarrolladores
4. El cliente no mostraba los errores específicos del servidor
5. La diferencia entre `$request->all()` y `$_POST` es sutil pero crítica

## Pruebas Recomendadas

1. Editar un proceso existente con ubicaciones
2. Verificar logs: `request_keys` debe tener valores
3. Confirmar en BD que ubicaciones se guardaron
4. Agregar/eliminar imágenes junto con cambios
5. Intentar con datos inválidos y ver errores específicos
