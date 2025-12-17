<?php

namespace App\Infrastructure\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Log;

/**
 * ImagenAlmacenador - Servicio para guardar imágenes en storage
 *
 * Responsabilidades:
 * - Validar archivos (tamaño, tipo MIME)
 * - Procesar imágenes (redimensionar, convertir a WebP)
 * - Guardar en storage/cotizaciones/{id}/{tipo}/
 * - Retornar rutas relativas
 *
 * Ventajas sobre Base64:
 * - 33% menos datos transmitidos
 * - Más rápido
 * - Escalable
 * - Estándar de la industria
 */
final class ImagenAlmacenador
{
    private const TIPOS_PERMITIDOS = ['prenda', 'tela', 'logo', 'bordado', 'estampado'];
    private const TAMANO_MAXIMO = 5 * 1024 * 1024; // 5 MB
    private const DIMENSIONES_MAXIMAS = 2000;
    private const CALIDAD_WEBP = 85;

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
     * @return string Ruta relativa (storage/cotizaciones/...)
     * @throws \DomainException Si hay error de validación
     */
    public function guardar(
        UploadedFile $archivo,
        int $cotizacionId,
        int $prendaId,
        string $tipo
    ): string {
        Log::info('ImagenAlmacenador: Iniciando guardado', [
            'cotizacion_id' => $cotizacionId,
            'prenda_id' => $prendaId,
            'tipo' => $tipo,
            'archivo' => $archivo->getClientOriginalName(),
            'tamaño' => $archivo->getSize(),
        ]);

        try {
            // Validar archivo
            $this->validar($archivo, $tipo);

            // Generar ruta
            $rutaRelativa = $this->generarRuta($cotizacionId, $prendaId, $tipo);

            // Crear directorio si no existe
            $directorio = dirname($rutaRelativa);
            if (!Storage::disk('public')->exists($directorio)) {
                Storage::disk('public')->makeDirectory($directorio, 0755, true);
                Log::info('ImagenAlmacenador: Directorio creado', ['directorio' => $directorio]);
            }

            // Procesar imagen
            $imagen = $this->imageManager->read($archivo->getRealPath());

            // Redimensionar si es necesario
            if ($imagen->width() > self::DIMENSIONES_MAXIMAS ||
                $imagen->height() > self::DIMENSIONES_MAXIMAS) {
                Log::info('ImagenAlmacenador: Redimensionando imagen', [
                    'ancho_original' => $imagen->width(),
                    'alto_original' => $imagen->height(),
                ]);

                $imagen->scaleDown(self::DIMENSIONES_MAXIMAS, self::DIMENSIONES_MAXIMAS);

                Log::info('ImagenAlmacenador: Imagen redimensionada', [
                    'ancho_nuevo' => $imagen->width(),
                    'alto_nuevo' => $imagen->height(),
                ]);
            }

            // Convertir a WebP y guardar
            $contenido = $imagen->toWebp(self::CALIDAD_WEBP);
            Storage::disk('public')->put($rutaRelativa, $contenido);

            // Retornar ruta SIN el prefijo 'storage/' - se agregará en la vista
            // Esto previene URLs duplicadas como /storage/storage/cotizaciones/...
            $rutaGuardada = $rutaRelativa;

            Log::info('ImagenAlmacenador: Imagen guardada exitosamente', [
                'cotizacion_id' => $cotizacionId,
                'prenda_id' => $prendaId,
                'tipo' => $tipo,
                'ruta_guardada' => $rutaGuardada,
                'tamaño' => Storage::disk('public')->size($rutaRelativa),
            ]);

            return $rutaGuardada;
        } catch (\Exception $e) {
            Log::error('ImagenAlmacenador: Error al guardar imagen', [
                'cotizacion_id' => $cotizacionId,
                'prenda_id' => $prendaId,
                'tipo' => $tipo,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Validar archivo
     *
     * @param UploadedFile $archivo
     * @param string $tipo
     * @throws \DomainException
     */
    private function validar(UploadedFile $archivo, string $tipo): void
    {
        // Validar tipo
        if (!in_array($tipo, self::TIPOS_PERMITIDOS)) {
            throw new \DomainException("Tipo de imagen no permitido: {$tipo}");
        }

            // Validar tamaño
            if ($archivo->getSize() > self::TAMANO_MAXIMO) {
                throw new \DomainException(
                    "Archivo demasiado grande. Máximo: " . (self::TAMANO_MAXIMO / 1024 / 1024) . " MB"
                );
            }

        // Validar MIME type
        $mimeType = $archivo->getMimeType();
        $tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($mimeType, $tiposPermitidos)) {
            throw new \DomainException("Tipo de archivo no permitido: {$mimeType}");
        }

        Log::info('ImagenAlmacenador: Archivo validado', [
            'archivo' => $archivo->getClientOriginalName(),
            'tipo' => $tipo,
            'mime' => $mimeType,
            'tamaño' => $archivo->getSize(),
        ]);
    }

    /**
     * Generar ruta de almacenamiento
     *
     * @param int $cotizacionId
     * @param int $prendaId
     * @param string $tipo
     * @return string Ruta relativa
     */
    private function generarRuta(int $cotizacionId, int $prendaId, string $tipo): string
    {
        $nombreUnico = $this->generarNombreUnico($tipo, $prendaId);
        return "cotizaciones/{$cotizacionId}/{$tipo}/{$nombreUnico}.webp";
    }

    /**
     * Generar nombre único para archivo
     *
     * Formato: {tipo}_{prenda_id}_{timestamp}_{random}.webp
     * Ejemplo: prenda_1_1702564859_1234.webp
     *
     * @param string $tipo
     * @param int $prendaId
     * @return string
     */
    private function generarNombreUnico(string $tipo, int $prendaId): string
    {
        $timestamp = now()->getTimestamp();
        $random = rand(1000, 9999);

        return "{$tipo}_{$prendaId}_{$timestamp}_{$random}";
    }
}
