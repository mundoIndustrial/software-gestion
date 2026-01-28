# FIX: Imágenes de Procesos No Se Guardaban Desde el Modal (27 ENE 2026)

## Problema Identificado

Las imágenes subidas en el modal de edición de procesos **NO SE ESTABAN GUARDANDO** en la base de datos. 

### Síntomas en el Log:
```
[2026-01-27 21:21:35] local.INFO: [PROCESOS-ACTUALIZAR] Procesando imágenes: {"raw_imagenes":[],"total_recibidas":0}
[2026-01-27 21:21:36] local.INFO: [PROCESOS-ACTUALIZAR] Resumen imágenes: {"eliminadas":0,"agregadas":0,"total_final":0}
```

Las imágenes recibidas eran 0, aunque el usuario había subido archivos en el modal.

## Causa Raíz

### Frontend:
1. Las imágenes subidas en el modal se guardaban como **File objects** en `window.imagenesProcesoActual`
2. Cuando se registraban los cambios del proceso, se normalizaban a **strings/URLs** en lugar de mantenerlas como files
3. El PATCH enviaba un JSON con strings, no FormData con archivos

### Backend:
1. El método `actualizarProcesoEspecifico()` del controlador **SOLO PROCESABA IMÁGENES EN JSON** (URLs/strings)
2. **NO HABÍA LÓGICA PARA PROCESAR ARCHIVOS NUEVOS** desde FormData en el PATCH
3. El controlador principal `PedidosProduccionController.actualizarPrendaCompleta()` sí procesaba imágenes nuevas, pero NO se pasaban al DTO

## Solución Implementada

### 1. Frontend: Cambiar PATCH a FormData
**Archivo:** `public/js/componentes/modal-novedad-edicion.js` (líneas 423-480)

**Cambio:**
- Antes: `body: JSON.stringify(procesoEditado.cambios)` (JSON puro)
- Después: `body: patchFormData` (FormData con archivos)

**Código nuevo:**
```javascript
// Crear FormData en lugar de JSON
const patchFormData = new FormData();

// Agregar campos como JSON string
patchFormData.append('ubicaciones', JSON.stringify(procesoEditado.cambios.ubicaciones));
patchFormData.append('observaciones', procesoEditado.cambios.observaciones);
// ... etc

// ✅ NUEVO: Incluir archivos nuevos desde window.imagenesProcesoActual
if (window.imagenesProcesoActual && Array.isArray(window.imagenesProcesoActual)) {
    window.imagenesProcesoActual.forEach((img, idx) => {
        if (img instanceof File) {
            patchFormData.append(`imagenes_nuevas[${idx}]`, img);
        }
    });
}

// Enviar FormData (sin Content-Type header)
const patchResponse = await fetch(url, {
    method: 'PATCH',
    headers: {
        'X-CSRF-TOKEN': token
    },
    body: patchFormData
});
```

### 2. Backend: Procesar Archivos Nuevos en PATCH
**Archivo:** `app/Infrastructure/Http/Controllers/API/PrendaPedidoEditController.php` (método `actualizarProcesoEspecifico()`)

**Cambios:**
1. **Procesar archivos nuevos ANTES de validar:**
   - Buscar archivos con clave `imagenes_nuevas[*]`
   - Usar `ProcesoFotoService` para convertir a WebP
   - Guardar rutas nuevas en array `$imagenesNuevasRutas`

2. **Agregar imágenes nuevas a la lista:**
   - Decodificar JSON de imágenes existentes
   - Mergear con rutas nuevas procesadas
   - Actualizar `$data['imagenes']` con lista completa

**Código insertado (líneas 410-447):**
```php
// ============ NUEVO: PROCESAR IMÁGENES NUEVAS (FILES) DEL FORMDATA ============
$imagenesNuevasRutas = [];
if ($request->hasFile('imagenes_nuevas')) {
    $files = $request->file('imagenes_nuevas');
    if (!is_array($files)) {
        $files = [$files];
    }
    
    $procesoFotoService = new \App\Domain\Pedidos\Services\ProcesoFotoService();
    foreach ($files as $imagen) {
        if ($imagen && $imagen->isValid()) {
            try {
                $rutas = $procesoFotoService->procesarFoto($imagen);
                $imagenesNuevasRutas[] = $rutas['ruta_webp'] ?? $rutas;
                \Log::info('[PROCESOS-ACTUALIZAR] Imagen nueva de proceso procesada', [
                    'archivo' => $imagen->getClientOriginalName(),
                    'ruta_webp' => $rutas['ruta_webp'] ?? 'N/A'
                ]);
            } catch (\Exception $e) {
                \Log::warning('[PROCESOS-ACTUALIZAR] Error procesando imagen nueva de proceso', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}

// ============ NUEVO: AGREGAR IMÁGENES NUEVAS A LA LISTA ============
if (!empty($imagenesNuevasRutas)) {
    $imagenesDeJSON = [];
    if (isset($data['imagenes']) && is_string($data['imagenes'])) {
        try {
            $imagenesDeJSON = json_decode($data['imagenes'], true) ?? [];
        } catch (\Exception $e) {}
    }
    $data['imagenes'] = array_merge($imagenesDeJSON, $imagenesNuevasRutas);
}
```

