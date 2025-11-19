# 📸 Guía de Gestión de Imágenes para Cotizaciones

## Estructura de Carpetas

```
storage/app/public/cotizaciones/
├── {cotizacion_id}/
│   ├── bordado/
│   │   ├── 1_bordado_20251119_001.jpg
│   │   └── 1_bordado_20251119_002.jpg
│   ├── estampado/
│   │   └── 1_estampado_20251119_001.jpg
│   ├── tela/
│   │   └── 1_tela_20251119_001.jpg
│   ├── prenda/
│   │   ├── 1_prenda_20251119_001.jpg
│   │   └── 1_prenda_20251119_002.jpg
│   └── general/
│       └── 1_general_20251119_001.jpg
```

## Tipos de Imágenes

| Tipo | Descripción | Uso |
|------|-------------|-----|
| **bordado** | Diseños de bordado | Mostrar diseños de bordado |
| **estampado** | Diseños de estampado | Mostrar diseños de estampado |
| **tela** | Muestras de telas | Mostrar muestras de tela |
| **prenda** | Fotos de prendas | Mostrar prendas finales |
| **general** | Otras imágenes | Imágenes diversas |

## Uso del Servicio

### 1. Guardar una Imagen

```php
use App\Services\ImagenCotizacionService;

$imagenService = new ImagenCotizacionService();

// Guardar una imagen
$ruta = $imagenService->guardarImagen(
    cotizacionId: 1,
    archivo: $request->file('imagen'),
    tipo: 'bordado'
);

// $ruta = '/storage/cotizaciones/1/bordado/1_bordado_20251119_001.jpg'
```

### 2. Guardar Múltiples Imágenes

```php
$rutas = $imagenService->guardarMultiples(
    cotizacionId: 1,
    archivos: $request->file('imagenes'), // Array de archivos
    tipo: 'estampado'
);

// $rutas = [
//     '/storage/cotizaciones/1/estampado/1_estampado_20251119_001.jpg',
//     '/storage/cotizaciones/1/estampado/1_estampado_20251119_002.jpg'
// ]
```

### 3. Obtener Todas las Imágenes

```php
$imagenes = $imagenService->obtenerImagenes(cotizacionId: 1);

// Resultado:
// [
//     'bordado' => ['/storage/cotizaciones/1/bordado/...', ...],
//     'estampado' => ['/storage/cotizaciones/1/estampado/...', ...],
//     'tela' => [...],
//     'prenda' => [...],
//     'general' => [...]
// ]
```

### 4. Obtener Imágenes por Tipo

```php
$imagenesBordado = $imagenService->obtenerImagenesPorTipo(
    cotizacionId: 1,
    tipo: 'bordado'
);

// ['/storage/cotizaciones/1/bordado/...', ...]
```

### 5. Eliminar una Imagen

```php
$eliminada = $imagenService->eliminarImagen(
    rutaPublica: '/storage/cotizaciones/1/bordado/1_bordado_20251119_001.jpg'
);

// true o false
```

### 6. Eliminar Todas las Imágenes de una Cotización

```php
$eliminadas = $imagenService->eliminarTodasLasImagenes(cotizacionId: 1);

// true o false (elimina toda la carpeta)
```

### 7. Validar Archivo

```php
$valido = $imagenService->validarArchivo($request->file('imagen'));

// Valida:
// - Extensión: jpg, jpeg, png, gif, webp
// - Tamaño máximo: 5MB
```

### 8. Obtener Información de Almacenamiento

```php
$info = $imagenService->obtenerInfo(cotizacionId: 1);

// [
//     'cotizacion_id' => 1,
//     'total_imagenes' => 5,
//     'tamanio_total' => 2097152,
//     'tamanio_total_mb' => 2.0,
//     'imagenes_por_tipo' => [
//         'bordado' => 2,
//         'estampado' => 1,
//         'tela' => 1,
//         'prenda' => 1,
//         'general' => 0
//     ],
//     'existe_carpeta' => true
// ]
```

## Ejemplo Completo en Controlador

