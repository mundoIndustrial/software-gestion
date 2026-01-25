<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HandleStorageImages
{
    /**
     * Middleware para manejar solicitudes de imágenes de storage
     * Convierte automáticamente .png a .webp cuando sea necesario
     */
    public function handle(Request $request, Closure $next)
    {
        // Solo interceptar rutas que comienzan con /storage/
        if (!str_starts_with($request->path(), 'storage/')) {
            return $next($request);
        }

        // Obtener la ruta solicitada
        $path = $request->path();
        
        // Intentar obtener el archivo directamente
        if (Storage::disk('public')->exists($path)) {
            return $next($request);
        }

        // Si no existe y es .png, intentar .webp
        if (str_ends_with($path, '.png')) {
            $pathWebp = substr($path, 0, -4) . '.webp';
            
            if (Storage::disk('public')->exists($pathWebp)) {
                $contents = Storage::disk('public')->get($pathWebp);
                
                return response($contents, 200)
                    ->header('Content-Type', 'image/webp')
                    ->header('Cache-Control', 'public, max-age=31536000')
                    ->header('Content-Disposition', 'inline');
            }
        }

        // Si no existe y es .jpg/.jpeg, intentar .webp
        if (str_ends_with($path, '.jpg') || str_ends_with($path, '.jpeg')) {
            $pathWebp = preg_replace('/\.(jpg|jpeg)$/i', '.webp', $path);
            
            if (Storage::disk('public')->exists($pathWebp)) {
                $contents = Storage::disk('public')->get($pathWebp);
                
                return response($contents, 200)
                    ->header('Content-Type', 'image/webp')
                    ->header('Cache-Control', 'public, max-age=31536000')
                    ->header('Content-Disposition', 'inline');
            }
        }

        // Si no existe en ninguno de los formatos, continuar con la siguiente request
        return $next($request);
    }
}