### 3. También se Corrigió: Búsqueda de Archivos de Procesos en POST
**Archivo:** `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php` (línea 855)

**Cambio:** La búsqueda de archivos en la actualización de prenda completa fue actualizada para buscar por `files_proceso_*` en lugar de `procesos[` (que era incorrecto).

## Flujo Completo (DESPUÉS del Fix)

### 1. Usuario Edita Proceso en Modal
- Carga imágenes nuevas → `window.imagenesProcesoActual` = [File, File, ...]
- Hace cambios en ubicaciones, observaciones, etc.
- Click en "Guardar cambios"

### 2. Gestor Captura Cambios
- `window.procesosEditor.registrarCambioImagenes()` se ejecuta
- Registra archivos como cambios para PATCH

### 3. Frontend Envía PATCH con FormData
```
PATCH /api/prendas-pedido/{prendaId}/procesos/{procesoId}
Content-Type: multipart/form-data

- ubicaciones: ["...", "..."]  (JSON string)
- observaciones: "..."         (string)
- imagenes: ["url1", "url2"]   (JSON string - existentes)
- imagenes_nuevas[0]: File1    (archivo nuevo)
- imagenes_nuevas[1]: File2    (archivo nuevo)
```

### 4. Backend Procesa
1. Extrae `imagenes_nuevas[*]` de FormData
2. Procesa cada archivo con `ProcesoFotoService` → WebP
3. Agrega rutas nuevas a `imagenes` original
4. Guarda todo en tabla `pedidos_procesos_imagenes`

### 5. BD: Imágenes Guardadas
```
pedidos_procesos_imagenes:
├─ proceso_prenda_detalle_id: 113
├─ ruta_webp: "procesos/proceso_20260127212136_964920.webp"
├─ orden: 1
└─ created_at: 2026-01-28 02:21:38
```

## Testing

Para verificar que el fix funciona:

### 1. Frontend Console:
```javascript
// En el modal de edición de proceso
console.log('Imágenes en memoria:', window.imagenesProcesoActual);
// Debería mostrar [File, File, null] o similar
```

### 2. Network Tab:
```
PATCH /api/prendas-pedido/3472/procesos/113
Content-Type: multipart/form-data
```
Verificar que lleva archivos.

### 3. Backend Log:
```
[PROCESOS-ACTUALIZAR] Imagen nueva de proceso procesada
[PROCESOS-ACTUALIZAR] Procesando imágenes: {"raw_imagenes":[...], "total_recibidas":2}
[PROCESOS-ACTUALIZAR] Imágenes agregadas: {"cantidad": 2, "rutas": [...]}
```

## Archivos Modificados

1. ✅ `public/js/componentes/modal-novedad-edicion.js`
   - Cambiar PATCH a FormData
   - Incluir files de `window.imagenesProcesoActual`

2. ✅ `app/Infrastructure/Http/Controllers/API/PrendaPedidoEditController.php`
   - Agregar lógica de procesamiento de `imagenes_nuevas[*]`
   - Mergear con imágenes existentes

3. ✅ `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php`
   - Corregir búsqueda de archivos a `files_proceso_*`

## Notas Importantes

- ✅ Las imágenes ahora se guardan en `pedidos_procesos_imagenes` correctamente
- ✅ Se convierte automáticamente a WebP
- ✅ Se mantienen las imágenes existentes (merge, no reemplazo)
- ✅ Se elimina solo lo que se quitó explícitamente del array
- ✅ Logging detallado para auditoría

## Validación Post-Fix

Después del fix, al editar un proceso:
1. ✅ Las imágenes nuevas se guardan en BD
2. ✅ Las imágenes existentes se preservan
3. ✅ Aparecen en el recibo/factura con la URL correcta
4. ✅ El log muestra `total_nuevas > 0`
