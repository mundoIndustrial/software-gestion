# FIX: EPP Images Not Being Saved - January 26, 2026

## Problem
EPP (Equipos de Protección Personal) images were not being saved to the database when creating a new pedido (order). The EPP data itself was being created, but images were being ignored.

### Root Cause
In the `CrearPedidoEditableController::crearPedido()` method (lines 459-620), the following sequence occurred:

1. ✅ Pedido (order) base is created
2. ✅ Prendas (garments) are created with images via `mapeoImagenes->mapearYCrearFotos()`
3. ❌ **EPP images processing is MISSING** - the `procesarYAsignarEpps()` method exists but is never called
4. ✅ Quantities are calculated and committed

### Evidence from Logs
From the log file, we can see:
```
[gestion-items-pedido.js] 🛡️ EPPs: 1
[gestion-items-pedido.js] EPP 0: {uid: 'uid-ip7ekg1g7-mkvfpeo9', epp_id: 849, ...imagenes_count: 1}
[item-api-service.js] FormData construido COMPLETO: {archivos_totales: 3, ...}
```

The EPP and its image are in the FormData, but the backend never processes them.

## Solution

### Changes Made

#### 1. **CrearPedidoEditableController.php** (Line ~555)
Added EPP image processing after prenda image processing:

```php
// ====== PASO 7B: CRÍTICO - Procesar imágenes de EPPs ======
if (!empty($dtoPedido->epps)) {
    $this->procesarYAsignarEpps($request, $pedidoId, $dtoPedido->epps);
    
    Log::info('[CrearPedidoEditableController] Imágenes de EPPs procesadas', [
        'pedido_id' => $pedidoId,
        'epps_count' => count($dtoPedido->epps)
    ]);
}
```

**What it does:**
- After mapping prenda images, process EPP images using the existing `procesarYAsignarEpps()` method
- Method iterates through each EPP and its images
- Saves images to `storage/pedidos/{pedido_id}/epps/` directory
- Creates `PedidoEppImagen` records linking images to the `pedido_epp` record

#### 2. **PedidoNormalizadorDTO.php** (Lines 192-204)
Updated EPP normalization to preserve critical fields:

```php
private static function normalizarEpps(array $epps): array
{
    return array_map(function ($epp) {
        return [
            'uid' => $epp['uid'] ?? null,
            'epp_id' => intval($epp['epp_id'] ?? 0),  // ← ADDED: preserve epp_id
            'nombre' => trim($epp['nombre'] ?? $epp['nombre_epp'] ?? ''),  // ← IMPROVED: fallback to nombre_epp
            'cantidad' => intval($epp['cantidad'] ?? 1),
            'observaciones' => trim($epp['observaciones'] ?? ''),  // ← ADDED: preserve observations
            'descripcion' => trim($epp['descripcion'] ?? ''),
            'imagenes' => self::normalizarImagenes($epp['imagenes'] ?? [])
        ];
    }, $epps);
}
```

**Why:**
- Frontend sends `epp_id` (the catalog EPP ID) but it was being dropped
- Need `epp_id` to reference the catalog EPP in `pedido_epp` table
- `nombre` field now has fallback to `nombre_epp` for compatibility
- `observaciones` field is now preserved for EPP notes

## Database Tables Affected

### pedido_epp
Existing EPP record is now complete:
```
pedido_produccion_id: 123
epp_id: 849
cantidad: 4
observaciones: "Usuario observation"
```

### pedido_epp_imagenes (NUEVOS REGISTROS)
Las imágenes de los EPPs ahora se guardan aquí:
```
id: 1
pedido_epp_id: 5
ruta_original: "pedidos/123/epp/epp_849_img_0.webp"
ruta_web: "pedidos/123/epp/epp_849_img_0.webp"
principal: 1
orden: 1
created_at: 2026-01-26 12:30:45
updated_at: 2026-01-26 12:30:45
```

**Notas sobre las rutas:**
- `ruta_original`: Ruta del archivo guardado en storage (formato WebP)
- `ruta_web`: Ruta accesible desde el navegador (también WebP, misma que original)
- Ambas apuntan a `storage/app/public/pedidos/{id}/epp/{nombre}`
- El servicio `ImageUploadService` convierte automáticamente a WebP con optimización (calidad 75%)

## Flujo de Procesamiento (Post-Corrección)

