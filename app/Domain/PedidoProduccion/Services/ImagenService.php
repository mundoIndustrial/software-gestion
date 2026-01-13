<?php

namespace App\Domain\PedidoProduccion\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

/**
 * Servicio de dominio para manejo de imágenes
 * Responsabilidad única: Procesar y guardar imágenes en formato WebP
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
        try {
            // Generar nombre único
            $timestamp = now()->format('YmdHis');
            $random = substr(md5(uniqid()), 0, 8);
            $nombreArchivo = "{$numeroPedido}_{$tipo}_{$timestamp}_{$random}.webp";

            // Definir ruta según tipo
            $carpeta = match($tipo) {
                'prendas' => 'pedidos/prendas',
                'logos' => 'pedidos/logos',
                'telas' => 'pedidos/telas',
                default => 'pedidos/otros'
            };

            $rutaCompleta = storage_path("app/public/{$carpeta}");

            // Crear directorio si no existe
            if (!file_exists($rutaCompleta)) {
                mkdir($rutaCompleta, 0755, true);
            }

            // Procesar y guardar imagen como WebP
            $imagen = Image::make($file->getRealPath());
            $imagen->encode('webp', 85); // Calidad 85%
            $imagen->save("{$rutaCompleta}/{$nombreArchivo}");

            // Retornar ruta relativa
            return "{$carpeta}/{$nombreArchivo}";

        } catch (\Exception $e) {
            \Log::error('Error guardando imagen como WebP', [
                'error' => $e->getMessage(),
                'tipo' => $tipo,
                'numero_pedido' => $numeroPedido
            ]);
            throw new \RuntimeException('No se pudo guardar la imagen');
        }
    }

    /**
     * Eliminar imagen
     * 
     * @param string $ruta Ruta relativa de la imagen
     * @return bool
     */
    public function eliminarImagen(string $ruta): bool
    {
        try {
            $rutaCompleta = storage_path("app/public/{$ruta}");
            
            if (file_exists($rutaCompleta)) {
                return unlink($rutaCompleta);
            }
            
            return false;
        } catch (\Exception $e) {
            \Log::error('Error eliminando imagen', [
                'error' => $e->getMessage(),
                'ruta' => $ruta
            ]);
            return false;
        }
    }

    /**
     * Validar que el archivo sea una imagen válida
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
