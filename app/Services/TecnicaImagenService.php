<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Intervention\Image\ImageManager;

/**
 * Servicio para procesar y guardar imágenes de técnicas en cotizaciones
 * 
 * Guarda en estructura:
 * - Individual: /public/cotizaciones/{cotizacion_id}/simple/{tipo_logo}/{imagen}.webp
 * - Combinada: /public/cotizaciones/{cotizacion_id}/combinada/{grupo}/{tipo_logo}/{imagen}.webp
 */
class TecnicaImagenService
{
    private ImageManager $imageManager;
    
    public function __construct()
    {
        $this->imageManager = ImageManager::gd();
    }

    /**
     * Guardar una imagen de técnica
     * 
     * @param UploadedFile $file Archivo subido
     * @param int $cotizacionId ID de la cotización
     * @param string $tipoLogoNombre Nombre del tipo de logo (bordado, estampado, etc)
     * @param int|null $grupoCombinado Si es técnica combinada, el ID del grupo
     * @return array Rutas guardadas [ruta_original, ruta_webp, ruta_miniatura]
     */
    public function guardarImagen(UploadedFile $file, int $cotizacionId, string $tipoLogoNombre, ?int $grupoCombinado = null)
    {
        try {
            \Log::info('📸 Guardando imagen de técnica', [
                'cotizacion_id' => $cotizacionId,
                'tipo_logo' => $tipoLogoNombre,
                'grupo_combinado' => $grupoCombinado,
                'filename' => $file->getClientOriginalName(),
                'size_kb' => $file->getSize() / 1024
            ]);

            // Generar nombre único
            $nombreUnico = Str::uuid() . '.' . $file->getClientOriginalExtension();
            
            // Determinar ruta base según si es simple o combinada
            if ($grupoCombinado !== null) {
                // COMBINADA: /cotizaciones/{cot_id}/combinada/{grupo}/{tipo_logo}/
                $rutaBase = "cotizaciones/{$cotizacionId}/combinada/{$grupoCombinado}/{$tipoLogoNombre}";
            } else {
                // SIMPLE: /cotizaciones/{cot_id}/simple/{tipo_logo}/
                $rutaBase = "cotizaciones/{$cotizacionId}/simple/{$tipoLogoNombre}";
            }

            // Crear directorio si no existe
            if (!Storage::disk('public')->exists($rutaBase)) {
                Storage::disk('public')->makeDirectory($rutaBase, 0755, true);
            }

            // 1. Guardar imagen original
            $rutaOriginal = "{$rutaBase}/original_{$nombreUnico}";
            Storage::disk('public')->putFileAs(
                dirname($rutaOriginal),
                $file,
                basename($rutaOriginal)
            );

            // Leer contenido para procesamiento
            $contenido = file_get_contents($file->getRealPath());
            $image = $this->imageManager->read($contenido);

            $ancho = $image->width();
            $alto = $image->height();

            \Log::info('✓ Imagen original guardada', [
                'ruta' => $rutaOriginal,
                'ancho' => $ancho,
                'alto' => $alto,
                'tamaño_bytes' => filesize($file->getRealPath())
            ]);

            // 2. Redimensionar si es muy grande (máximo 2000x2000)
            if ($ancho > 2000 || $alto > 2000) {
                $image->scaleDown(2000, 2000);
                \Log::info('✓ Imagen redimensionada', [
                    'nuevo_ancho' => $image->width(),
                    'nuevo_alto' => $image->height()
                ]);
            }

            // 3. Guardar como WebP (optimizado)
            $rutaWebp = "{$rutaBase}/{$nombreUnico}.webp";
            $contenidoWebP = $image->toWebp(85);
            Storage::disk('public')->put($rutaWebp, $contenidoWebP);

            $tamañoWebP = Storage::disk('public')->size($rutaWebp);

            \Log::info('✅ WebP guardado', [
                'ruta' => $rutaWebp,
                'tamaño_bytes' => $tamañoWebP
            ]);

            // 4. Crear miniatura (máximo 300x300)
            $rutaMiniatura = "{$rutaBase}/thumb_{$nombreUnico}.webp";
            $thumbnail = clone $image;
            $thumbnail->scaleDown(300, 300);
            $contenidoThumb = $thumbnail->toWebp(75);
            Storage::disk('public')->put($rutaMiniatura, $contenidoThumb);

            \Log::info('✅ Miniatura guardada', [
                'ruta' => $rutaMiniatura,
                'tamaño_bytes' => Storage::disk('public')->size($rutaMiniatura)
            ]);

            // Retornar las 3 rutas (relativas a public/)
            return [
                'ruta_original' => $rutaOriginal,
                'ruta_webp' => $rutaWebp,
                'ruta_miniatura' => $rutaMiniatura,
                'ancho' => $image->width(),
                'alto' => $image->height(),
                'tamaño' => $tamañoWebP
            ];

        } catch (\Exception $e) {
            \Log::error('❌ Error al guardar imagen de técnica', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }

    /**
     * Guardar múltiples imágenes
     */
    public function guardarMultiples(array $archivos, int $cotizacionId, string $tipoLogoNombre, ?int $grupoCombinado = null): array
    {
        $rutas = [];
        
        foreach ($archivos as $index => $archivo) {
            try {
                $resultado = $this->guardarImagen($archivo, $cotizacionId, $tipoLogoNombre, $grupoCombinado);
                $resultado['orden'] = $index;
                $rutas[] = $resultado;
                \Log::info("✓ Imagen {$index} guardada");
            } catch (\Exception $e) {
                \Log::error("❌ Error en imagen {$index}", ['error' => $e->getMessage()]);
                // Continuar con la siguiente
            }
        }
        
        return $rutas;
    }
}
