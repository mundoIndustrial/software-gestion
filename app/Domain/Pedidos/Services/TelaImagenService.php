<?php

namespace App\Domain\Pedidos\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    public function __construct(ImagenTransformadorService $transformador = null)
    {
        $this->transformador = $transformador ?? app(ImagenTransformadorService::class);
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

                // Procesar fotos de la tela
                $fotos = $tela['fotos'] ?? [];
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
        $referencia = $tela['referencia'] ?? '';

        // Si vienen como nombres (strings), buscar o crear los IDs
        if (!$colorId && !empty($tela['color'])) {
            $colorNombre = $tela['color'];
            $colorObj = DB::table('colores_prenda')
                ->where('nombre', $colorNombre)
                ->first();
            
            if ($colorObj) {
                $colorId = $colorObj->id;
            } else {
                $colorId = DB::table('colores_prenda')->insertGetId([
                    'nombre' => $colorNombre,
                    'activo' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if (!$telaId && !empty($tela['tela'])) {
            $telaNombre = $tela['tela'];
            $telaObj = DB::table('telas_prenda')
                ->where('nombre', $telaNombre)
                ->first();
            
            if ($telaObj) {
                $telaId = $telaObj->id;
            } else {
                $telaId = DB::table('telas_prenda')->insertGetId([
                    'nombre' => $telaNombre,
                    'referencia' => $referencia,
                    'activo' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
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

        // Buscar si ya existe
        $colorTela = DB::table('prenda_pedido_colores_telas')
            ->where('prenda_pedido_id', $prendaId)
            ->where('color_id', $colorId)
            ->where('tela_id', $telaId)
            ->first();

        if ($colorTela) {
            return $colorTela->id;
        }

        // Crear nuevo
        return DB::table('prenda_pedido_colores_telas')->insertGetId([
            'prenda_pedido_id' => $prendaId,
            'color_id' => $colorId,
            'tela_id' => $telaId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

