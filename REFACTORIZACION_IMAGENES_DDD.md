# 🖼️ REFACTORIZACIÓN DE IMÁGENES - ARQUITECTURA DDD

**Fecha:** 10 de Diciembre de 2025
**Estado:** 📋 PLAN DE IMPLEMENTACIÓN
**Prioridad:** ALTA

---

## ⚠️ PROBLEMA ACTUAL

### Base64 - Mala Práctica

**Problemas:**
- ❌ Aumenta tamaño de payload 33% (base64 encoding)
- ❌ Consume más ancho de banda
- ❌ Lentitud en transmisión
- ❌ Difícil de debuggear
- ❌ No es escalable para múltiples imágenes
- ❌ Carga toda la imagen en memoria

**Ejemplo:**
```
Imagen original: 245 KB
Base64 encoded: 327 KB (33% más)
Transmisión: Más lenta
```

---

## ✅ SOLUCIÓN: UPLOAD DIRECTO (FormData)

### Ventajas

✅ **Eficiente:** Transmisión directa sin encoding
✅ **Rápido:** Menor tamaño de payload
✅ **Escalable:** Múltiples archivos simultáneamente
✅ **Estándar:** Práctica recomendada en la industria
✅ **Seguro:** Validación en servidor
✅ **Debuggeable:** Fácil de inspeccionar

---

## 🏗️ NUEVA ARQUITECTURA

### Domain Layer

#### 1. Value Object: `RutaImagen` (Ya existe)
```php
namespace App\Domain\Cotizacion\ValueObjects;

final readonly class RutaImagen
{
    public function __construct(
        private string $ruta
    ) {
        if (empty($ruta)) {
            throw new \DomainException('Ruta de imagen no puede estar vacía');
        }
    }

    public function valor(): string
    {
        return $this->ruta;
    }

    public static function crear(string $ruta): self
    {
        return new self($ruta);
    }
}
```

#### 2. Entity: `PrendaCotizacion` (Ya existe)
```php
// Propiedades para imágenes
private array $fotos = [];  // URLs de fotos guardadas
private array $telas = [];  // URLs de telas guardadas

public function agregarFoto(RutaImagen $ruta): void
{
    $this->fotos[] = $ruta->valor();
}

public function agregarTela(RutaImagen $ruta): void
{
    $this->telas[] = $ruta->valor();
}
```

### Application Layer

#### 1. DTO: `SubirImagenDTO`
```php
namespace App\Application\Cotizacion\DTOs;

final readonly class SubirImagenDTO
{
    public function __construct(
        public int $cotizacionId,
        public int $prendaId,
        public string $tipo,  // 'prenda' o 'tela'
        public \SplFileInfo $archivo
    ) {
    }

    public static function desdeRequest(
        int $cotizacionId,
        int $prendaId,
        string $tipo,
        \Illuminate\Http\UploadedFile $archivo
    ): self {
        return new self($cotizacionId, $prendaId, $tipo, $archivo);
    }
}
```

#### 2. Command: `SubirImagenCotizacionCommand`
```php
namespace App\Application\Cotizacion\Commands;

final readonly class SubirImagenCotizacionCommand
{
    public function __construct(
        public int $cotizacionId,
        public int $prendaId,
        public string $tipo,
        public \SplFileInfo $archivo,
        public int $usuarioId
    ) {
    }

    public static function crear(
        int $cotizacionId,
        int $prendaId,
        string $tipo,
        \SplFileInfo $archivo,
        int $usuarioId
    ): self {
        return new self($cotizacionId, $prendaId, $tipo, $archivo, $usuarioId);
    }
}
```

