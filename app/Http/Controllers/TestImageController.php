<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Illuminate\Support\Facades\Log;

class TestImageController extends Controller
{
    public function processImage(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|image|max:10240',
            ]);

            $archivo = $request->file('image');
            Log::info('🔍 Test: Archivo recibido', [
                'nombre' => $archivo->getClientOriginalName(),
                'tipo' => $archivo->getMimeType(),
                'tamaño' => $archivo->getSize(),
                'ruta_temporal' => $archivo->getRealPath(),
            ]);

            // Procesar imagen
            try {
                $manager = ImageManager::gd();
                Log::info('✓ ImageManager GD creado');
                
                $imagen = $manager->read($archivo->getRealPath());
                Log::info('✓ Imagen leída exitosamente');

                // Redimensionar si es necesario
                if ($imagen->width() > 2000 || $imagen->height() > 2000) {
                    $imagen->scaleDown(width: 2000, height: 2000);
                    Log::info('✓ Imagen redimensionada');
                }

                // Convertir a WebP
                $webp = $imagen->toWebp(quality: 80);
                $contenidoWebP = $webp->toString();
                $tamaño = strlen($contenidoWebP);
                Log::info('✓ Imagen convertida a WebP', ['tamaño' => $tamaño]);

                // Guardar archivo de prueba
                $directorio = storage_path('app/public/test-images');
                if (!is_dir($directorio)) {
                    mkdir($directorio, 0755, true);
                    Log::info('✓ Directorio creado');
                }

                $nombreArchivo = 'test_' . time() . '.webp';
                $rutaCompleta = $directorio . '/' . $nombreArchivo;
                file_put_contents($rutaCompleta, $contenidoWebP);
                Log::info('✓ Archivo guardado en disco', ['ruta' => $rutaCompleta]);

                // Verificar que se guardó
                if (file_exists($rutaCompleta)) {
                    $tamañoGuardado = filesize($rutaCompleta);
                    Log::info('✓ Archivo verificado en disco', ['tamaño_archivo' => $tamañoGuardado]);

                    $rutaWeb = asset("storage/test-images/{$nombreArchivo}");

                    return response()->json([
                        'success' => true,
                        'ruta_web' => $rutaWeb,
                        'tamaño' => $tamaño,
                        'archivo_guardado' => $rutaCompleta,
                    ]);
                } else {
                    throw new \Exception('El archivo no se guardó correctamente');
                }
            } catch (\Exception $e) {
                Log::error('❌ Error procesando imagen', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('❌ Error en test de imagen', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
