<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StorageController extends Controller
{
    /**
     * Servir archivo de storage público
     * GET /storage-serve/{path}
     */
    public function serve($path)
    {
        // Sanitizar la ruta para evitar directory traversal
        $path = str_replace('..', '', $path);
        $path = ltrim($path, '/');
        
        // Verificar que el archivo existe en storage/app/public
        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'Archivo no encontrado');
        }
        
        // Obtener el contenido del archivo
        $contents = Storage::disk('public')->get($path);
        
        // Determinar el tipo MIME
        $mimeType = Storage::disk('public')->mimeType($path);
        
        // Retornar el archivo con headers apropiados
        return response($contents, 200)
            ->header('Content-Type', $mimeType)
            ->header('Cache-Control', 'public, max-age=31536000')
            ->header('Content-Disposition', 'inline');
    }
}