```
FormData del Frontend
    ↓
CrearPedidoEditableController::crearPedido()
    ├─ Extraer JSON del campo "pedido"
    ├─ Normalizar usando PedidoNormalizadorDTO (incluye epps con epp_id)
    ├─ Crear registro base en pedido_produccion
    ├─ Procesar imágenes de prendas vía MapeoImagenesService
    └─ 🆕 Procesar imágenes de EPPs vía procesarYAsignarEpps()
        ├─ Iterar cada EPP
        ├─ Verificar que el EPP existe en catálogo (tabla epps)
        ├─ Crear/actualizar registro en pedido_epp
        │  └─ Guarda: epp_id, cantidad, observaciones
        └─ Para cada imagen del EPP:
           ├─ Recibir archivo del FormData
           ├─ Convertir a WebP (calidad 75%)
           ├─ Guardar en: storage/app/public/pedidos/{id}/epp/
           └─ Crear registro en pedido_epp_imagenes
              └─ Guarda: ruta_original, ruta_web, principal, orden
```

## Pruebas (Checklist)

- [ ] Crear un nuevo pedido con 1 o más EPPs que tengan imágenes
- [ ] Verificar en BD que el registro `pedido_epp` se creó con el `epp_id` correcto
- [ ] Verificar en BD que los registros `pedido_epp_imagenes` existen con `ruta_original` y `ruta_web` correctos
- [ ] Verificar que los archivos existan en disco en `storage/app/public/pedidos/{id}/epp/`
- [ ] Verificar que las imágenes están en formato WebP
- [ ] Verificar en logs de Laravel los mensajes de éxito (buscar "Imágenes de EPPs procesadas")
- [ ] Acceder a la ruta `ruta_web` desde el navegador y confirmar que la imagen se descarga
- [ ] Verificar que `principal` = 1 para la primera imagen y 0 para las demás

## Salida esperada en Logs

Cuando se cree un pedido con EPPs e imágenes, deberías ver en `storage/logs/laravel.log`:

```
[2026-01-26 12:30:45] local.INFO: [CrearPedidoEditableController] 📦 Procesando EPPs {"pedido_id": 123, "epps_count": 1}
[2026-01-26 12:30:45] local.INFO: [CrearPedidoEditableController] EPP creado {"pedido_epp_id": 5, "epp_id": 849, "cantidad": 4}
[2026-01-26 12:30:45] local.INFO: [ImageUploadService] Imagen guardada directamente {"pedido_id": 123, "tipo": "epps", "ruta_webp": "pedidos/123/epp/epp_849_img_0.webp"}
[2026-01-26 12:30:45] local.DEBUG: [CrearPedidoEditableController] 📸 Imagen EPP guardada (WebP) {"pedido_epp_id": 5, "webp": "pedidos/123/epp/epp_849_img_0.webp", "orden": 1}
[2026-01-26 12:30:46] local.INFO: [CrearPedidoEditableController] Imágenes EPP procesadas {"pedido_id": 123, "epps_count": 1}
[2026-01-26 12:30:46] local.INFO: [CrearPedidoEditableController] Imágenes de EPPs procesadas {"pedido_id": 123, "epps_count": 1}
[2026-01-26 12:30:46] local.INFO: [CrearPedidoEditableController] TRANSACCIÓN EXITOSA {"pedido_id": 123, "numero_pedido": "456", "cantidad_total_prendas": 60, "cantidad_total_epps": 4, "cantidad_total": 64}
```

## Archivos Modificados

1. `app/Infrastructure/Http/Controllers/Asesores/CrearPedidoEditableController.php`
   - Agregado: Llamada a `procesarYAsignarEpps()` después de procesar imágenes de prendas
   - Línea: ~555 (después del método `mapearYCrearFotos()`)

2. `app/Domain/Pedidos/DTOs/PedidoNormalizadorDTO.php`
   - Actualizado: Método `normalizarEpps()` para preservar `epp_id`, `observaciones`
   - Línea: 192-204

## Estado
✅ **LISTO PARA PROBAR**

La solución es **mínima y enfocada**:
- Usa código existente (`procesarYAsignarEpps()` ya estaba implementado pero nunca se llamaba)
- Sigue el mismo patrón que las imágenes de prendas
- Guarda correctamente en ambas columnas: `ruta_original` y `ruta_web`
- No afecta ningún otro módulo del sistema
