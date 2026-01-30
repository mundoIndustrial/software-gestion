<?php

namespace App\Domain\Pedidos\Services;

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
                    
                    // 🔄 COPIAR IMAGEN si viene de cotizaciones
                    if (str_contains($rutaAbsoluta, '/storage/cotizaciones/')) {
                        $rutaAbsoluta = $this->copiarImagenDesdeCotizacion($foto, $prendaId, $pedidoId, $index, 'prenda');
                    }
                    
                    DB::table('prenda_fotos_pedido')->insert([
                        'prenda_pedido_id' => $prendaId,
                        'ruta_original' => basename($foto),
                        'ruta_webp' => $rutaAbsoluta,
                        'orden' => $index + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    Log::info(' Foto de prenda guardada (String)', [
                        'prenda_id' => $prendaId,
                        'index' => $index,
                        'ruta_absoluta' => $rutaAbsoluta,
                    ]);
                }
                // CASO 4: Array con string
                elseif (is_array($foto) && isset($foto['ruta'])) {
                    $rutaAbsoluta = $foto['ruta'] && !str_starts_with($foto['ruta'], '/') ? '/' . $foto['ruta'] : $foto['ruta'];
                    
                    // 🔄 COPIAR IMAGEN si viene de cotizaciones
                    if (str_contains($rutaAbsoluta, '/storage/cotizaciones/')) {
                        $rutaAbsoluta = $this->copiarImagenDesdeCotizacion($foto['ruta'], $prendaId, $pedidoId, $index, 'prenda');
                    }
                    
                    DB::table('prenda_fotos_pedido')->insert([
                        'prenda_pedido_id' => $prendaId,
                        'ruta_original' => basename($foto['ruta']),
                        'ruta_webp' => $rutaAbsoluta,
                        'orden' => $index + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    Log::info(' Foto de prenda guardada (Array con ruta)', [
                        'prenda_id' => $prendaId,
                        'index' => $index,
                        'ruta_absoluta' => $rutaAbsoluta,
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
    
    /**
     * Copiar imagen desde cotizaciones a pedidos
     */
    private function copiarImagenDesdeCotizacion(string $rutaOriginal, int $prendaId, int $pedidoId, int $index, string $tipo): string
    {
        try {
            // Convertir ruta de storage a ruta del sistema de archivos
            $rutaSistema = str_replace('/storage/', storage_path('app/public/') . '/', $rutaOriginal);
            
            if (!file_exists($rutaSistema)) {
                Log::warning(' [PrendaImagenService] Archivo original no existe', [
                    'ruta_original' => $rutaOriginal,
                    'ruta_sistema' => $rutaSistema,
                ]);
                return $rutaOriginal; // Devolver ruta original si no existe
            }
            
            // Crear directorio destino
            $directorioDestino = storage_path("app/public/pedidos/{$pedidoId}/prendas");
            if (!is_dir($directorioDestino)) {
                mkdir($directorioDestino, 0755, true);
            }
            
            // Generar nombre de archivo único
            $nombreOriginal = basename($rutaSistema);
            $nombreArchivo = $index . '_' . $nombreOriginal;
            $rutaDestino = $directorioDestino . '/' . $nombreArchivo;
            
            // Copiar archivo
            if (!copy($rutaSistema, $rutaDestino)) {
                Log::error(' [PrendaImagenService] Error copiando archivo', [
                    'ruta_origen' => $rutaSistema,
                    'ruta_destino' => $rutaDestino,
                ]);
                return $rutaOriginal;
            }
            
            // Generar nueva ruta para storage
            $nuevaRuta = "/storage/pedidos/{$pedidoId}/prendas/{$nombreArchivo}";
            
            Log::info(' [PrendaImagenService] Imagen copiada exitosamente', [
                'ruta_original' => $rutaOriginal,
                'nueva_ruta' => $nuevaRuta,
                'prenda_id' => $prendaId,
                'pedido_id' => $pedidoId,
            ]);
            
            return $nuevaRuta;
            
        } catch (\Exception $e) {
            Log::error(' [PrendaImagenService] Excepción copiando imagen', [
                'ruta_original' => $rutaOriginal,
                'error' => $e->getMessage(),
            ]);
            return $rutaOriginal; // Devolver ruta original en caso de error
        }
    }
}

