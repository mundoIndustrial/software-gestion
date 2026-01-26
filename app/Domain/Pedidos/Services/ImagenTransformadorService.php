<?php

namespace App\Domain\Pedidos\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * ImagenTransformadorService
 * 
 * Responsabilidad: Transformar imÃ¡genes a WebP con compresión y redimensionamiento
 * Centraliza la lógica de conversión de imÃ¡genes para toda la aplicación
 */
class ImagenTransformadorService
{
    /**
     * Transformar imagen a WebP
     * 
     * @param UploadedFile $archivo
     * @param string $directorio Ruta completa del directorio donde guardar
     * @param int $index Ãndice de la imagen
     * @param string $tipo Tipo de imagen (prenda, tela, proceso, epp)
     * @return array ['nombreArchivo' => 'img_tipo_0_20260121_abc123.webp', 'tamaÃ±o' => bytes]
     */
    public function transformarAWebp(
        UploadedFile $archivo,
        string $directorio,
        int $index,
        string $tipo = 'imagen'
    ): array {
        try {
            // Crear directorio si no existe
            if (!is_dir($directorio)) {
                mkdir($directorio, 0755, true);
            }
            
            // Convertir a WebP usando ImageManager
            try {
                $imagen = \Intervention\Image\ImageManager::gd()->read($archivo->getRealPath());
            } catch (\Exception $e) {
                Log::warning(' [ImagenTransformadorService] Error con GD, intentando ImageMagick', [
                    'error' => $e->getMessage()
                ]);
                try {
                    $imagen = \Intervention\Image\ImageManager::imagick()->read($archivo->getRealPath());
                } catch (\Exception $e2) {
                    throw new \Exception("No se pudo procesar imagen: " . $e2->getMessage());
                }
            }
            
            // Redimensionar si es necesario
            if ($imagen->width() > 2000 || $imagen->height() > 2000) {
                $imagen->scaleDown(width: 2000, height: 2000);
            }
            
            // Convertir a WebP con calidad 80
            $webp = $imagen->toWebp(quality: 80);
            $contenidoWebP = $webp->toString();
            $tamaÃ±o = strlen($contenidoWebP);
            
            // Generar nombre Ãºnico
            $timestamp = now()->format('YmdHis');
            $random = substr(uniqid(), -6);
            $nombreArchivo = "img_{$tipo}_{$index}_{$timestamp}_{$random}.webp";
            $rutaCompleta = $directorio . '/' . $nombreArchivo;
            
            // Guardar archivo
            file_put_contents($rutaCompleta, $contenidoWebP);
            
            Log::info(' [ImagenTransformadorService] Imagen transformada a WebP', [
                'tipo' => $tipo,
                'archivo_original' => $archivo->getClientOriginalName(),
                'tamaÃ±o_original' => $archivo->getSize(),
                'tamaÃ±o_webp' => $tamaÃ±o,
                'nombre_archivo' => $nombreArchivo,
            ]);
            
            return [
                'nombreArchivo' => $nombreArchivo,
                'tamaÃ±o' => $tamaÃ±o,
            ];
        } catch (\Exception $e) {
            Log::error(' [ImagenTransformadorService] Error transformando imagen', [
                'archivo' => $archivo->getClientOriginalName(),
                'tipo' => $tipo,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

