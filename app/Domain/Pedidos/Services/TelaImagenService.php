<?php

namespace App\Domain\Pedidos\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Application\Services\ColorTelaService;

/**
 * TelaImagenService
 * 
 * Responsabilidad: Guardar fotos de telas en la BD
 * Acepta: UploadedFile, strings con rutas, o arrays con ruta_original/ruta_webp
 * Preserva el campo 'observaciones'
 */
class TelaImagenService
{
    private ImagenTransformadorService $transformador;
    private ColorTelaService $colorTelaService;

    public function __construct(ImagenTransformadorService $transformador = null, ColorTelaService $colorTelaService = null)
    {
        $this->transformador = $transformador ?? app(ImagenTransformadorService::class);
        $this->colorTelaService = $colorTelaService ?? app(ColorTelaService::class);
    }

    /**
     * Guardar fotos de telas
     */
    public function guardarFotosTelas(int $prendaId, int $pedidoId, array $telas): void
    {
        Log::info(' [TelaImagenService::guardarFotosTelas] Guardando fotos de telas', [
            'prenda_id' => $prendaId,
            'pedido_id' => $pedidoId,
            'cantidad_telas' => count($telas),
        ]);

        foreach ($telas as $telaIndex => $tela) {
            try {
                // Obtener o crear color_tela_id
                $colorTelaId = $this->obtenerOCrearColorTela($prendaId, $tela);
                
                if (!$colorTelaId) {
                    continue;
                }

                // Procesar fotos de la tela (soportar 'fotos' e 'imagenes')
                $fotos = $tela['fotos'] ?? $tela['imagenes'] ?? [];
                if (empty($fotos)) {
                    continue;
                }

                foreach ($fotos as $fotoIndex => $foto) {
                    try {
                        // CASO 1: UploadedFile directo
                        if ($foto instanceof UploadedFile) {
                            $directorio = storage_path("app/public/pedidos/{$pedidoId}/telas");
                            $resultado = $this->transformador->transformarAWebp($foto, $directorio, $fotoIndex, 'tela');
                            $rutaAbsoluta = '/storage/pedidos/' . $pedidoId . '/telas/' . $resultado['nombreArchivo'];
                            
                            DB::table('prenda_fotos_tela_pedido')->insert([
                                'prenda_pedido_colores_telas_id' => $colorTelaId,
                                'ruta_original' => $foto->getClientOriginalName(),
                                'ruta_webp' => $rutaAbsoluta,
                                'orden' => $fotoIndex + 1,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            
                            Log::info(' Foto de tela guardada (UploadedFile)', [
                                'prenda_id' => $prendaId,
                                'color_tela_id' => $colorTelaId,
                                'ruta_original' => $foto->getClientOriginalName(),
                                'ruta_webp' => $rutaAbsoluta,
                            ]);
                        }
                        // CASO 2: Array con UploadedFile
                        elseif (is_array($foto) && isset($foto['archivo']) && $foto['archivo'] instanceof UploadedFile) {
                            $directorio = storage_path("app/public/pedidos/{$pedidoId}/telas");
                            $resultado = $this->transformador->transformarAWebp($foto['archivo'], $directorio, $fotoIndex, 'tela');
                            $rutaAbsoluta = '/storage/pedidos/' . $pedidoId . '/telas/' . $resultado['nombreArchivo'];
                            
                            DB::table('prenda_fotos_tela_pedido')->insert([
                                'prenda_pedido_colores_telas_id' => $colorTelaId,
                                'ruta_original' => $foto['archivo']->getClientOriginalName(),
                                'ruta_webp' => $rutaAbsoluta,
                                'orden' => $fotoIndex + 1,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            
                            Log::info(' Foto de tela guardada (Array con UploadedFile)', [
                                'prenda_id' => $prendaId,
                                'color_tela_id' => $colorTelaId,
                                'ruta_original' => $foto['archivo']->getClientOriginalName(),
                                'ruta_webp' => $rutaAbsoluta,
                            ]);
                        }
                        // CASO 3: String (ruta existente)
                        elseif (is_string($foto)) {
                            $rutaAbsoluta = $foto && !str_starts_with($foto, '/') ? '/' . $foto : $foto;
                            
                            //  COPIAR IMAGEN si viene de cotizaciones
                            if (str_contains($rutaAbsoluta, '/storage/cotizaciones/')) {
                                $rutaAbsoluta = $this->copiarImagenDesdeCotizacion($foto, $prendaId, $pedidoId, $telaIndex, $fotoIndex, 'tela');
                            }
                            
                            DB::table('prenda_fotos_tela_pedido')->insert([
                                'prenda_pedido_colores_telas_id' => $colorTelaId,
                                'ruta_original' => basename($foto),
                                'ruta_webp' => $rutaAbsoluta,
                                'orden' => $fotoIndex + 1,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error(' Error guardando foto de tela', [
                            'prenda_id' => $prendaId,
                            'tela_index' => $telaIndex,
                            'foto_index' => $fotoIndex,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            } catch (\Exception $e) {
                Log::error(' Error procesando tela', [
                    'prenda_id' => $prendaId,
                    'tela_index' => $telaIndex,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Obtener o crear color_tela_id
     * Acepta tanto IDs como nombres de color y tela
     */
    private function obtenerOCrearColorTela(int $prendaId, array $tela): ?int
    {
        $colorId = $tela['color_id'] ?? null;
        $telaId = $tela['tela_id'] ?? null;

        // Si vienen como nombres (strings), obtener o crear usando el servicio centralizado
        if (!$colorId && !empty($tela['color'])) {
            $colorId = $this->colorTelaService->obtenerOCrearColor($tela['color']);
        }

        if (!$telaId && !empty($tela['tela'])) {
            $telaId = $this->colorTelaService->obtenerOCrearTela($tela['tela']);
        }

        if (!$colorId || !$telaId) {
            Log::warning(' [TelaImagenService] No se pudo obtener color_id o tela_id', [
                'prenda_id' => $prendaId,
                'color_id' => $colorId,
                'tela_id' => $telaId,
                'tela_data' => $tela,
            ]);
            return null;
        }

        // Usar el servicio para obtener o crear la combinación color-tela
        return $this->colorTelaService->obtenerOCrearColorTela($prendaId, $colorId, $telaId);
    }
    
    /**
     * Copiar imagen desde cotizaciones a pedidos
     */
    private function copiarImagenDesdeCotizacion(string $rutaOriginal, int $prendaId, int $pedidoId, int $telaIndex, int $fotoIndex, string $tipo): string
    {
        try {
            // Convertir ruta de storage a ruta del sistema de archivos
            $rutaSistema = str_replace('/storage/', storage_path('app/public/') . '/', $rutaOriginal);
            
            if (!file_exists($rutaSistema)) {
                Log::warning(' [TelaImagenService] Archivo original no existe', [
                    'ruta_original' => $rutaOriginal,
                    'ruta_sistema' => $rutaSistema,
                ]);
                return $rutaOriginal; // Devolver ruta original si no existe
            }
            
            // Crear directorio destino
            $directorioDestino = storage_path("app/public/pedidos/{$pedidoId}/telas");
            if (!is_dir($directorioDestino)) {
                mkdir($directorioDestino, 0755, true);
            }
            
            // Generar nombre de archivo único
            $nombreOriginal = basename($rutaSistema);
            $nombreArchivo = $telaIndex . '_' . $fotoIndex . '_' . $nombreOriginal;
            $rutaDestino = $directorioDestino . '/' . $nombreArchivo;
            
            // Copiar archivo
            if (!copy($rutaSistema, $rutaDestino)) {
                Log::error(' [TelaImagenService] Error copiando archivo', [
                    'ruta_origen' => $rutaSistema,
                    'ruta_destino' => $rutaDestino,
                ]);
                return $rutaOriginal;
            }
            
            // Generar nueva ruta para storage
            $nuevaRuta = "/storage/pedidos/{$pedidoId}/telas/{$nombreArchivo}";
            
            Log::info(' [TelaImagenService] Imagen copiada exitosamente', [
                'ruta_original' => $rutaOriginal,
                'nueva_ruta' => $nuevaRuta,
                'prenda_id' => $prendaId,
                'pedido_id' => $pedidoId,
                'tela_index' => $telaIndex,
                'foto_index' => $fotoIndex,
            ]);
            
            return $nuevaRuta;
            
        } catch (\Exception $e) {
            Log::error(' [TelaImagenService] Excepción copiando imagen', [
                'ruta_original' => $rutaOriginal,
                'error' => $e->getMessage(),
            ]);
            return $rutaOriginal; // Devolver ruta original en caso de error
        }
    }
}

