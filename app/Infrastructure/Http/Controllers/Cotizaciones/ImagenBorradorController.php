<?php

namespace App\Infrastructure\Http\Controllers\Cotizaciones;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\PrendaFotoCot;
use App\Models\PrendaTelaFotoCot;
use App\Models\LogoFotoCot;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * ImagenBorradorController
 * 
 * Gestiona la eliminación de imágenes en borradores de cotizaciones
 */
class ImagenBorradorController
{
    /**
     * Borrar imagen de prenda
     * DELETE /cotizaciones/imagenes/prenda/{id}
     */
    public function borrarPrenda($id): JsonResponse
    {
        try {
            $foto = PrendaFotoCot::findOrFail($id);
            
            // Obtener la ruta de archivo
            $rutaArchivo = str_replace('/storage/', '', $foto->ruta_webp);
            
            Log::info('ImagenBorradorController: Borrando imagen de prenda', [
                'foto_id' => $id,
                'ruta' => $rutaArchivo,
            ]);
            
            // Borrar archivo del storage
            if (Storage::disk('public')->exists($rutaArchivo)) {
                Storage::disk('public')->delete($rutaArchivo);
            }
            
            // Borrar registro de BD
            $foto->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Imagen de prenda eliminada',
            ]);
            
        } catch (\Exception $e) {
            Log::error('ImagenBorradorController: Error borrando imagen de prenda', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar imagen: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Borrar imagen de tela
     * DELETE /cotizaciones/imagenes/tela/{id}
     */
    public function borrarTela($id): JsonResponse
    {
        try {
            $foto = PrendaTelaFotoCot::findOrFail($id);
            
            // Obtener la ruta de archivo
            $rutaArchivo = str_replace('/storage/', '', $foto->ruta_webp);
            
            Log::info('ImagenBorradorController: Borrando imagen de tela', [
                'foto_id' => $id,
                'ruta' => $rutaArchivo,
            ]);
            
            // Borrar archivo del storage
            if (Storage::disk('public')->exists($rutaArchivo)) {
                Storage::disk('public')->delete($rutaArchivo);
            }
            
            // Borrar registro de BD
            $foto->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Imagen de tela eliminada',
            ]);
            
        } catch (\Exception $e) {
            Log::error('ImagenBorradorController: Error borrando imagen de tela', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar imagen: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Borrar imagen de logo
     * DELETE /cotizaciones/imagenes/logo/{id}
     */
    public function borrarLogo($id): JsonResponse
    {
        try {
            $foto = LogoFotoCot::findOrFail($id);
            
            // Obtener la ruta de archivo
            $rutaArchivo = str_replace('/storage/', '', $foto->ruta_webp);
            
            Log::info('ImagenBorradorController: Borrando imagen de logo', [
                'foto_id' => $id,
                'ruta' => $rutaArchivo,
            ]);
            
            // Borrar archivo del storage
            if (Storage::disk('public')->exists($rutaArchivo)) {
                Storage::disk('public')->delete($rutaArchivo);
            }
            
            // Borrar registro de BD
            $foto->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Imagen de logo eliminada',
            ]);
            
        } catch (\Exception $e) {
            Log::error('ImagenBorradorController: Error borrando imagen de logo', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar imagen: ' . $e->getMessage(),
            ], 500);
        }
    }
}
