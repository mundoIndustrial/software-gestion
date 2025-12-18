# 📸 SOLUCIÓN: Mostrar Imágenes de Logo en PDF

## 📋 Problema

El PDF de cotización de logo no mostraba las imágenes guardadas en la tabla `logo_fotos_cot`.

## ✅ Raiz del Problema

La tabla `logo_fotos_cot` almacena las imágenes de logo con las columnas:
- `logo_cotizacion_id` (relación)
- `ruta_original` (ruta de la imagen original)
- `ruta_webp` (versión optimizada)
- `ruta_miniatura` (thumbnail)
- Otros metadatos (ancho, alto, tamaño, orden)

El código anterior no estaba:
1. ❌ Cargando correctamente las fotos en la consulta Eloquent
2. ❌ Usando el accessor `url` del modelo que maneja rutas correctamente

## ✅ Solución Implementada

### 1️⃣ **Actualizar Carga de Relaciones (Línea ~15)**

**Archivo**: `app/Http/Controllers/PDFCotizacionController.php`

**Antes**:
```php
'logoCotizacion.fotos',  // Carga directa
```

**Después**:
```php
'logoCotizacion',        // Cargar primero el logo
'logoCotizacion.fotos',  // Luego las fotos del logo
```

### 2️⃣ **Usar el Accessor `url` del Modelo (Línea ~267-290)**

**Antes** (❌ Código complicado):
```php
$rutaImagen = $imagen->ruta_webp ?? $imagen->ruta_original ?? null;
// Luego verificar manual si es URL, local, con /storage/, etc.
```

**Después** (✅ Código limpio):
```php
// El accessor 'url' del modelo LogoFotoCot maneja todo
$rutaImagen = $imagen->url;

if ($rutaImagen) {
    // Si es una URL web (http/https)
    if (strpos($rutaImagen, 'http') === 0) {
        $html .= '<img src="' . htmlspecialchars($rutaImagen) . '" ...';
    } else {
        // Es una ruta local (/storage/...)
        $rutaAbsoluta = public_path($rutaImagen);
        
        if (file_exists($rutaAbsoluta)) {
            // Usar ruta absoluta para mPDF
            $html .= '<img src="' . $rutaAbsoluta . '" ...';
        } else {
            // Usar URL web como fallback
            $urlWeb = asset($rutaImagen);
            $html .= '<img src="' . htmlspecialchars($urlWeb) . '" ...';
        }
    }
}
```

## 🔄 Flujo de Funcionamiento

```
1. Usuario solicita PDF de logo
   ↓
2. PDFCotizacionController@generarPDF
   ├─ Carga cotización con relaciones:
   │  ├─ logoCotizacion
   │  └─ logoCotizacion.fotos (de logo_fotos_cot)
   ↓
3. generarLogoHTML($cotizacion)
   ├─ Itera sobre $logo->fotos
   ├─ Para cada foto, obtiene $imagen->url (accessor)
   │  ├─ Preferencia: ruta_webp > ruta_original
   │  ├─ Agrega automáticamente /storage/ si falta
   │  └─ Maneja URLs completas correctamente
   ├─ Verifica si archivo existe
   ├─ Si existe: usa ruta absoluta para mPDF
   ├─ Si no existe: usa URL web como fallback
   └─ Genera <img> tags en HTML
   ↓
4. mPDF renderiza HTML con imágenes
   ↓
5. PDF generado correctamente con imágenes
```

## 🎯 Qué hace el Accessor `url`

El modelo `LogoFotoCot` tiene un accessor que:

```php
public function getUrlAttribute(): string
{
    $ruta = $this->ruta_webp ?? $this->ruta_original;
    
    // Si es URL completa (http/https), devolverla tal cual
    if (str_starts_with($ruta, 'http')) {
        return $ruta;
    }
    
    // Si ya tiene /storage/, es accesible
    if (str_starts_with($ruta, '/storage/')) {
        return $ruta;
    }
    
    // Si comienza con 'storage/', agregar /
    if (str_starts_with($ruta, 'storage/')) {
        return '/' . $ruta;
    }
    
    // Si es relativa, agregar /storage/
    return '/storage/' . ltrim($ruta, '/');
}
```

## ✨ Beneficios

✅ **Código más limpio**: Usa el patrón de accessor del modelo
✅ **Manejo centralizado**: Todas las rutas se normalizan en un lugar
✅ **Consistencia**: Si cambia la lógica de rutas, se actualiza en un solo lugar
✅ **Robustez**: Maneja múltiples formatos de rutas
✅ **Fallback**: Si falla la ruta local, intenta URL web

## 🧪 Prueba

1. Crear/editar una cotización de logo con imágenes
2. Guardar la cotización
3. Generar PDF con `?tipo=logo`
4. ✅ Las imágenes deben aparecer en el PDF

## 📁 Archivo Modificado

| Archivo | Línea | Cambio |
|---------|-------|--------|
| `app/Http/Controllers/PDFCotizacionController.php` | 15-29 | Agregar carga de `logoCotizacion` antes de `fotos` |
| `app/Http/Controllers/PDFCotizacionController.php` | 263-290 | Usar accessor `url` en lugar de manejo manual de rutas |

---

**Estado**: ✅ COMPLETADO
**Fecha**: 18 de Diciembre de 2025
