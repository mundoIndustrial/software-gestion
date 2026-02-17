# ✅ Fix: Eliminación de Imágenes en Procesos

## Problema Identificado en Logs
```
⚠️ Imagen sin ID ni ruta_original, no se pudo guardar: /storage/pedidos/24/proceso/procesos_20260215221814_0PfGAO5U.webp
```

**Causa Raíz**: Las imágenes se estaban devolviendo como **strings** (solo URLs) desde el backend, no como objetos con `id` y `ruta_original`.

---

## Cambios Realizados

### 1️⃣ Backend: `PedidosProduccionViewController.php` (Líneas 489-507)

**ANTES:**
```php
$imagenesFormato = $imagenesProc->map(function($img) {
    $ruta = str_replace('\\', '/', $img->ruta_webp ?? $img->ruta_original);
    // ... normalización
    return $ruta;  // ❌ SOLO RETORNA STRING
})->toArray();
```

**DESPUÉS:**
```php
$imagenesFormato = $imagenesProc->map(function($img) {
    // ✅ Retorna OBJETO completo
    return [
        'id' => $img->id,
        'ruta_webp' => $ruta_webp,
        'ruta_original' => $ruta_original,
        'url' => $ruta_webp ?: $ruta_original,
        'es_principal' => $img->es_principal ?? false
    ];
})->toArray();
```

### 2️⃣ Frontend: `prenda-editor-procesos.js` (Línea 47-60)

Agregué logging para verificar que las imágenes se reciben correctamente como objetos.

---

## Flujo Completo (Ahora Funcional)

```
1. Backend devuelve:
   {id: 123, ruta_webp: '/storage/...', ruta_original: '/storage/...'}
   
2. Frontend loader almacena en window.procesosSeleccionados
   
3. Modal carga imágenes en window.imagenesProcesoExistentes
   con TODOS los campos (id + ruta_original)
   
4. Usuario hace click en eliminar:
   - Se GUARDA imagen completa en window.imagenesEliminadasProcesoStorage
   - Se marca como null en window.imagenesProcesoExistentes
   
5. Adapter construye imagenes_a_eliminar:
   [{id: 123, ruta_original: '/storage/...', ruta_webp: '...'}]
   
6. Backend recibe y ELIMINA (soft delete con deleted_at)
```

---

## ✅ Pasos para Probar

### Paso 1: Hard Refresh
```
Ctrl + Shift + R (borrar cache)
```

### Paso 2: Abrir Consola (F12)
Buscar estos logs para verificar que las imágenes vienen como objetos:

```javascript
[PROCESOS-LOADER] 🖼️ Imágenes recibidas para [tipo]:
// Debe mostrar primeraprimera como OBJETO con {id, ruta_webp, ruta_original}
// NO como string
```

### Paso 3: Editar Proceso con Imagen
- Ir a crear pedido → editar prenda → abrir proceso con imagen

### Paso 4: Ver Logs de Carga
Debería ver:
```
[cargarDatosProcesoEnModal] 🔍 GUARDANDO OBJETO en imagenesProcesoExistentes[0]:
    tipoImg: 'object' ✅
    esString: false ✅
    tieneId: true ✅
    tieneRutaOriginal: true ✅
```

### Paso 5: Eliminar Imagen
- Click en botón eliminar
- Click en "Confirmar eliminación"

Debería ver:
```
[confirmarEliminarImagenProceso] ✅ Confirmando eliminación de imagen: 1
[confirmarEliminarImagenProceso] storageLlenado: Array(1)  ← DEBE TENER 1 OBJETO
[confirmarEliminarImagenProceso] Objeto almacenado: {id: 123, ruta_original: '...'}
```

### Paso 6: Guardar Cambios
- Click "Guardar cambios"
- Click "Guardar prenda"

Debería ver en adapter:
```
[PedidosAdapter] ✅ Objeto AGREGADO a imagenesAEliminar:
    {id: 123, ruta_original: '/storage/...', ruta_webp: '...'}
```

### Paso 7: Verificar Base de Datos
En `pedidos_procesos_imagenes`, la imagen debe tener `deleted_at` con timestamp (soft delete)

```sql
SELECT * FROM pedidos_procesos_imagenes 
WHERE proceso_prenda_detalle_id = 123
AND deleted_at IS NOT NULL;
```

---

## 🔍 Expected Results

✅ Logs show images are OBJECTS (not strings)
✅ Storage array has 1 complete object when deleting
✅ Adapter builds imagenesAEliminar correctly
✅ Database row gets `deleted_at` timestamp
✅ Image disappears from modal after saving

---

## ❌ Troubleshooting

**Si aún ves "Imagen sin ID ni ruta_original":**
1. Verificar que el navegador no tenga cache (Ctrl+Shift+R)
2. Abrir DevTools → Network → buscar `/actualizar-prenda` request
3. Ver en response si las imágenes vienen como objetos o strings
4. Si vienen como strings, check que controller fue actualizado correctamente

---

## 📊 Logs Clave a Revisar

1. **Network (F12 → Network)**
   - Buscar: `POST /asesores/pedidos/24/actualizar-prenda`
   - Response debe mostrar procesos con imagenes como array de objetos

2. **Console (F12 → Console)**
   - `[PROCESOS-LOADER]` - Verificar si imagenes son objetos
   - `[cargarDatosProcesoEnModal]` - Verificar id y ruta_original
   - `[confirmarEliminarImagenProceso]` - Verificar storage
   - `[PedidosAdapter]` - Verificar imagenesAEliminar

3. **Laravel Log** (`storage/logs/laravel.log`)
   - `[ActualizarPrendaCompletaUseCase]` - Verificar "imagenes_a_eliminar"
   - `sincronizarImagenesProceso` - Verificar que entra y elimina

---

## ✨ Cambios Resumidos

| Archivo | Línea | Cambio | Impacto |
|---------|-------|--------|---------|
| `PedidosProduccionViewController.php` | 489-507 | Backend devuelve objetos completos | 🟢 CRÍTICO - Fix raíz |
| `prenda-editor-procesos.js` | 47-60 | Agregué logging y normalización para objetos | 🟡 Debug + normalizacion |

---

