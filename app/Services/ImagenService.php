<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Illuminate\Support\Facades\File;

/**
 * Servicio para gestionar imágenes
 * - Convertir a WebP
 * - Guardar en estructura de carpetas
 * - Generar nombres únicos
 */
class ImagenService
{
    /**
     * Guardar imagen de prenda
     * 
     * @param UploadedFile $file
     * @param int $cotizacionId
     * @return string Nombre del archivo guardado
     */
    public function guardarImagenPrenda(UploadedFile $file, int $cotizacionId): string
    {
        return $this->guardarImagen($file, $cotizacionId, 'prendas');
    }

    /**
     * Guardar imagen de tela
     * 
     * @param UploadedFile $file
     * @param int $cotizacionId
     * @return string Nombre del archivo guardado
     */
    public function guardarImagenTela(UploadedFile $file, int $cotizacionId): string
    {
        return $this->guardarImagen($file, $cotizacionId, 'telas');
    }

    /**
     * Guardar imagen en la carpeta especificada
     * 
     * @param UploadedFile $file
     * @param int $cotizacionId
     * @param string $tipo 'prendas' o 'telas'
     * @return string Nombre del archivo guardado (sin ruta)
     */
    private function guardarImagen(UploadedFile $file, int $cotizacionId, string $tipo): string
    {
        try {
            // Crear estructura de carpetas en storage/app/public
            $rutaCarpeta = storage_path("app/public/cotizaciones/{$cotizacionId}/{$tipo}");
            
            if (!File::exists($rutaCarpeta)) {
                File::makeDirectory($rutaCarpeta, 0755, true);
                \Log::info("📁 Carpeta creada", ['ruta' => $rutaCarpeta]);
            }
            
            // Generar nombre único
            $nombreOriginal = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $nombreUnico = $this->generarNombreUnico($nombreOriginal);
            $nombreWebP = $nombreUnico . '.webp';
            
            // Crear ImageManager con GD driver
            $manager = new ImageManager(new GdDriver());
            
            // Leer, convertir a WebP y guardar
            $imagen = $manager->read($file->getRealPath());
            $rutaCompleta = $rutaCarpeta . DIRECTORY_SEPARATOR . $nombreWebP;
            $imagen->toWebp(85)->save($rutaCompleta);
            
            // Retornar la URL relativa para acceso web via storage symlink
            $rutaRelativa = "storage/cotizaciones/{$cotizacionId}/{$tipo}/{$nombreWebP}";
            
            \Log::info("✅ Imagen guardada", [
                'nombre' => $nombreWebP,
                'tipo' => $tipo,
                'cotizacion_id' => $cotizacionId,
                'ruta_almacenamiento' => $rutaCompleta,
                'ruta_acceso' => $rutaRelativa,
                'tamano' => filesize($rutaCompleta) . ' bytes'
            ]);
            
            return $rutaRelativa;
            
        } catch (\Exception $e) {
            \Log::error("❌ Error al guardar imagen", [
                'error' => $e->getMessage(),
                'tipo' => $tipo,
                'cotizacion_id' => $cotizacionId,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Generar nombre único para archivo
     * 
     * @param string $nombreBase
     * @return string
     */
    private function generarNombreUnico(string $nombreBase): string
    {
        // Limpiar nombre: solo letras, números, guiones
        $nombreLimpio = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $nombreBase);
        
        // Limitar a 50 caracteres
        $nombreLimpio = substr($nombreLimpio, 0, 50);
        
        // Agregar timestamp para unicidad
        $timestamp = time();
        $random = substr(uniqid(), -4);
        
        return "{$nombreLimpio}_{$timestamp}_{$random}";
    }

    /**
     * Obtener URL relativa de imagen guardada
     * 
     * @param int $cotizacionId
     * @param string $tipo 'prendas' o 'telas'
     * @param string $nombreArchivo
     * @return string
     */
    public function obtenerUrlImagen(int $cotizacionId, string $tipo, string $nombreArchivo): string
    {
        return "/cotizaciones/{$cotizacionId}/{$tipo}/{$nombreArchivo}";
    }

    /**
     * Eliminar imagen
     * 
     * @param int $cotizacionId
     * @param string $tipo
     * @param string $nombreArchivo
     * @return bool
     */
    public function eliminarImagen(int $cotizacionId, string $tipo, string $nombreArchivo): bool
    {
        try {
            $rutaCompleta = public_path("cotizaciones/{$cotizacionId}/{$tipo}/{$nombreArchivo}");
            
            if (File::exists($rutaCompleta)) {
                File::delete($rutaCompleta);
                \Log::info("🗑️ Imagen eliminada", ['ruta' => $rutaCompleta]);
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            \Log::error("❌ Error al eliminar imagen", [
                'error' => $e->getMessage(),
                'ruta' => $rutaCompleta ?? 'desconocida'
            ]);
            return false;
        }
    }

    /**
     * Eliminar carpeta de cotización completa
     * 
     * @param int $cotizacionId
     * @return bool
     */
    public function eliminarCarpetaCotizacion(int $cotizacionId): bool
    {
        try {
            $rutaCarpeta = public_path("cotizaciones/{$cotizacionId}");
            
            if (File::exists($rutaCarpeta)) {
                File::deleteDirectory($rutaCarpeta);
                \Log::info("🗑️ Carpeta de cotización eliminada", ['ruta' => $rutaCarpeta]);
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            \Log::error("❌ Error al eliminar carpeta", [
                'error' => $e->getMessage(),
                'cotizacion_id' => $cotizacionId
            ]);
            return false;
        }
    }
}
