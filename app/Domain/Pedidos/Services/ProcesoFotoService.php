<?php

namespace App\Domain\Pedidos\Services;

use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

/**
 * ProcesoFotoService - Servicio para procesar y guardar imágenes de procesos
 * 
 * Responsabilidades:
 * - Guardar imagen original de proceso
 * - Convertir a WebP
 * - Guardar en carpeta específica del pedido
 * - Retornar rutas para BD
 */
class ProcesoFotoService
{
    private const WEBP_QUALITY = 80;

    /**
     * Procesar y guardar foto de proceso
     * 
     * @param UploadedFile $archivo
     * @param int|null $pedidoId - Opcional para organizarlas por pedido
     * @return array ['ruta_original' => string, 'ruta_webp' => string]
     */
    public function procesarFoto(UploadedFile $archivo, ?int $pedidoId = null): array
    {
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
        
        // Guardar en carpeta específica si pedidoId existe
        if ($pedidoId) {
            $carpeta = "pedidos/{$pedidoId}/proceso";
        } else {
            $carpeta = "procesos";
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

            // Crear directorio si no existe
            @mkdir(dirname($rutaCompletaWebp), 0755, true);

            // Guardar como WebP
            $imagen->toWebp(self::WEBP_QUALITY)->save($rutaCompletaWebp);

            return $rutaWebp;
        } catch (\Exception $e) {
            \Log::warning('[ProcesoFotoService] Error convertiendo a WebP', [
                'ruta_original' => $rutaOriginal,
                'error' => $e->getMessage(),
            ]);
            
            // Si falla conversión, retornar original
            return $rutaOriginal;
        }
    }

    /**
     * Generar nombre único para archivo
     * 
     * @param UploadedFile $archivo
     * @return string Nombre con timestamp y hash
     */
    private function generarNombreUnico(UploadedFile $archivo): string
    {
        $timestamp = now()->format('YmdHis');
        $hash = substr(md5(uniqid()), 0, 8);
        $extension = $archivo->getClientOriginalExtension();
        
        return "proceso_{$timestamp}_{$hash}.{$extension}";
    }
}
