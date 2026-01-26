<?php

namespace App\Domain\Pedidos\Services;

use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

/**
 * @deprecated Este servicio NO usa el sistema centralizado de uploads
 * 
 *  PROBLEMA: Guarda directamente en /prendas/ (carpeta global)
 * USAR EN SU LUGAR: ImageUploadService con sistema temp/{uuid}/{tipo}/
 * 
 * Servicio para gestionar fotos de prendas (OBSOLETO)
 * 
 * Responsabilidades:
 * - Guardar imagen original
 * - Convertir a WebP
 * - Retornar rutas de ambas versiones
 */
class PrendaFotoService
{
    private const STORAGE_PATH = 'prendas'; //  PROBLEMA: Carpeta global
    private const WEBP_QUALITY = 80;

    /**
     * Procesar y guardar foto de prenda
     * 
     * @param UploadedFile $archivo
     * @return array ['ruta_original' => string, 'ruta_webp' => string]
     */
    public function procesarFoto(UploadedFile $archivo): array
    {
        // 1. Guardar imagen original
        $rutaOriginal = $this->guardarOriginal($archivo);

        // 2. Convertir a WebP
        $rutaWebp = $this->convertirAWebp($rutaOriginal);

        return [
            'ruta_original' => $rutaOriginal,
            'ruta_webp' => $rutaWebp,
        ];
    }

    /**
     * Guardar imagen original con nombre Ãºnico
     * 
     * @param UploadedFile $archivo
     * @return string Ruta relativa guardada
     */
    private function guardarOriginal(UploadedFile $archivo): string
    {
        $nombreOriginal = $this->generarNombreUnico($archivo);
        return $archivo->storeAs(self::STORAGE_PATH, $nombreOriginal, 'public');
    }

    /**
     * Convertir imagen a WebP y guardar
     * 
     * @param string $rutaOriginal Ruta relativa de la imagen original
     * @return string Ruta relativa de la imagen WebP
     */
    private function convertirAWebp(string $rutaOriginal): string
    {
        try {
            // Obtener ruta completa
            $rutaCompleta = storage_path('app/public/' . $rutaOriginal);

            // Crear manager de Intervention Image v3
            $manager = new ImageManager(new Driver());
            
            // Cargar imagen
            $imagen = $manager->read($rutaCompleta);

            // Generar nombre para WebP
            $rutaWebp = preg_replace('/\.[^.]+$/', '.webp', $rutaOriginal);
            $rutaCompletaWebp = storage_path('app/public/' . $rutaWebp);

            // Asegurar que la carpeta existe
            $carpeta = dirname($rutaCompletaWebp);
            if (!is_dir($carpeta)) {
                mkdir($carpeta, 0755, true);
            }

            // Guardar como WebP en Intervention Image v3
            $imagen->toWebp(self::WEBP_QUALITY)->save($rutaCompletaWebp);

            \Log::info('[PrendaFotoService] Imagen convertida a WebP', [
                'original' => $rutaOriginal,
                'webp' => $rutaWebp,
            ]);

            return $rutaWebp;

        } catch (\Exception $e) {
            \Log::error('[PrendaFotoService] Error al convertir a WebP', [
                'ruta_original' => $rutaOriginal,
                'error' => $e->getMessage(),
            ]);

            // Si falla la conversión, retornar ruta original como fallback
            return $rutaOriginal;
        }
    }

    /**
     * Generar nombre Ãºnico para archivo
     * 
     * Formato: prendas_TIMESTAMP_RANDOM.ext
     * 
     * @param UploadedFile $archivo
     * @return string
     */
    private function generarNombreUnico(UploadedFile $archivo): string
    {
        $timestamp = now()->format('YmdHis');
        $random = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $extension = $archivo->getClientOriginalExtension();

        return "prenda_{$timestamp}_{$random}.{$extension}";
    }
}

