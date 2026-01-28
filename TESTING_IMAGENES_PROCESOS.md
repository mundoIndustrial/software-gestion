# TESTING: Verificar que Imágenes de Procesos se Guardan

## Pre-Requisitos
- Tener un pedido creado
- Tener una prenda con al menos un proceso
- Estar autenticado como asesor

## Test Case 1: Agregar Imagen Nueva a Proceso Existente

### Pasos:
1. Ir a: `asesores/pedidos/{pedidoId}/edicion-dinamica`
2. Seleccionar una prenda con proceso existente
3. Click en el botón de editar proceso (ej: "Reflectivo")
4. Se abre modal
5. En la sección de "Imágenes": Click en "Agregar imagen"
6. Seleccionar archivo (JPG, PNG, etc)
7. Verificar que aparece en el preview
8. Click en "Guardar cambios"

### Verificación:

#### En Console (F12 → Console):
```javascript
// 1. Verificar que hay imágenes en memoria
console.log(window.imagenesProcesoActual);
// Esperado: [File, null, null] o similar

// 2. Verificar que se hace PATCH
// Ir a Network tab → buscar "procesos" → debe haber un PATCH
// - Method: PATCH
// - URL: /api/prendas-pedido/3472/procesos/113
// - Content-Type: multipart/form-data
// - Payload: imagenes_nuevas[0]: (archivo)
```

#### En Backend Log:
```
tail -f storage/logs/laravel.log | grep "PROCESOS-ACTUALIZAR"

# Esperado:
[PROCESOS-ACTUALIZAR] Imagen nueva de proceso procesada
[PROCESOS-ACTUALIZAR] Procesando imágenes: {"total_recibidas":1}
[PROCESOS-ACTUALIZAR] Imágenes agregadas: {"cantidad":1}
```

#### En BD (MySQL):
```sql
SELECT * FROM pedidos_procesos_imagenes 
WHERE proceso_prenda_detalle_id = 113
ORDER BY updated_at DESC
LIMIT 5;

# Esperado:
# - Nueva fila con ruta_webp
# - created_at/updated_at = ahora
```

#### En Interfaz:
```
- Cerrar y volver a abrir el modal
- Las imágenes nuevas deben estar presentes
- Deben aparecer en el preview de la factura
```

---

## Test Case 2: Reemplazar Imagen Existente

### Pasos:
1. Abrir modal de proceso que ya tiene imagen
2. Click en "X" para eliminar imagen existente
3. Agregar imagen nueva
4. Click en "Guardar cambios"

### Verificación:

#### En Backend Log:
```
[PROCESOS-ACTUALIZAR] Imágenes eliminadas: {"cantidad":1, "rutas":[...]}
[PROCESOS-ACTUALIZAR] Imágenes agregadas: {"cantidad":1, "rutas":[...]}
```

#### En BD:
```sql
SELECT * FROM pedidos_procesos_imagenes 
WHERE proceso_prenda_detalle_id = 113;

# Esperado:
# - Imagen vieja: deleted_at = NULL (o no existe si usó DELETE)
# - Imagen nueva: created_at = ahora
```

---

## Test Case 3: Agregar Múltiples Imágenes

### Pasos:
1. Abrir modal
2. Agregar 3 imágenes nuevas
3. Guardar cambios

### Verificación:

#### En Console:
```javascript
window.imagenesProcesoActual
// Esperado: [File, File, File]
```

#### En Backend Log:
```
[PROCESOS-ACTUALIZAR] Procesando imágenes: {"total_recibidas":3}
[PROCESOS-ACTUALIZAR] Imágenes agregadas: {"cantidad":3}
```

#### En BD:
```sql
SELECT COUNT(*) as cantidad FROM pedidos_procesos_imagenes 
WHERE proceso_prenda_detalle_id = 113;
# Esperado: 3 (o más si ya había)
```

---

## Test Case 4: Ver Imágenes en Recibo/Factura

