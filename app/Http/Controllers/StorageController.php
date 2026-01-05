<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StorageController extends Controller
{
    /**
     * Servir archivo de storage público con conversión automática de extensiones
     * GET /storage-serve/{path}
     * 
     * Si se solicita .png pero existe .webp, sirve .webp
     * Esto resuelve problemas donde las rutas en BD tienen .png pero archivos están en .webp
     */
    public function serve($path)
    {
        // Sanitizar la ruta para evitar directory traversal
        $path = str_replace('..', '', $path);
        $path = ltrim($path, '/');
        
        // Intentar servir el archivo como está primero
        if (Storage::disk('public')->exists($path)) {
            $contents = Storage::disk('public')->get($path);
            $mimeType = Storage::disk('public')->mimeType($path);
            
            return response($contents, 200)
                ->header('Content-Type', $mimeType)
                ->header('Cache-Control', 'public, max-age=31536000')
                ->header('Content-Disposition', 'inline');
        }
        
        // Si no existe y tiene extensión .png, intentar .webp
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
        
        // Si no existe y tiene extensión .jpg/.jpeg, intentar .webp
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
        
        abort(404, 'Archivo no encontrado');
    }
}
