<?php

namespace App\Domain\PedidoProduccion\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PrendaImagenService
 * 
 * Responsabilidad: Guardar fotos de prendas en la BD
 * Acepta: UploadedFile, strings con rutas, o arrays con ruta_original/ruta_webp
 */
class PrendaImagenService
{
    private ImagenTransformadorService $transformador;

    public function __construct(ImagenTransformadorService $transformador = null)
    {
        $this->transformador = $transformador ?? app(ImagenTransformadorService::class);
    }

    /**
     * Guardar fotos de prenda
     * 
     * SOLO ACEPTA UploadedFile - NO base64, NO archivos en disco
     */
    public function guardarFotosPrenda(int $prendaId, int $pedidoId, array $fotos): void
    {
        Log::info(' [PrendaImagenService::guardarFotosPrenda] Guardando fotos de prenda', [
            'prenda_id' => $prendaId,
            'pedido_id' => $pedidoId,
            'cantidad_fotos' => count($fotos),
        ]);

        foreach ($fotos as $index => $foto) {
            try {
                // CASO 1: UploadedFile directo
                if ($foto instanceof UploadedFile) {
                    $directorio = storage_path("app/public/pedidos/{$pedidoId}/prendas");
                    $resultado = $this->transformador->transformarAWebp($foto, $directorio, $index, 'prenda');
                    $rutaAbsoluta = '/storage/pedidos/' . $pedidoId . '/prendas/' . $resultado['nombreArchivo'];
                    
                    DB::table('prenda_fotos_pedido')->insert([
                        'prenda_pedido_id' => $prendaId,
                        'ruta_original' => $foto->getClientOriginalName(),
                        'ruta_webp' => $rutaAbsoluta,
                        'orden' => $index + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    Log::info(' Foto de prenda guardada (UploadedFile)', [
                        'prenda_id' => $prendaId,
                        'index' => $index,
                        'ruta_absoluta' => $rutaAbsoluta,
                    ]);
                }
                // CASO 2: Array con UploadedFile
                elseif (is_array($foto) && isset($foto['archivo']) && $foto['archivo'] instanceof UploadedFile) {
                    $directorio = storage_path("app/public/pedidos/{$pedidoId}/prendas");
                    $resultado = $this->transformador->transformarAWebp($foto['archivo'], $directorio, $index, 'prenda');
                    $rutaRelativa = "pedidos/{$pedidoId}/prendas/" . $resultado['nombreArchivo'];
                    $rutaAbsoluta = '/' . $rutaRelativa;
                    
                    DB::table('prenda_fotos_pedido')->insert([
                        'prenda_pedido_id' => $prendaId,
                        'ruta_original' => $foto['archivo']->getClientOriginalName(),
                        'ruta_webp' => $rutaAbsoluta,
                        'orden' => $index + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    Log::info(' Foto de prenda guardada (Array con UploadedFile)', [
                        'prenda_id' => $prendaId,
                        'index' => $index,
                        'ruta_absoluta' => $rutaAbsoluta,
                    ]);
                }
                // CASO 3: String (ruta existente)
                elseif (is_string($foto)) {
                    $rutaAbsoluta = $foto && !str_starts_with($foto, '/') ? '/' . $foto : $foto;
                    
                    DB::table('prenda_fotos_pedido')->insert([
                        'prenda_pedido_id' => $prendaId,
                        'ruta_original' => basename($foto),
                        'ruta_webp' => $rutaAbsoluta,
                        'orden' => $index + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error(' Error guardando foto de prenda', [
                    'prenda_id' => $prendaId,
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
