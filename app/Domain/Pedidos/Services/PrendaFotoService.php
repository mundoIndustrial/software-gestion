<?php

namespace App\Domain\Pedidos\Services;

use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

/**
 * Servicio para gestionar fotos de prendas
 * 
 * Responsabilidades:
 * - Guardar imagen original en carpeta por pedido
 * - Convertir a WebP
 * - Retornar rutas de ambas versiones
 */
class PrendaFotoService
{
    private const WEBP_QUALITY = 80;

    /**
     * Procesar y guardar foto de prenda
     * 
     * @param UploadedFile $archivo
     * @param int|null $pedidoId - ID del pedido para organizar en carpetas
     * @param bool $soloWebp - Si true, solo guarda WebP (modo edición). Si false, guarda original + WebP
     * @return array ['ruta_original' => string, 'ruta_webp' => string]
     */
    public function procesarFoto(UploadedFile $archivo, ?int $pedidoId = null, bool $soloWebp = true): array
    {
        // 🔴 CRÍTICO: En modo edición ($soloWebp=true), SOLO guardar WebP, nunca PNG
        if ($soloWebp) {
            // Guardar directamente como WebP sin guardar original
            $rutaWebp = $this->guardarDirectoWebp($archivo, $pedidoId);
            return [
                'ruta_original' => $rutaWebp,  // Retornar WebP como original también
                'ruta_webp' => $rutaWebp,
            ];
        }
        
        // Modo antiguo: guardar original + WebP
        // 1. Guardar imagen original
        $rutaOriginal = $this->guardarOriginal($archivo, $pedidoId);

        // 2. Convertir a WebP
        $rutaWebp = $this->convertirAWebp($rutaOriginal);

        return [
            'ruta_original' => $rutaOriginal,
            'ruta_webp' => $rutaWebp,
        ];
    }

    /**
     * Guardar imagen original con nombre único
     * 
     * @param UploadedFile $archivo
     * @param int|null $pedidoId
     * @return string Ruta relativa guardada
     */
    private function guardarOriginal(UploadedFile $archivo, ?int $pedidoId = null): string
    {
        $nombreOriginal = $this->generarNombreUnico($archivo);
        
        // Guardar en carpeta específica del pedido si existe
        if ($pedidoId) {
            $carpeta = "pedidos/{$pedidoId}/prendas";
        } else {
            $carpeta = "prendas";
        }
        
        return $archivo->storeAs($carpeta, $nombreOriginal, 'public');
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
     * 🔴 NUEVO: Guardar imagen directamente como WebP sin guardar PNG original
     * Usado en modo edición para ahorrar espacio
     * 
     * @param UploadedFile $archivo
     * @param int|null $pedidoId
     * @return string Ruta relativa del archivo WebP
     */
    private function guardarDirectoWebp(UploadedFile $archivo, ?int $pedidoId = null): string
    {
        try {
            // Crear manager de Intervention Image v3
            $manager = new ImageManager(new Driver());
            
            // Cargar imagen desde el archivo subido
            $imagen = $manager->read($archivo->get());

            // Generar nombre único para WebP
            $timestamp = now()->format('YmdHis');
            $random = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $nombreWebp = "prenda_{$timestamp}_{$random}.webp";
            
            // Determinar carpeta
            if ($pedidoId) {
                $carpeta = "pedidos/{$pedidoId}/prendas";
            } else {
                $carpeta = "prendas";
            }
            
            // Ruta completa donde guardar
            $rutaCompletaWebp = storage_path('app/public/' . $carpeta . '/' . $nombreWebp);
            
            // Crear directorio si no existe
            @mkdir(dirname($rutaCompletaWebp), 0755, true);

            // Guardar directamente como WebP
            $imagen->toWebp(self::WEBP_QUALITY)->save($rutaCompletaWebp);
            
            \Log::info('[PrendaFotoService] Imagen guardada directamente como WebP', [
                'archivo' => $archivo->getClientOriginalName(),
                'webp' => $carpeta . '/' . $nombreWebp,
            ]);
            
            // Retornar ruta relativa
            return $carpeta . '/' . $nombreWebp;
        } catch (\Exception $e) {
            \Log::error('[PrendaFotoService] Error guardando WebP directo', [
                'archivo' => $archivo->getClientOriginalName(),
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Generar nombre único para archivo
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

