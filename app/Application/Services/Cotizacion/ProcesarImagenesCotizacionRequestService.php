<?php

namespace App\Application\Services\Cotizacion;

use App\Models\Cotizacion;
use App\Models\LogoCotizacion;
use App\Models\LogoCotizacionTecnicaPrenda;
use App\Models\LogoFotoCot;
use App\Models\PrendaCot;
use App\Models\PrendaTelaFotoCot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ProcesarImagenesCotizacionRequestService
{
    public function __construct(
        private readonly \App\Application\Services\ProcesarImagenesCotizacionService $procesarImagenesService,
    ) {
    }

    public function ejecutar(Request $request, int $cotizacionId): void
    {
        try {
            //  OBTENER PRENDAS DESDE FORMDATA (no uses input() para arrays complejos)
            $allData = $request->all();
            $prendas = $allData['prendas'] ?? $request->input('prendas', []);
            $allFiles = $request->allFiles();

            // DETECTAR si es UPDATE o CREATE
            $cotizacionExistente = Cotizacion::find($cotizacionId);
            $esUpdate = !!$cotizacionExistente;

            Log::info('Procesando imágenes de cotización', [
                'cotizacion_id' => $cotizacionId,
                'es_update' => $esUpdate,
                'prendas_count' => is_array($prendas) ? count($prendas) : 0,
                'all_files_keys' => array_keys($allFiles),
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
                $fotosArchivos = [];
                $fotosArchivos = $request->file("prendas.{$index}.fotos") ?? [];

                if (empty($fotosArchivos)) {
                    $fotosArchivos = $request->file("prendas.{$index}.fotos.0") ?? [];
                }

                if (empty($fotosArchivos)) {
                    $allFilesTmp = $request->allFiles();
                    $fotosArchivos = $allFilesTmp["prendas.{$index}.fotos"] ?? [];
                }

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
                $fotosGuardadas = $request->input("prendas.{$index}.fotos_guardadas") ?? [];
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

                // Acceder a la estructura anidada: prendas[index][telas][telaIndex][fotos][]
                $allFilesTmp = $request->allFiles();
                if (isset($allFilesTmp['prendas']) && is_array($allFilesTmp['prendas']) && isset($allFilesTmp['prendas'][$index])) {
                    $prendaFiles = $allFilesTmp['prendas'][$index];

                    if (isset($prendaFiles['telas']) && is_array($prendaFiles['telas'])) {
                        foreach ($prendaFiles['telas'] as $telaIndex => $telaData) {
                            unset($ordenFotosTela);

                            $telaInfo = [];
                            foreach ($telasMultiples as $tm) {
                                if (($tm['indice'] ?? null) === (int) $telaIndex) {
                                    $telaInfo = $tm;
                                    break;
                                }
                            }

                            $fotosTelaExistentes = $request->input("prendas.{$index}.telas.{$telaIndex}.fotos_existentes") ?? ($telaData['fotos_existentes'] ?? []);
                            if (is_string($fotosTelaExistentes)) {
                                $fotosTelaExistentes = json_decode($fotosTelaExistentes, true) ?? [];
                            }
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

                            if (isset($telaData['fotos']) && is_array($telaData['fotos'])) {
                                if (!isset($ordenFotosTela)) {
                                    $ordenFotosTela = (DB::table('prenda_tela_fotos_cot')
                                        ->where('prenda_cot_id', $prendaModel->id)
                                        ->where('tela_index', $telaIndex)
                                        ->max('orden') ?? 0) + 1;
                                }

                                $prendaTelaCotId = $telaCotIds[$telaIndex] ?? null;

                                $fotosArray = $telaData['fotos'];
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
                                            $rutaUrl = Storage::url($rutaGuardada);

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

    private function flatearArchivos(array $archivos, array &$resultado, string $prefijo = ''): void
    {
        foreach ($archivos as $key => $valor) {
            $nuevaLlave = $prefijo . '[' . $key . ']';

            if ($valor instanceof UploadedFile) {
                $resultado[$nuevaLlave] = $valor;
            } elseif (is_array($valor)) {
                $this->flatearArchivos($valor, $resultado, $nuevaLlave);
            }
        }
    }
}
