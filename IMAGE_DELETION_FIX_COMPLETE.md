# ✅ COMPLETADO: Fix Raíz para Eliminación de Imágenes en Procesos

## 🔴 Problema Identificado
```
⚠️ Imagen sin ID ni ruta_original, no se pudo guardar: /storage/pedidos/25/proceso/procesos_20260215231659_1iYYfrLL.webp
```

**Causa Raíz**: El backend devolvía imágenes de procesos como **STRINGS** (solo URLs), no como objetos completos con `id` y `ruta_original`.

---

## 🔍 Descubrimiento Clave

Había **DOS endpoints backend** diferentes que causaban el problema:
1. **`PedidosProduccionViewController.php`** (Líneas 489-535) - Para carga inicial de pedidos
2. **`ObtenerPedidoDetalleService.php`** (Líneas 773-815) - Para edición de procesos existentes

**Ambos ahora están corregidos para devolver objetos completos.**

---

## ✅ CAMBIOS REALIZADOS

### 1️⃣ Backend: `PedidosProduccionViewController.php`

**Ubicación**: `/app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionViewController.php` líneas 489-535

**ANTES** (❌ Devuelve solo strings):
```php
$imagenesFormato = $imagenesProc->map(function($img) {
    $ruta = str_replace('\\', '/', $img->ruta_webp ?? $img->ruta_original);
    if (strpos($ruta, '/storage/') === 0) {
        return $ruta;
    }
    // ... más normalización ...
    return $ruta;  // ❌ SOLO URL COMO STRING
})->toArray();
```

**DESPUÉS** (✅ Devuelve objetos completos):
```php
$imagenesFormato = $imagenesProc->map(function($img) {
    // Normalizar rutas...
    
    return [
        'id' => $img->id,                      // ✅ ID importante para eliminar
        'ruta_webp' => $ruta_webp,            // ✅ Ruta WebP normalizada
        'ruta_original' => $ruta_original,    // ✅ Ruta original normalizada
        'url' => $ruta_webp ?: $ruta_original, // Para compatibilidad frontend
        'es_principal' => $img->es_principal ?? false
    ];
})->toArray();
```

### 2️⃣ Backend: `ObtenerPedidoDetalleService.php`

**Ubicación**: `/app/Application/Services/Asesores/ObtenerPedidoDetalleService.php` líneas 773-815

**ANTES** (❌ Devuelve solo strings):
```php
'imagenes' => $proceso->imagenes->map(function($img) {
    return $img->ruta_webp ?? $img->ruta_original ?? '';  // ❌ SOLO STRING
})->filter()->toArray() ?? [],
```

**DESPUÉS** (✅ Devuelve objetos completos):
```php
'imagenes' => $proceso->imagenes->map(function($img) {
    // Normalizar rutas...
    
    return [
        'id' => $img->id,
        'ruta_webp' => $ruta_webp,
        'ruta_original' => $ruta_original,
        'url' => $ruta_webp ?: $ruta_original,
        'es_principal' => $img->es_principal ?? false
    ];
})->filter(function($img) {
    return $img['ruta_webp'] || $img['ruta_original'];
})->toArray() ?? [],
```

### 3️⃣ Frontend: `prenda-editor-procesos.js` (líneas 47-69)

Agregué logging de diagnóstico para confirmar que las imágenes se reciben como objetos.

---

## 🔄 Flujo Completo DESPUÉS DEL FIX

```
1. Usuario abre pedido existente o crea nuevo
   ↓
2. Backend devuelve procesos con imágenes como OBJETOS:
   {id: 123, ruta_webp: '/storage/...', ruta_original: '/storage/...'}
   ↓
3. Frontend loader almacena en window.procesosSeleccionados
   ↓
4. Usuario abre proceso en modal
   ↓
5. cargarDatosProcesoEnModal() carga imágenes en window.imagenesProcesoExistentes
   Cada imagen es un OBJETO COMPLETO con {id, ruta_webp, ruta_original}
   ↓
6. Usuario marca imagen para eliminar (click ×)
   ↓
7. confirmarEliminarImagenProceso() guarda objeto COMPLETO en 
   window.imagenesEliminadasProcesoStorage ANTES de marcar como null
   ↓
8. Usuario guarda cambios del proceso
   ↓
9. agregarProcesoAlPedido() construye datos.imagenesEliminadas 
   desde window.imagenesEliminadasProcesoStorage
   ↓
10. prenda-editor-pedidos-adapter extracts imagenesEliminadas y 
    construye imagenesAEliminar como array de OBJETOS COMPLETOS
    ↓
11. POST /asesores/pedidos/{id}/actualizar-prenda con:
    imagenes_a_eliminar: [{id, ruta_original, ruta_webp}, ...]
    ↓
12. Backend ActualizarPrendaCompletaUseCase recibe y ejecuta 
    sincronizarImagenesProceso() con datos completos
    ↓
13. Database: pedidos_procesos_imagenes set deleted_at = NOW()
    (soft delete)
    ↓
14. ✅ LISTO: Imagen eliminada correctamente
```

