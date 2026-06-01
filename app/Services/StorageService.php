<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * StorageService
 * 
 * Servicio centralizado para servir archivos con fallback de extensiones
 * Maneja la conversión automática a webp cuando sea disponible
 */
class StorageService
{
    private const ALLOWED_TYPES = ['cotizaciones', 'prendas', 'pedidos', 'firmas'];
    private const CACHE_DURATION = 31536000; // 1 año en segundos
    
    /**
     * Servir archivo con fallback de extensiones
     * 
     * @param string $tipo Tipo de almacenamiento (cotizaciones, prendas, pedidos)
     * @param string $path Ruta del archivo dentro del tipo
     * @return Response
     */
    public function serve(string $tipo, string $path): Response
    {
        // Validar tipo permitido
        if (!in_array($tipo, self::ALLOWED_TYPES)) {
            abort(404, 'Tipo de almacenamiento no válido');
        }
        
        $disk = Storage::disk('public');
        $fullPath = $tipo . '/' . $path;
        
        // 1. Intentar servir el archivo tal cual
        if ($disk->exists($fullPath)) {
            return $this->createResponse(
                $disk->get($fullPath),
                Storage::mimeType($fullPath)
            );
        }
        
        // 2. Si no existe y termina en .png, intentar .webp
        if (str_ends_with($fullPath, '.png')) {
            $pathWebp = substr($fullPath, 0, -4) . '.webp';
            if ($disk->exists($pathWebp)) {
                return $this->createResponse(
                    $disk->get($pathWebp),
                    'image/webp'
                );
            }
        }
        
        // 3. Si no existe y termina en .jpg/.jpeg, intentar .webp
        if (str_ends_with($fullPath, '.jpg') || str_ends_with($fullPath, '.jpeg')) {
            $pathWebp = preg_replace('/\.(jpg|jpeg)$/i', '.webp', $fullPath);
            if ($disk->exists($pathWebp)) {
                return $this->createResponse(
                    $disk->get($pathWebp),
                    'image/webp'
                );
            }
        }
        
        // 4. Si no existe en ningún formato, devolver 404
        abort(404, 'Imagen no encontrada');
    }
    
    /**
     * Crear respuesta HTTP con headers de cache
     * 
     * @param string $contents Contenido del archivo
     * @param string $mimeType Tipo MIME del archivo
     * @return Response
     */
    private function createResponse(string $contents, string $mimeType): Response
    {
        return response($contents, 200)
            ->header('Content-Type', $mimeType)
            ->header('Cache-Control', "public, max-age=" . self::CACHE_DURATION)
            ->header('Content-Disposition', 'inline');
    }
}
