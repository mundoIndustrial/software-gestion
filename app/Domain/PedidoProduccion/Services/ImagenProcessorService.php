<?php

namespace App\Domain\PedidoProduccion\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Servicio de Dominio para procesar y guardar imágenes
 */
class ImagenProcessorService
{
    /**
     * Procesar imagen: convertir a WebP y crear miniatura
     */
    public function procesarYGuardarImagen(UploadedFile $archivoSubido, string $numeroPedido, int $index): ?array
    {
        try {
            // Leer imagen
            $image = \Intervention\Image\ImageManager::gd()->read($archivoSubido->getRealPath());

            // Crear nombre único
            $timestamp = now()->format('YmdHis');
            $random = substr(uniqid(), -6);
            $baseFilename = "pedido_{$numeroPedido}_img_{$index}_{$timestamp}_{$random}";

            // Crear directorio
            $dirPath = "prendas/pedidos/{$numeroPedido}";
            Storage::disk('public')->makeDirectory($dirPath, 0755, true);

            // Guardar original
            $originalPath = "{$dirPath}/{$baseFilename}.jpg";
            Storage::disk('public')->put($originalPath, $image->encode('jpeg', 90)->toString());

            // Guardar WebP
            $webpPath = "{$dirPath}/{$baseFilename}.webp";
            Storage::disk('public')->put($webpPath, $image->toWebp(quality: 85)->toString());

            // Crear miniatura
            $thumbnail = $image->scaleDown(width: 300, height: 300);
            $thumbPath = "{$dirPath}/{$baseFilename}_thumb.webp";
            Storage::disk('public')->put($thumbPath, $thumbnail->toWebp(quality: 80)->toString());

            Log::info('📷 Imagen procesada', [
                'numero_pedido' => $numeroPedido,
                'original' => $originalPath,
                'webp' => $webpPath,
                'miniatura' => $thumbPath,
            ]);

            return [
                'ruta_original' => $originalPath,
                'ruta_webp' => $webpPath,
                'ruta_miniatura' => $thumbPath,
            ];

        } catch (\Exception $e) {
            Log::error(' Error procesando imagen', [
                'numero_pedido' => $numeroPedido,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