```php
public function guardarCotizacionConImagenes(Request $request)
{
    $imagenService = new ImagenCotizacionService();
    
    // Crear cotización
    $cotizacion = Cotizacion::create([
        'user_id' => Auth::id(),
        'cliente' => $request->cliente,
        'estado' => 'enviada',
        'es_borrador' => false
    ]);

    // Guardar imágenes por tipo
    $tipos = ['bordado', 'estampado', 'tela', 'prenda'];
    $imagenes = [];

    foreach ($tipos as $tipo) {
        if ($request->hasFile($tipo)) {
            // Validar archivos
            foreach ($request->file($tipo) as $archivo) {
                if (!$imagenService->validarArchivo($archivo)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Archivo inválido: {$archivo->getClientOriginalName()}"
                    ], 422);
                }
            }

            // Guardar múltiples
            $rutas = $imagenService->guardarMultiples(
                $cotizacion->id,
                $request->file($tipo),
                $tipo
            );

            $imagenes[$tipo] = $rutas;
        }
    }

    // Guardar rutas en BD
    $cotizacion->update([
        'imagenes' => array_merge(...array_values($imagenes))
    ]);

    return response()->json([
        'success' => true,
        'cotizacion_id' => $cotizacion->id,
        'imagenes' => $imagenes
    ]);
}
```

## Convención de Nombres de Archivos

Formato: `{cotizacion_id}_{tipo}_{timestamp}_{random}.{extension}`

Ejemplo: `1_bordado_20251119150530_042.jpg`

- `1` = ID de cotización
- `bordado` = Tipo de imagen
- `20251119150530` = Timestamp (YYYYMMDDHHMMSS)
- `042` = Número aleatorio (001-999)
- `jpg` = Extensión

## Ventajas de esta Estructura

✅ **Organización Clara**: Cada cotización tiene su propia carpeta
✅ **Fácil Limpieza**: Eliminar carpeta = eliminar todas las imágenes
✅ **Escalable**: Soporta miles de cotizaciones
✅ **Nombres Únicos**: Timestamp + random evita conflictos
✅ **Backup Simple**: Respaldar carpeta `cotizaciones/`
✅ **Acceso Rápido**: URLs públicas directas
✅ **Auditoría**: Timestamp en nombre del archivo

## Configuración en .env

```env
FILESYSTEM_DISK=public
```

## Crear Enlace Simbólico

```bash
php artisan storage:link
```

Esto crea: `public/storage` → `storage/app/public`

## Límites

| Concepto | Límite |
|----------|--------|
| Tamaño máximo por archivo | 5 MB |
| Extensiones permitidas | jpg, jpeg, png, gif, webp |
| Tipos de imágenes | 5 (bordado, estampado, tela, prenda, general) |

## Mantenimiento

### Limpiar Imágenes Huérfanas

```php
// Encontrar carpetas sin cotización correspondiente
$carpetas = Storage::disk('public')->directories('cotizaciones');

foreach ($carpetas as $carpeta) {
    $cotizacionId = basename($carpeta);
    if (!Cotizacion::find($cotizacionId)) {
        Storage::disk('public')->deleteDirectory($carpeta);
    }
}
```

### Estadísticas de Almacenamiento

```php
$totalImagenes = 0;
$tamanioTotal = 0;

$carpetas = Storage::disk('public')->directories('cotizaciones');

foreach ($carpetas as $carpeta) {
    $archivos = Storage::disk('public')->allFiles($carpeta);
    $totalImagenes += count($archivos);
    
    foreach ($archivos as $archivo) {
        $tamanioTotal += Storage::disk('public')->size($archivo);
    }
}

echo "Total de imágenes: $totalImagenes";
echo "Tamaño total: " . round($tamanioTotal / 1024 / 1024, 2) . " MB";
```

## Notas Importantes

⚠️ **Siempre validar archivos** antes de guardar
⚠️ **Usar el servicio** en lugar de guardar directamente
⚠️ **Crear enlace simbólico** después de instalar
⚠️ **Respaldar carpeta `storage/app/public/cotizaciones/`** regularmente
⚠️ **Monitorear espacio en disco** si hay muchas imágenes
