<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

/**
 * Servicio para procesar imágenes (Base64 -> WebP)
 * 
 * Responsabilidades:
 * - Convertir Base64 a imagen WebP
 * - Guardar en storage/cotizaciones
 * - Retornar rutas públicas
 */
class ImagenProcesadorService
{
    private ImageManager $imageManager;
    
    public function __construct()
    {
        // Intervention Image v3: usar make() con driver automático
        $this->imageManager = ImageManager::gd();
    }
    
    /**
     * Procesar imagen Base64 y guardarla como WebP
     * 
     * @param array $imagenData Array con 'nombre', 'base64', 'tipo', 'size'
     * @param string $tipo 'prenda' o 'tela'
     * @param int $prendaId ID de la prenda
     * @return string Ruta pública de la imagen
     */
    public function procesarImagenBase64(array $imagenData, string $tipo, int $prendaId): string
    {
        try {
            \Log::info('📸 Procesando imagen Base64', [
                'nombre' => $imagenData['nombre'] ?? 'unknown',
                'tipo' => $tipo,
                'prenda_id' => $prendaId,
                'size_kb' => ($imagenData['size'] ?? 0) / 1024
            ]);
            
            // Extraer base64 del data URL
            $base64 = $imagenData['base64'];
            if (strpos($base64, 'base64,') !== false) {
                $base64 = explode('base64,', $base64)[1];
            }
            
            // Decodificar
            $imagenBinaria = base64_decode($base64);
            if ($imagenBinaria === false) {
                throw new \Exception('No se pudo decodificar la imagen Base64');
            }
            
            \Log::info('✓ Base64 decodificado correctamente', [
                'bytes' => strlen($imagenBinaria)
            ]);
            
            // Crear imagen con Intervention
            $image = $this->imageManager->read($imagenBinaria);
            
            // Información de la imagen
            $ancho = $image->width();
            $alto = $image->height();
            \Log::info('✓ Imagen leída', [
                'ancho' => $ancho,
                'alto' => $alto,
                'formato' => $image->origin()
            ]);
            
            // Redimensionar si es muy grande (máximo 2000x2000)
            if ($ancho > 2000 || $alto > 2000) {
                $image->scaleDown(2000, 2000);
                \Log::info('✓ Imagen redimensionada', [
                    'nuevo_ancho' => $image->width(),
                    'nuevo_alto' => $image->height()
                ]);
            }
            
            // Generar nombre único
            $nombreOriginal = pathinfo($imagenData['nombre'] ?? 'imagen', PATHINFO_FILENAME);
            $nombreUnico = $this->generarNombreUnico($nombreOriginal, $tipo, $prendaId);
            
            // Ruta de almacenamiento
            $rutaRelativa = "cotizaciones/{$prendaId}/{$tipo}/{$nombreUnico}.webp";
            
            // Crear directorio si no existe
            $directorio = dirname($rutaRelativa);
            if (!Storage::disk('public')->exists($directorio)) {
                Storage::disk('public')->makeDirectory($directorio, 0755, true);
            }
            
            \Log::info('✓ Directorio asegurado', [
                'directorio' => $directorio,
                'existe' => Storage::disk('public')->exists($directorio)
            ]);
            
            // Convertir a WebP y guardar
            $contenidoWebP = $image->toWebp(85);
            Storage::disk('public')->put($rutaRelativa, $contenidoWebP);
            
            \Log::info('✅ Imagen guardada como WebP', [
                'ruta' => $rutaRelativa,
                'existe' => Storage::disk('public')->exists($rutaRelativa),
                'size' => Storage::disk('public')->size($rutaRelativa)
            ]);
            
            // Retornar ruta relativa (sin URL completa para portabilidad)
            return "storage/{$rutaRelativa}";
            
        } catch (\Exception $e) {
            \Log::error('❌ Error al procesar imagen', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Procesar múltiples imágenes Base64
     * 
     * @param array $imagenesData Array de imágenes
     * @param string $tipo 'prenda' o 'tela'
     * @param int $prendaId ID de la prenda
     * @return array URLs públicas
     */
    public function procesarMultiplesImagenes(array $imagenesData, string $tipo, int $prendaId): array
    {
        $urls = [];
        
        foreach ($imagenesData as $index => $imagenData) {
            try {
                $url = $this->procesarImagenBase64($imagenData, $tipo, $prendaId);
                $urls[] = $url;
                \Log::info("✓ Imagen {$index} procesada", ['url' => $url]);
            } catch (\Exception $e) {
                \Log::error("❌ Error procesando imagen {$index}", [
                    'error' => $e->getMessage()
                ]);
                // Continuar con la siguiente imagen
            }
        }
        
        return $urls;
    }
    
    /**
     * Generar nombre único para la imagen
     */
    private function generarNombreUnico(string $nombreOriginal, string $tipo, int $prendaId): string
    {
        $timestamp = now()->getTimestamp();
        $random = rand(1000, 9999);
        
        // Sanitizar nombre original
        $nombreLimpio = preg_replace('/[^a-zA-Z0-9_-]/', '', 
            str_replace(' ', '_', $nombreOriginal)
        );
        
        if (strlen($nombreLimpio) > 30) {
            $nombreLimpio = substr($nombreLimpio, 0, 30);
        }
        
        return "{$tipo}_{$prendaId}_{$nombreLimpio}_{$timestamp}_{$random}";
    }
}