#### 3. Handler: `SubirImagenCotizacionHandler`
```php
namespace App\Application\Cotizacion\Handlers\Commands;

use App\Application\Cotizacion\Commands\SubirImagenCotizacionCommand;
use App\Domain\Cotizacion\Repositories\CotizacionRepositoryInterface;
use App\Domain\Cotizacion\ValueObjects\CotizacionId;
use App\Domain\Cotizacion\ValueObjects\RutaImagen;
use App\Infrastructure\Storage\ImagenAlmacenador;
use Illuminate\Support\Facades\Log;

final class SubirImagenCotizacionHandler
{
    public function __construct(
        private readonly CotizacionRepositoryInterface $repository,
        private readonly ImagenAlmacenador $almacenador
    ) {
    }

    public function handle(SubirImagenCotizacionCommand $comando): RutaImagen
    {
        Log::info('SubirImagenCotizacionHandler: Iniciando subida', [
            'cotizacion_id' => $comando->cotizacionId,
            'prenda_id' => $comando->prendaId,
            'tipo' => $comando->tipo,
            'archivo' => $comando->archivo->getFilename(),
        ]);

        try {
            // Obtener cotización
            $cotizacionId = CotizacionId::crear($comando->cotizacionId);
            $cotizacion = $this->repository->findById($cotizacionId);

            if (!$cotizacion) {
                throw new \DomainException('Cotización no encontrada');
            }

            // Guardar imagen en storage
            $ruta = $this->almacenador->guardar(
                $comando->archivo,
                $comando->cotizacionId,
                $comando->prendaId,
                $comando->tipo
            );

            $rutaImagen = RutaImagen::crear($ruta);

            // Agregar ruta a la prenda
            if ($comando->tipo === 'prenda') {
                $prenda = $cotizacion->obtenerPrenda($comando->prendaId);
                $prenda->agregarFoto($rutaImagen);
            } elseif ($comando->tipo === 'tela') {
                $prenda = $cotizacion->obtenerPrenda($comando->prendaId);
                $prenda->agregarTela($rutaImagen);
            }

            // Guardar cambios
            $this->repository->save($cotizacion);

            Log::info('SubirImagenCotizacionHandler: Imagen subida exitosamente', [
                'cotizacion_id' => $comando->cotizacionId,
                'ruta' => $ruta,
            ]);

            return $rutaImagen;
        } catch (\Exception $e) {
            Log::error('SubirImagenCotizacionHandler: Error al subir imagen', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
```

### Infrastructure Layer

#### 1. Servicio: `ImagenAlmacenador`
```php
namespace App\Infrastructure\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

final class ImagenAlmacenador
{
    private const TIPOS_PERMITIDOS = ['prenda', 'tela', 'logo', 'bordado', 'estampado'];
    private const TAMAÑO_MAXIMO = 5 * 1024 * 1024; // 5 MB
    private const DIMENSIONES_MAXIMAS = 2000;

    public function __construct(
        private readonly ImageManager $imageManager
    ) {
    }

    /**
     * Guardar imagen en storage
     * 
     * @param UploadedFile $archivo
     * @param int $cotizacionId
     * @param int $prendaId
     * @param string $tipo
     * @return string Ruta relativa
     */
    public function guardar(
        UploadedFile $archivo,
        int $cotizacionId,
        int $prendaId,
        string $tipo
    ): string {
        // Validar
        $this->validar($archivo, $tipo);

        // Generar ruta
        $rutaRelativa = $this->generarRuta($cotizacionId, $prendaId, $tipo);

        // Crear directorio
        $directorio = dirname($rutaRelativa);
        if (!Storage::disk('public')->exists($directorio)) {
            Storage::disk('public')->makeDirectory($directorio, 0755, true);
        }

        // Procesar imagen
        $imagen = $this->imageManager->read($archivo->getRealPath());

        // Redimensionar si es necesario
        if ($imagen->width() > self::DIMENSIONES_MAXIMAS || 
            $imagen->height() > self::DIMENSIONES_MAXIMAS) {
            $imagen->scaleDown(self::DIMENSIONES_MAXIMAS, self::DIMENSIONES_MAXIMAS);
        }

        // Guardar como WebP
        $contenido = $imagen->toWebp(85);
        Storage::disk('public')->put($rutaRelativa, $contenido);

        return "storage/{$rutaRelativa}";
    }

    /**
     * Validar archivo
     */
    private function validar(UploadedFile $archivo, string $tipo): void
    {
        // Validar tipo
        if (!in_array($tipo, self::TIPOS_PERMITIDOS)) {
            throw new \DomainException("Tipo de imagen no permitido: {$tipo}");
        }

        // Validar tamaño
        if ($archivo->getSize() > self::TAMAÑO_MAXIMO) {
            throw new \DomainException("Archivo demasiado grande. Máximo: 5 MB");
        }

        // Validar MIME type
        $mimeType = $archivo->getMimeType();
        $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $tiposPermitidos)) {
            throw new \DomainException("Tipo de archivo no permitido: {$mimeType}");
        }
    }

    /**
     * Generar ruta de almacenamiento
     */
    private function generarRuta(int $cotizacionId, int $prendaId, string $tipo): string
    {
        $nombreUnico = $this->generarNombreUnico($tipo, $prendaId);
        return "cotizaciones/{$cotizacionId}/{$tipo}/{$nombreUnico}.webp";
    }

    /**
     * Generar nombre único
     */
    private function generarNombreUnico(string $tipo, int $prendaId): string
    {
        $timestamp = now()->getTimestamp();
        $random = rand(1000, 9999);
        return "{$tipo}_{$prendaId}_{$timestamp}_{$random}";
    }
}
```

