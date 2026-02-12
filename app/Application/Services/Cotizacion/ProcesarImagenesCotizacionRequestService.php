<?php

namespace App\Application\Services\Cotizacion;

use App\Models\Cotizacion;
use App\Models\PrendaCot;
use App\Models\PrendaTelaFotoCot;
use App\Application\Cotizacion\DTOs\ProcesarImagenesCotizacionDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ProcesarImagenesCotizacionRequestService
{
    public function __construct(
        private readonly \App\Application\Services\ProcesarImagenesCotizacionService $procesarImagenesService,
    ) {
    }

    public function ejecutar(ProcesarImagenesCotizacionDTO $dto): void
    {
        try {
            $cotizacionId = $dto->cotizacionId;
            $prendas = $dto->prendas;

            // DETECTAR si es UPDATE o CREATE
            $cotizacionExistente = Cotizacion::find($cotizacionId);
            $esUpdate = !!$cotizacionExistente;

            Log::info('Procesando imágenes de cotización', [
                'cotizacion_id' => $cotizacionId,
                'es_update' => $esUpdate,
                'prendas_count' => is_array($prendas) ? count($prendas) : 0,
                'all_files_keys' => [],
            ]);

            foreach ($prendas as $index => $prenda) {
                // Obtener la prenda guardada
                $prendaModel = PrendaCot::where('cotizacion_id', $cotizacionId)
                    ->skip($index)
                    ->first();

                if (!$prendaModel) {
                    continue;
                }

                $ordenFotosPrenda = ($prendaModel->fotos()->max('orden') ?? 0) + 1;

                // Procesar imágenes de prenda
                $fotosArchivos = $dto->prendaFotosArchivosPorIndex[$index] ?? [];

                if ($fotosArchivos instanceof \Illuminate\Http\UploadedFile) {
                    $fotosArchivos = [$fotosArchivos];
                } elseif (!is_array($fotosArchivos)) {
                    $fotosArchivos = [];
                }

                if (!empty($fotosArchivos)) {
                    $orden = $ordenFotosPrenda;
                    foreach ($fotosArchivos as $foto) {
                        if ($foto instanceof \Illuminate\Http\UploadedFile) {
                            $ruta = $this->procesarImagenesService->procesarImagenPrenda(
                                $foto,
                                $cotizacionId,
                                $prendaModel->id
                            );

                            $prendaModel->fotos()->create([
                                'ruta_original' => $ruta,
                                'ruta_webp' => $ruta,
                                'orden' => $orden,
                            ]);
                            $orden++;
                        }
                    }
                }

                // Procesar fotos guardadas (rutas desde el frontend)
                $fotosGuardadas = $dto->prendaFotosGuardadasPorIndex[$index] ?? [];
                if (!is_array($fotosGuardadas)) {
                    $fotosGuardadas = [];
                }

                if (!empty($fotosGuardadas)) {
                    $orden = max($ordenFotosPrenda, count($fotosArchivos) + $ordenFotosPrenda);
                    foreach ($fotosGuardadas as $rutaGuardada) {
                        if ($rutaGuardada && is_string($rutaGuardada)) {
                            $rutaLimpia = $rutaGuardada;
                            if (strpos($rutaLimpia, '/storage/') === 0) {
                                $rutaLimpia = substr($rutaLimpia, 9);
                            }

                            $prendaModel->fotos()->create([
                                'ruta_original' => $rutaLimpia,
                                'ruta_webp' => $rutaLimpia,
                                'orden' => $orden,
                            ]);
                            $orden++;
                        }
                    }
                }

                // Procesar imágenes de telas - NUEVA LÓGICA
                $prendaModel->refresh();
                $variante = $prendaModel->variantes()->first();
                $telasMultiples = [];
                if ($variante && $variante->telas_multiples) {
                    $telasMultiples = is_array($variante->telas_multiples)
                        ? $variante->telas_multiples
                        : json_decode($variante->telas_multiples, true);
                }

                // Mapeo de indice de tela => prenda_tela_cot_id
                $telaCotIds = [];

                foreach ($telasMultiples as $telaInfo) {
                    $colorId = null;
                    if (!empty($telaInfo['color'])) {
                        $color = DB::table('colores_prenda')
                            ->where('nombre', $telaInfo['color'])
                            ->first();

                        if (!$color) {
                            $colorId = DB::table('colores_prenda')->insertGetId([
                                'nombre' => $telaInfo['color'],
                                'activo' => true,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        } else {
                            $colorId = $color->id;
                        }
                    }

                    $telaId = null;
                    if (!empty($telaInfo['tela'])) {
                        $tela = DB::table('telas_prenda')
                            ->where('nombre', trim($telaInfo['tela']))
                            ->first();

                        if (!$tela) {
                            $telaId = DB::table('telas_prenda')->insertGetId([
                                'nombre' => trim($telaInfo['tela']),
                                'referencia' => $telaInfo['referencia'] ?? null,
                                'activo' => true,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        } else {
                            $telaId = $tela->id;
                        }
                    }

                    if ($colorId && $telaId && $variante) {
                        $existente = DB::table('prenda_telas_cot')
                            ->where('prenda_cot_id', $prendaModel->id)
                            ->where('variante_prenda_cot_id', $variante->id)
                            ->where('color_id', $colorId)
                            ->where('tela_id', $telaId)
                            ->first();

                        if (!$existente) {
                            $prendaTelaCotId = DB::table('prenda_telas_cot')->insertGetId([
                                'prenda_cot_id' => $prendaModel->id,
                                'variante_prenda_cot_id' => $variante->id,
                                'color_id' => $colorId,
                                'tela_id' => $telaId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                            $telaIndex = $telaInfo['indice'] ?? null;
                            if ($telaIndex !== null) {
                                $telaCotIds[$telaIndex] = $prendaTelaCotId;
                            }
                        } else {
                            $telaIndex = $telaInfo['indice'] ?? null;
                            if ($telaIndex !== null) {
                                $telaCotIds[$telaIndex] = $existente->id;
                            }
                        }
                    }
                }

                // Procesar fotos de telas a partir del DTO
                $telasFiles = $dto->telasArchivosPorPrendaIndex[$index] ?? [];
                if (is_array($telasFiles)) {
                    foreach ($telasFiles as $telaIndex => $fotosArray) {
                        unset($ordenFotosTela);

                            $telaInfo = [];
                            foreach ($telasMultiples as $tm) {
                                if (($tm['indice'] ?? null) === (int) $telaIndex) {
                                    $telaInfo = $tm;
                                    break;
                                }
                            }

                        $fotosTelaExistentes = $dto->telasFotosExistentesPorPrendaIndex[$index][$telaIndex] ?? [];
                        if (!is_array($fotosTelaExistentes)) {
                            $fotosTelaExistentes = [];
                        }

                            if (!empty($fotosTelaExistentes) && !$esUpdate) {
                                $ordenFotosTela = (DB::table('prenda_tela_fotos_cot')
                                    ->where('prenda_cot_id', $prendaModel->id)
                                    ->where('tela_index', $telaIndex)
                                    ->max('orden') ?? 0) + 1;

                                foreach ($fotosTelaExistentes as $fotoId) {
                                    $fotoExistente = PrendaTelaFotoCot::find($fotoId);
                                    if ($fotoExistente) {
                                        $prendaTelaCotId = $telaCotIds[$telaIndex] ?? null;
                                        DB::table('prenda_tela_fotos_cot')->insert([
                                            'prenda_cot_id' => $prendaModel->id,
                                            'prenda_tela_cot_id' => $prendaTelaCotId,
                                            'tela_index' => $telaIndex,
                                            'ruta_original' => $fotoExistente->ruta_original,
                                            'ruta_webp' => $fotoExistente->ruta_webp,
                                            'ruta_miniatura' => $fotoExistente->ruta_miniatura,
                                            'orden' => $ordenFotosTela,
                                            'ancho' => $fotoExistente->ancho,
                                            'alto' => $fotoExistente->alto,
                                            'tamaño' => $fotoExistente->tamaño,
                                            'created_at' => now(),
                                            'updated_at' => now(),
                                        ]);
                                        $ordenFotosTela++;
                                    }
                                }
                            }

                        if (!isset($ordenFotosTela)) {
                            $ordenFotosTela = (DB::table('prenda_tela_fotos_cot')
                                ->where('prenda_cot_id', $prendaModel->id)
                                ->where('tela_index', $telaIndex)
                                ->max('orden') ?? 0) + 1;
                        }

                        $prendaTelaCotId = $telaCotIds[$telaIndex] ?? null;

                        if ($fotosArray instanceof \Illuminate\Http\UploadedFile) {
                            $fotosArray = [$fotosArray];
                        }

                        if (!is_array($fotosArray)) {
                            continue;
                        }

                        foreach ($fotosArray as $archivoFoto) {
                            if ($archivoFoto && $archivoFoto instanceof \Illuminate\Http\UploadedFile && $archivoFoto->isValid()) {
                                try {
                                    $rutaGuardada = $this->procesarImagenesService->procesarImagenTela(
                                        $archivoFoto,
                                        $cotizacionId,
                                        $prendaModel->id
                                    );

                                    DB::table('prenda_tela_fotos_cot')->insert([
                                        'prenda_cot_id' => $prendaModel->id,
                                        'prenda_tela_cot_id' => $prendaTelaCotId,
                                        'tela_index' => $telaIndex,
                                        'ruta_original' => null,
                                        'ruta_webp' => $rutaGuardada,
                                        'ruta_miniatura' => null,
                                        'orden' => $ordenFotosTela,
                                        'ancho' => null,
                                        'alto' => null,
                                        'tamaño' => $archivoFoto->getSize(),
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);

                                    $ordenFotosTela++;
                                } catch (\Exception $e) {
                                    Log::error(' Error guardando foto de tela', [
                                        'error' => $e->getMessage(),
                                        'archivo' => $archivoFoto->getClientOriginalName(),
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

            // (Logo + Técnicas + Paso 3) y fallback de fotos existentes permanecen en el controller por ahora.
            // Este service ya cubre el procesamiento de fotos de prenda y telas (lo más crítico del paso 2).
        } catch (\Exception $e) {
            Log::error('ProcesarImagenesCotizacionRequestService: Error', [
                'cotizacion_id' => $cotizacionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    // DTO-based: flatearArchivos ya no se usa aquí, quedó en el mapper del borde.
}
