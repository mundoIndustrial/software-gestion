# FIX: Rutas de Imágenes (Procesos, Prendas y Telas) - 16 ENE 2026

## 🎯 Problema Identificado

Las rutas de imágenes se estaban guardando con la URL completa del servidor:
```
http://servermi:8000/storage/pedidos/2635/procesos/reflectivo/img_proceso_0_20260116161610_a24473.webp
```

Esto causa problemas de portabilidad cuando se cambia de servidor (dominio diferente).

## ✅ Solución Implementada

### 1. **Actualizar PedidoPrendaService** 
Archivos: `app/Application/Services/PedidoPrendaService.php`

#### Métodos actualizados:
- `guardarProcesosImagenes()` - Líneas ~1361-1430
  - Cambio: Guardar `$rutaRelativa` en lugar de `$rutaWeb` 
  - Formato guardado: `storage/pedidos/{id}/procesos/{tipo}/{archivo}.webp`

- `guardarFotosPrenda()` - Líneas ~525-700
  - Cambio: Guardar `$rutaRelativa` en lugar de `$rutaWeb`
  - Formato guardado: `storage/pedidos/{id}/prendas/{archivo}.webp`

- `guardarFotosTelas()` - Líneas ~750-850
  - Cambio: Guardar `$rutaRelativa` en lugar de `$rutaWeb`
  - Formato guardado: `storage/pedidos/{id}/telas/{archivo}.webp`

**Detalles técnicos:**
```php
// Antes (❌ INCORRECTO):
$rutaWeb = asset("storage/{$rutaRelativa}");  // Genera: http://servermi:8000/storage/...
DB::table('pedidos_procesos_imagenes')->insert([
    'ruta_webp' => $rutaWeb,  // Guardaba URL completa
]);

// Después (✅ CORRECTO):
$rutaRelativa = "storage/{$rutaRelativa}";  // Solo ruta relativa
DB::table('pedidos_procesos_imagenes')->insert([
    'ruta_webp' => $rutaRelativa,  // Guarda: storage/pedidos/2635/procesos/reflectivo/img_proceso_0_20260116161610_a24473.webp
]);
```

### 2. **Actualizar Modelos**
Archivos actualizados:

#### `app/Models/ProcesoPrendaImagen.php`
- Agregado: `protected $appends = ['url'];`
- Nuevo accessor `getUrlAttribute()` que:
  - ✅ Si la ruta ya es URL completa, la devuelve tal cual
  - ✅ Si es ruta relativa `storage/...`, prepend `/` → `/storage/...`
  - ✅ Construye la URL correcta automáticamente

#### `app/Models/PedidosProcessImagenes.php`
- Agregado: `protected $appends = ['url'];`
- Nuevo accessor `getUrlAttribute()` con la misma lógica

**Uso en vistas:**
```blade
{{-- Antes (❌ Problemático) --}}
<img src="{{ $imagen->ruta_webp }}" alt="imagen">

{{-- Después (✅ Correcto) --}}
<img src="{{ $imagen->url }}" alt="imagen">
{{-- Automáticamente devuelve: /storage/pedidos/2635/procesos/reflectivo/img_proceso_0_20260116161610_a24473.webp --}}
```

### 3. **Script SQL para Limpiar URLs Existentes**
Archivo: `database/scripts/01_limpiar_urls_procesos_imagenes.sql`

Convierte URLs completas a rutas relativas:
```sql
-- Transforma esto:
UPDATE pedidos_procesos_imagenes
SET ruta_webp = SUBSTRING(ruta_webp, POSITION('/storage/' IN ruta_webp))
WHERE ruta_webp LIKE 'http%' AND ruta_webp LIKE '%/storage/%';
```

**Ejecución:**
```bash
mysql -u usuario -p nombre_bd < database/scripts/01_limpiar_urls_procesos_imagenes.sql
```

## 📋 Tablas Afectadas

| Tabla | Columna | Cambio |
|-------|---------|--------|
| `pedidos_procesos_imagenes` | `ruta_webp` | URL completa → Ruta relativa |
| `prenda_fotos_pedido` | `ruta_webp` | URL completa → Ruta relativa |
| `prenda_fotos_tela_pedido` | `ruta_webp` | URL completa → Ruta relativa |

## 🚀 Beneficios

✅ **Portabilidad**: Funciona con cualquier dominio/servidor  
✅ **Mantenibilidad**: Solo guarda rutas, no URLs completas  
✅ **Consistencia**: Todos los tipos de imágenes usan el mismo formato  
✅ **Performance**: Menor tamaño de datos en BD  

## 🔍 Cómo Verificar

```php
// Test - Verificar que retorna ruta relativa
$imagen = ProcesoPrendaImagen::first();
echo $imagen->ruta_webp;  // Output: storage/pedidos/2635/procesos/reflectivo/img_proceso_0_20260116161610_a24473.webp

// Test - Verificar que accessor construye URL correcta
echo $imagen->url;  // Output: /storage/pedidos/2635/procesos/reflectivo/img_proceso_0_20260116161610_a24473.webp

// En Blade
<img src="{{ $imagen->url }}" alt="test">  // Funciona correctamente
```

## 📅 Próximos Pasos

1. **Ejecutar script SQL** para limpiar URLs existentes
2. **Hacer prueba completa** de guardado y visualización de imágenes
3. **Verificar en otro servidor** que las imágenes se muestren correctamente
4. **Documentar en guía de deployment** si se cambia de servidor

---

**Cambios realizados por:** AI Assistant  
**Fecha:** 16 ENE 2026  
**Versión:** 1.0
