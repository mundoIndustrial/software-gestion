<?php

namespace App\Domain\Pedidos\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

/**
 * Servicio de dominio para manejo de imÃ¡genes
 * Responsabilidad Ãºnica: Procesar y guardar imÃ¡genes en formato WebP
 */
class ImagenService
{
    /**
     * Guardar imagen como WebP
     * 
     * @param UploadedFile $file
     * @param string|int $numeroPedido
     * @param string $tipo Tipo de imagen: 'prendas', 'logos', 'telas'
     * @return string Ruta relativa del archivo guardado
     */
    public function guardarImagenComoWebp(UploadedFile $file, $numeroPedido, string $tipo): string
    {
        $startTime = microtime(true);
        \Log::info('[ImagenService]  Iniciando guardado de imagen', [
            'tipo' => $tipo,
            'numero_pedido' => $numeroPedido,
            'archivo_size' => $file->getSize(),
            'archivo_nombre' => $file->getClientOriginalName()
        ]);

        try {
            // Generar nombre Ãºnico
            $timestamp = now()->format('YmdHis');
            $random = substr(md5(uniqid()), 0, 8);
            $nombreArchivo = "{$numeroPedido}_{$tipo}_{$timestamp}_{$random}.webp";

            // Definir ruta segÃºn tipo
            $carpeta = match($tipo) {
                'prendas' => 'pedidos/prendas',
                'logos' => 'pedidos/logos',
                'telas' => 'pedidos/telas',
                default => 'pedidos/otros'
            };

            $rutaCompleta = storage_path("app/public/{$carpeta}");

            // Crear directorio si no existe
            $dirStartTime = microtime(true);
            if (!file_exists($rutaCompleta)) {
                \Log::info('[ImagenService] 📁 Creando directorio', ['ruta' => $rutaCompleta]);
                mkdir($rutaCompleta, 0755, true);
            }
            $dirTime = (microtime(true) - $dirStartTime) * 1000;
            \Log::info('[ImagenService]  Directorio listo', ['tiempo_ms' => round($dirTime, 2)]);

            // Procesar y guardar imagen como WebP
            $processStartTime = microtime(true);
            \Log::info('[ImagenService] 🖼️ Iniciando procesamiento de imagen');
            
            $imagen = Image::make($file->getRealPath());
            $makeTime = (microtime(true) - $processStartTime) * 1000;
            \Log::info('[ImagenService]  Image::make() completado', ['tiempo_ms' => round($makeTime, 2)]);
            
            $encodeStartTime = microtime(true);
            $imagen->encode('webp', 85); // Calidad 85%
            $encodeTime = (microtime(true) - $encodeStartTime) * 1000;
            \Log::info('[ImagenService]  Encode WebP completado', ['tiempo_ms' => round($encodeTime, 2)]);
            
            $saveStartTime = microtime(true);
            $imagen->save("{$rutaCompleta}/{$nombreArchivo}");
            $saveTime = (microtime(true) - $saveStartTime) * 1000;
            \Log::info('[ImagenService]  Save completado', ['tiempo_ms' => round($saveTime, 2)]);

            $totalTime = (microtime(true) - $startTime) * 1000;
            \Log::info('[ImagenService]  Imagen guardada exitosamente', [
                'ruta' => "{$carpeta}/{$nombreArchivo}",
                'tiempo_total_ms' => round($totalTime, 2),
                'desglose' => [
                    'directorio_ms' => round($dirTime, 2),
                    'make_ms' => round($makeTime, 2),
                    'encode_ms' => round($encodeTime, 2),
                    'save_ms' => round($saveTime, 2)
                ]
            ]);

            // Retornar ruta relativa
            return "{$carpeta}/{$nombreArchivo}";

        } catch (\Exception $e) {
            $totalTime = (microtime(true) - $startTime) * 1000;
            \Log::error('[ImagenService]  Error guardando imagen como WebP', [
                'error' => $e->getMessage(),
                'tipo' => $tipo,
                'numero_pedido' => $numeroPedido,
                'tiempo_transcurrido_ms' => round($totalTime, 2)
            ]);
            throw new \RuntimeException('No se pudo guardar la imagen');
        }
    }

    /**
     * Eliminar imagen
     * 
     * @param string $ruta Ruta relativa de la imagen (puede venir con o sin /storage/ al inicio)
     * @return bool
     */
    public function eliminarImagen(string $ruta): bool
    {
        try {
            // Limpiar la ruta: remover /storage/ al inicio si existe
            $rutaLimpia = preg_replace('|^/storage/|', '', $ruta);
            
            $rutaCompleta = storage_path("app/public/{$rutaLimpia}");
            
            \Log::debug('[ImagenService] Intentando eliminar imagen', [
                'ruta_original' => $ruta,
                'ruta_limpia' => $rutaLimpia,
                'ruta_completa' => $rutaCompleta,
                'existe' => file_exists($rutaCompleta)
            ]);
            
            if (file_exists($rutaCompleta)) {
                $resultado = unlink($rutaCompleta);
                \Log::info('[ImagenService] Imagen eliminada exitosamente', [
                    'ruta' => $ruta,
                    'ruta_completa' => $rutaCompleta
                ]);
                return $resultado;
            } else {
                \Log::warning('[ImagenService] Archivo no existe en la ruta especificada', [
                    'ruta_original' => $ruta,
                    'ruta_limpia' => $rutaLimpia,
                    'ruta_completa' => $rutaCompleta
                ]);
            }
            
            return false;
        } catch (\Exception $e) {
            \Log::error('[ImagenService] Error eliminando imagen', [
                'error' => $e->getMessage(),
                'ruta' => $ruta,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Validar que el archivo sea una imagen vÃ¡lida
     * 
     * @param UploadedFile $file
     * @return bool
     */
    public function esImagenValida(UploadedFile $file): bool
    {
        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower($file->getClientOriginalExtension());
        
        return in_array($extension, $extensionesPermitidas) && $file->isValid();
    }
}