#### 2. Controller: `CotizacionController` (Actualizado)
```php
public function subirImagen(Request $request, int $id): JsonResponse
{
    try {
        $request->validate([
            'prenda_id' => 'required|integer',
            'tipo' => 'required|in:prenda,tela,logo,bordado,estampado',
            'archivo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $comando = SubirImagenCotizacionCommand::crear(
            $id,
            $request->input('prenda_id'),
            $request->input('tipo'),
            $request->file('archivo'),
            Auth::id()
        );

        $rutaImagen = $this->subirImagenHandler->handle($comando);

        return response()->json([
            'success' => true,
            'message' => 'Imagen subida exitosamente',
            'data' => [
                'ruta' => $rutaImagen->valor(),
            ],
        ], 201);
    } catch (\DomainException $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 400);
    } catch (\Exception $e) {
        Log::error('Error al subir imagen', ['error' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'Error al subir imagen',
        ], 500);
    }
}
```

---

## 🔄 MIGRACIÓN DEL FRONTEND

### Antes (Base64 - ❌ MAL)
```javascript
// Leer archivo como Base64
const reader = new FileReader();
reader.onload = function(e) {
    const base64 = e.target.result;
    
    // Enviar como JSON (pesado)
    fetch('/asesores/cotizaciones/37/imagenes', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token
        },
        body: JSON.stringify({
            fotos_base64: [base64],
            tipo: 'prenda'
        })
    });
};
reader.readAsDataURL(file);
```

### Después (FormData - ✅ BIEN)
```javascript
// Usar FormData (eficiente)
const formData = new FormData();
formData.append('archivo', file);
formData.append('prenda_id', prendaId);
formData.append('tipo', 'prenda');

fetch(`/asesores/cotizaciones/${cotizacionId}/imagenes`, {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': token
    },
    body: formData  // FormData maneja multipart/form-data automáticamente
})
.then(r => r.json())
.then(data => {
    if (data.success) {
        console.log('Imagen subida:', data.data.ruta);
    }
});
```

---

## 📋 PLAN DE IMPLEMENTACIÓN

### Fase 1: Crear Infraestructura (1-2 horas)
- [ ] Crear `ImagenAlmacenador.php`
- [ ] Crear `SubirImagenCotizacionCommand.php`
- [ ] Crear `SubirImagenCotizacionHandler.php`
- [ ] Crear `SubirImagenDTO.php`

### Fase 2: Actualizar Domain (30 minutos)
- [ ] Actualizar `PrendaCotizacion.php` (agregar métodos)
- [ ] Actualizar `RutaImagen.php` (si es necesario)

### Fase 3: Actualizar Controller (30 minutos)
- [ ] Agregar método `subirImagen()` a `CotizacionController`
- [ ] Agregar inyección de `SubirImagenCotizacionHandler`
- [ ] Agregar ruta POST `/asesores/cotizaciones/{id}/imagenes`

### Fase 4: Actualizar Frontend (1-2 horas)
- [ ] Cambiar `guardado.js` para usar FormData
- [ ] Remover procesamiento Base64
- [ ] Agregar validación de archivos en cliente
- [ ] Agregar feedback visual de progreso

### Fase 5: Testing (1 hora)
- [ ] Tests unitarios para `ImagenAlmacenador`
- [ ] Tests E2E para subida de imágenes
- [ ] Verificar en staging

### Fase 6: Cleanup (30 minutos)
- [ ] Remover `ImagenProcesadorService` (ya no se usa)
- [ ] Remover `ImagenService` (ya no se usa)
- [ ] Remover `ImagenCotizacionService` (ya no se usa)
- [ ] Actualizar documentación

---

## 📊 COMPARATIVA

| Aspecto | Base64 | FormData |
|---------|--------|----------|
| **Tamaño payload** | +33% | Normal |
| **Velocidad** | Lenta | Rápida |
| **Escalabilidad** | Limitada | Excelente |
| **Memoria** | Alta | Baja |
| **Estándar** | ❌ No | ✅ Sí |
| **Debugging** | Difícil | Fácil |
| **Seguridad** | Media | Alta |

---

## 🎯 BENEFICIOS

✅ **Rendimiento:** 33% menos datos transmitidos
✅ **Escalabilidad:** Múltiples archivos simultáneamente
✅ **Seguridad:** Validación en servidor
✅ **UX:** Feedback de progreso
✅ **Mantenibilidad:** Código más limpio
✅ **Estándar:** Práctica recomendada

---

## 📝 PRÓXIMOS PASOS

1. Crear `ImagenAlmacenador.php`
2. Crear Command y Handler
3. Actualizar Controller
4. Actualizar Frontend
5. Crear Tests
6. Remover código legacy

---

**Estado:** 📋 PLAN LISTO PARA IMPLEMENTACIÓN
**Prioridad:** ALTA
**Estimado:** 4-6 horas
