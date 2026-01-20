# Estructura Final de Almacenamiento de Imágenes

## 📁 Estructura de Carpetas

```
storage/
  prendas/
    {numero_pedido}/
      prenda/
        foto_prenda_0_...webp
        foto_prenda_1_...webp
      tela/
        foto_tela_0_...webp
        foto_tela_1_...webp
      proceso/
        reflectivo/
          imagen_0_...webp
          imagen_1_...webp
        bordado/
          imagen_0_...webp
        estampado/
          imagen_0_...webp
        ... (otros tipos de proceso)
```

## 🗄️ Tablas de Base de Datos

### `pedidos_procesos_imagenes`
```sql
- id (PK)
- proceso_prenda_detalle_id (FK)
- ruta_original (varchar) -- Null (para referencia futura)
- ruta_webp (varchar) -- storage/prendas/{pedido_id}/proceso/{tipo}/{archivo}.webp
- orden (int)
- es_principal (boolean)
- created_at
- updated_at
- deleted_at
```

### `prenda_fotos_pedido`
```sql
- id (PK)
- prenda_pedido_id (FK)
- ruta_original (varchar) -- storage/prendas/{pedido_id}/prenda/{archivo}.webp
- ruta_webp (varchar) -- storage/prendas/{pedido_id}/prenda/{archivo}.webp
- orden (int)
- created_at
- updated_at
- deleted_at
```

### `prenda_fotos_tela_pedido`
```sql
- id (PK)
- prenda_pedido_id (FK)
- tela_id (FK)
- color_id (FK)
- ruta_original (varchar) -- storage/prendas/{pedido_id}/tela/{archivo}.webp
- ruta_webp (varchar) -- storage/prendas/{pedido_id}/tela/{archivo}.webp
- orden (int)
- observaciones (text)
- created_at
- updated_at
- deleted_at
```

## 📝 Métodos Actualizado

### `guardarFotosPrenda()`
- Guarda en: `storage/prendas/{numero_pedido}/prenda/`
- Solo guarda: `ruta_original` y `ruta_webp` (ambas son iguales)
- Formato: WebP

### `guardarFotosTelas()`
- Guarda en: `storage/prendas/{numero_pedido}/tela/`
- Solo guarda: `ruta_original` y `ruta_webp` (ambas son iguales)
- Formato: WebP
- Incluye: observaciones de tela

### `guardarProcesosImagenes()`
- Guarda en: `storage/prendas/{numero_pedido}/proceso/{tipo_proceso}/`
- Tipos de proceso: reflectivo, bordado, estampado, etc.
- Solo guarda: `ruta_webp` (relativa a storage)
- Formato: WebP

## 🔧 Acceso a las Imágenes

Para acceder a las imágenes desde vistas:

```php
// Fotos de prenda
$ruta = storage_path($fotoPrenda->ruta_webp); // Ruta completa del archivo
$url = asset('storage/' . $fotoPrenda->ruta_webp); // URL pública

// Fotos de tela
$ruta = storage_path($fotoTela->ruta_webp); // Ruta completa del archivo
$url = asset('storage/' . $fotoTela->ruta_webp); // URL pública

// Imágenes de proceso
$ruta = storage_path($imagenProceso->ruta_webp); // Ruta completa del archivo
$url = asset('storage/' . $imagenProceso->ruta_webp); // URL pública
```

##  Ventajas de Esta Estructura

1. **Organización clara**: Todas las imágenes de un pedido en una carpeta
2. **Separación por tipo**: prenda, tela, proceso (cada proceso con su subcarpeta)
3. **Almacenamiento seguro**: En storage, no en public
4. **URLs accesibles**: Via `storage_path()` y `asset('storage/...')`
5. **Simplificación**: Solo 2 campos en BD (ruta_original y ruta_webp)
6. **WebP moderno**: Todas las imágenes convertidas automáticamente
7. **Nombres únicos**: Con timestamp y random para evitar conflictos
8. **Escalable**: Estructura soporta crecimiento sin límite

## 🔄 Migración de BD

Se ejecutaron 4 migraciones:

1. `2026_01_16_000002_simplify_procesos_imagenes_table.php` 
   - Eliminó campos innecesarios de `pedidos_procesos_imagenes`

2. `2026_01_16_000003_simplify_prenda_fotos_pedido_table.php` 
   - Eliminó campos innecesarios de `prenda_fotos_pedido`

3. `2026_01_16_000004_simplify_prenda_fotos_tela_pedido_table.php` 
   - Eliminó campos innecesarios de `prenda_fotos_tela_pedido`

## 📦 Archivos Modificados

1. `app/Application/Services/PedidoPrendaService.php`
   - Actualizado: `guardarFotosPrenda()`
   - Actualizado: `guardarFotosTelas()`
   - Actualizado: `guardarProcesosImagenes()`
   - Actualizado: `guardarImagenDesdeArchivo()`
   - Actualizado: `guardarImagenBase64()`
   - Nuevo: `guardarFotoEnWeb()`

2. `public/js/modulos/crear-pedido/procesos/gestor-modal-proceso-generico.js`
   - Cambio: Guarda File objects en lugar de base64
   - Cambio: Usa URL.createObjectURL() para previsualizaciones
   - Cambio: Limpia URLs con revokeObjectURL()