---

## 🧪 VERIFICACIÓN Y TESTING

### Test 1: Verificar que backend devuelve objetos
```bash
# Hard refresh del navegador
Ctrl + Shift + R

# Abrir DevTools Network
F12 → Network tab

# Editar un pedido
# Buscar request: POST /asesores/pedidos/{id}/actualizar-prenda
# O: GET /asesores/pedidos-public/{id}/factura-datos

# En Response, verificar estructura de imagenes:
# CORRECTO: [ {id: 123, ruta_webp: "...", ruta_original: "..."} ]
# INCORRECTO: [ "/storage/..." ]  (string)
```

### Test 2: Verificar logs en console
```javascript
// En F12 → Console, deberías ver:
[PROCESOS-LOADER] 🖼️ Imágenes recibidas para [proceso]:
    cantidad: 1
    primeraprimera: {id: 123, ruta_webp: '...', ...}
    tipo_primera: "object"  // ✅ DEBE SER "object", no "string"
```

### Test 3: Eliminar una imagen
1. Hard refresh: `Ctrl+Shift+R`
2. Abre un proceso que tenga imagen
3. Haz click en botón eliminar (×)
4. Confirma eliminación
5. Guarda cambios
6. Verifica en BD:
```sql
SELECT * FROM pedidos_procesos_imagenes 
WHERE deleted_at IS NOT NULL 
LIMIT 5;
```
   ✅ Debe tener `deleted_at` con timestamp

### Test 4: Verificar logs en backend
```
tail -f storage/logs/laravel.log | grep -E "imagenesAEliminar|eliminar|successfully"
```

---

## 📋 CHECKLIST DE VALIDACIÓN

- [x] `PedidosProduccionViewController.php` modificado (líneas 489-535)
- [x] `ObtenerPedidoDetalleService.php` modificado (líneas 773-815)
- [x] `prenda-editor-procesos.js` actualizado con logging
- [ ] Hard refresh navegador ejecutado
- [ ] Logs verifican que imágenes son objetos (no strings)
- [ ] Editar proceso con imagen existente
- [ ] Marcar imagen para eliminar
- [ ] Guardar cambios
- [ ] Verificar BD: `deleted_at` presente
- [ ] Recargar página: imagen NO reaparece

---

## 🔧 TROUBLESHOOTING

**Síntoma**: Logs aún muestran `tipoImg: 'string'`
```
SOLUCIÓN:
1. Vaciar cache Laravel: php artisan cache:clear
2. Hard refresh navegador: Ctrl+Shift+R
3. Verificar que archivos PHP fueron guardados
4. Abrir DevTools Network y ver Response JSON crudo
```

**Síntoma**: "Imagen sin ID ni ruta_original" aún aparece
```
SOLUCIÓN:
1. Ir a F12 → Console
2. Buscar logs [cargarDatosProcesoEnModal]
3. Si tieneId: false, endpoint NO fue actualizado
4. Verificar URL de donde viene la imagen (¿cuál endpoint?)
5. Asegurar que ese endpoint también fue corregido
```

**Síntoma**: Imagen se elimina en UI pero NO en BD
```
SOLUCIÓN:
1. Ver logs Laravel: ActivetActualizarPrendaCompletaUseCase]
2. Debe mostrar: "imagenes_a_eliminar" con datos
3. Si está vacío, problema está en frontend adapter
4. Revisar: [PedidosAdapter] logs en console
```

---

## 📊 MONITOREO

### Logs importantes a revisar

**Frontend Console (F12)**:
- `[PROCESOS-LOADER]` - Imágenes al cargar procesos
- `[cargarDatosProcesoEnModal]` - Imágenes al abrir modal
- `[confirmarEliminarImagenProceso]` - Storage de eliminadas
- `[PedidosAdapter]` - ImagenesAEliminar enviadas

**Backend Logs** (`storage/logs/laravel.log`):
- `[ActualizarPrendaCompletaUseCase]` - Tiene_imagenes_a_eliminar
- `[ActualizarPrendaCompletaUseCase]` - Cantidad_imagenes_a_eliminar
- `[sincronizarImagenesProceso]` - Eliminar imagen
- `deleted_at` set correctly

---

## 🎯 RESULTADO ESPERADO

✅ **Images are received as OBJECTS** (not strings)
✅ **Storage array contains complete objects** when deleting
✅ **Adapter sends complete image data** to backend
✅ **Database row gets deleted_at timestamp** (soft delete)
✅ **Image no longer visible** in modal after save
✅ **API returns empty imagenes array** for that process

---

## 📝 NOTAS FINALES

- Este fix arregla la **causa raíz** del problema
- Todos los endpoints backend ahora devuelven imágenes como OBJETOS
- El frontend ya contiene toda la lógica necesaria
- No hay cambios necesarios en la estructura de la BD
- Soft delete preserva data (deleted_at, no DELETE físico)