### Pasos:
1. Después de guardar las imágenes del proceso
2. Ir a "Ver Factura" del pedido
3. Buscar el proceso en la factura

### Verificación:

#### En HTML:
```html
<!-- Deben aparecer las imágenes nuevas en el recibo -->
<img src="/storage/procesos/proceso_20260127212136_964920.webp" />
```

#### En JavaScript Console:
```javascript
// En la página del recibo
document.querySelectorAll('[data-proceso-id="113"] img')
// Debería mostrar todas las imágenes del proceso
```

---

## Test Case 5: Editar Proceso Nuevamente

### Pasos:
1. Abrir de nuevo el modal del proceso (que ya tiene imágenes nuevas)
2. Las imágenes nuevas deben estar visibles
3. Agregar una imagen más
4. Guardar

### Verificación:

#### En BD:
```sql
SELECT COUNT(*) FROM pedidos_procesos_imagenes 
WHERE proceso_prenda_detalle_id = 113;
# Esperado: cantidad_anterior + 1
```

---

## Checklist de Validación ✅

- [ ] Las imágenes nuevas aparecen en el modal después de guardar
- [ ] El log muestra `Imágenes agregadas > 0`
- [ ] Las imágenes se guardan en `pedidos_procesos_imagenes`
- [ ] Las imágenes aparecen en la factura
- [ ] Se pueden editar múltiples veces
- [ ] Las imágenes se convierten a WebP
- [ ] Las imágenes existentes se preservan (no se borran)
- [ ] Funciona sin errores en el console

---

## Si Algo Falla

### Error: "Error al aplicar PATCH"
```
Console: Error PATCH: [mensaje]
Log: [modal-novedad-edicion] ❌ Error al aplicar PATCH
```
**Solución:**
- Verificar que el backend está corriendo
- Verificar que hay espacio en storage
- Ver el log completo: `tail -f storage/logs/laravel.log`

### Error: Imagen no aparece en BD
```sql
SELECT * FROM pedidos_procesos_imagenes 
WHERE proceso_prenda_detalle_id = 113
AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE);
# Resultado vacío
```
**Solución:**
- Verificar log: `[PROCESOS-ACTUALIZAR] Imagen nueva de proceso procesada`
- Verificar que `imagenes_nuevas[*]` llega en el PATCH (Network tab)
- Verificar permisos de carpeta storage

### Error: Imagen aparece en BD pero no en factura
```
Log muestra imagen procesada correctamente
BD tiene el registro
Pero factura no muestra imagen
```
**Solución:**
- Recargar la página completa (Ctrl+F5)
- Limpiar cache: `php artisan cache:clear`
- Verificar que la ruta existe en storage

---

## Comandos de Debugging

```bash
# 1. Ver últimos logs
tail -f storage/logs/laravel.log | grep PROCESOS

# 2. Limpiar cache
php artisan cache:clear

# 3. Verificar archivos creados
ls -la storage/app/public/procesos/

# 4. Contar imágenes en BD
mysql -u root -p << EOF
USE mundoindustrial;
SELECT COUNT(*) as total FROM pedidos_procesos_imagenes;
SELECT proceso_prenda_detalle_id, COUNT(*) as cantidad FROM pedidos_procesos_imagenes GROUP BY proceso_prenda_detalle_id;
EOF

# 5. Ver últimas 10 imágenes insertadas
mysql -u root -p << EOF
USE mundoindustrial;
SELECT id, proceso_prenda_detalle_id, ruta_webp, created_at FROM pedidos_procesos_imagenes ORDER BY created_at DESC LIMIT 10;
EOF
```

---

## Documentos Relacionados

- [FIX_IMAGENES_PROCESOS_NO_GUARDABAN_MODAL.md](./FIX_IMAGENES_PROCESOS_NO_GUARDABAN_MODAL.md) - Explicación técnica del fix
- [RESUMEN_FIX_IMAGENES_PROCESOS.md](./RESUMEN_FIX_IMAGENES_PROCESOS.md) - Resumen de cambios
