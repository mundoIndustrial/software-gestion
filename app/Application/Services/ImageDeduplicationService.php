<?php

namespace App\Application\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * ImageDeduplicationService
 * 
 * Evita guardar imágenes duplicadas
 * 
 * ESTRATEGIA:
 * 1. Calcular hash MD5 del contenido del archivo
 * 2. Buscar si ese hash ya existe en BD
 * 3. Si existe → reutilizar ruta existente (NO guardar nuevamente)
 * 4. Si no existe → guardar normalmente
 * 
 * VENTAJA: Ahorro de espacio + velocidad
 */
class ImageDeduplicationService
{
    /**
     * Guardar archivo con deduplicación
     * 
     * @param UploadedFile $file
     * @param string $carpeta Ej: pedidos/2723/prendas
     * @param callable $onReutilizado Callback si se reutilizó imagen existente
     * 
     * @return array ['ruta' => '...', 'duplicado' => false/true, 'hash' => '...']
     */
    public function guardarConDeduplicacion(
        UploadedFile $file,
        string $carpeta,
        ?callable $onReutilizado = null
    ): array {
        try {
            // 1. Calcular hash del contenido
            $contenido = file_get_contents($file->getRealPath());
            $hash = md5($contenido);
            
            Log::info('[ImageDeduplicationService] Hash calculado', [
                'archivo' => $file->getClientOriginalName(),
                'hash' => $hash,
                'size' => $file->getSize()
            ]);
            
            // 2. Buscar si ese hash ya existe en BD
            $fotoExistente = \App\Models\PrendaFotoPedido::where('hash_contenido', $hash)->first()
                ?? \App\Models\PrendaFotoTelaPedido::where('hash_contenido', $hash)->first()
                ?? \App\Models\ProcesoPrendaFoto::where('hash_contenido', $hash)->first();
            
            if ($fotoExistente) {
                // Reutilizar ruta existente
                Log::info('[ImageDeduplicationService] Imagen duplicada encontrada, reutilizando', [
                    'hash' => $hash,
                    'ruta_existente' => $fotoExistente->ruta_original,
                    'archivo_nuevo' => $file->getClientOriginalName()
                ]);
                
                if ($onReutilizado) {
                    $onReutilizado($fotoExistente->ruta_original, $fotoExistente->ruta_webp ?? null);
                }
                
                return [
                    'ruta' => $fotoExistente->ruta_original,
                    'ruta_webp' => $fotoExistente->ruta_webp,
                    'duplicado' => true,
                    'hash' => $hash
                ];
            }
            
            // 3. Si no existe, guardar normalmente
            // (El que llame este servicio debe continuar con el guardado)
            
            return [
                'ruta' => null,
                'ruta_webp' => null,
                'duplicado' => false,
                'hash' => $hash
            ];
            
        } catch (\Exception $e) {
            Log::error('[ImageDeduplicationService] Error en deduplicación', [
                'error' => $e->getMessage(),
                'archivo' => $file->getClientOriginalName()
            ]);
            
            return [
                'ruta' => null,
                'ruta_webp' => null,
                'duplicado' => false,
                'hash' => null
            ];
        }
    }
    
    /**
     * Registrar hash de una imagen después de guardarla
     * 
     * @param string $rutaOriginal
     * @param string $rutaWebp
     * @param string $hash
     */
    public function registrarHash(string $rutaOriginal, ?string $rutaWebp = null, string $hash = ''): void
    {
        if (!$hash) {
            try {
                $archivo = Storage::disk('public')->path($rutaOriginal);
                $hash = md5_file($archivo);
            } catch (\Exception $e) {
                Log::warning('[ImageDeduplicationService] No se pudo calcular hash para ruta: ' . $rutaOriginal);
                return;
            }
        }
        
        Log::debug('[ImageDeduplicationService] Hash registrado', [
            'ruta_original' => $rutaOriginal,
            'ruta_webp' => $rutaWebp,
            'hash' => $hash
        ]);
        
        // Nota: Las tablas PrendaFotoPedido, etc, deben tener columna hash_contenido
    }
}
