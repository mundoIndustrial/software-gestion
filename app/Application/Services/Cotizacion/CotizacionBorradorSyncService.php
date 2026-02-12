<?php

namespace App\Application\Services\Cotizacion;

use App\Models\Cotizacion;
use App\Models\PrendaCot;
use App\Models\PrendaTelaCot;
use App\Models\PrendaVarianteCot;

final class CotizacionBorradorSyncService
{
    public function sincronizarPrendasCotizacion(Cotizacion $cotizacion, array $prendasRecibidas): void
    {
        // IMPORTANTE: no sincronizar ni eliminar prendas que están asociadas a técnicas/logo (Paso 3).
        // Si se eliminan estas prendas se provoca borrado en cascada (FK) de logo_cotizacion_tecnica_prendas.
        $prendasExistentes = PrendaCot::where('cotizacion_id', $cotizacion->id)
            ->whereDoesntHave('logoCotizacionesTecnicas')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->values();

        $idsConservar = [];

        foreach ($prendasRecibidas as $index => $prendaData) {
            if (is_string($prendaData)) {
                $prendaData = json_decode($prendaData, true) ?? [];
            }
            if (!is_array($prendaData)) {
                continue;
            }

            $prendaModel = $prendasExistentes->get($index);

            $variantes = $prendaData['variantes'] ?? [];
            if (is_string($variantes)) {
                $variantes = json_decode($variantes, true) ?? [];
            }

            $prendaBodega = false;
            if (is_array($variantes)) {
                $valorBodega = $variantes['prenda_bodega'] ?? false;
                if (is_bool($valorBodega)) {
                    $prendaBodega = $valorBodega;
                } elseif (is_numeric($valorBodega)) {
                    $prendaBodega = ((int) $valorBodega) === 1;
                } elseif (is_string($valorBodega)) {
                    $prendaBodega = in_array(strtolower(trim($valorBodega)), ['1', 'true', 'yes', 'on'], true);
                }
            }

            $nombre = $prendaData['nombre_producto'] ?? $prendaData['nombre'] ?? 'Sin nombre';

            if (!$prendaModel) {
                $prendaModel = PrendaCot::create([
                    'cotizacion_id' => $cotizacion->id,
                    'nombre_producto' => $nombre,
                    'descripcion' => $prendaData['descripcion'] ?? '',
                    'cantidad' => $prendaData['cantidad'] ?? 1,
                    'prenda_bodega' => $prendaBodega,
                ]);
            } else {
                $prendaModel->update([
                    'nombre_producto' => $nombre,
                    'descripcion' => $prendaData['descripcion'] ?? '',
                    'cantidad' => $prendaData['cantidad'] ?? 1,
                    'prenda_bodega' => $prendaBodega,
                ]);
            }

            $idsConservar[] = $prendaModel->id;

            $this->sincronizarTallasPrenda($prendaModel, $prendaData);
            $this->sincronizarVarianteYTelasPrenda($prendaModel, $prendaData);
        }

        $prendasAEliminar = PrendaCot::where('cotizacion_id', $cotizacion->id)
            ->whereDoesntHave('logoCotizacionesTecnicas')
            ->when(!empty($idsConservar), fn($q) => $q->whereNotIn('id', $idsConservar))
            ->get();

        foreach ($prendasAEliminar as $prenda) {
            $prenda->fotos()->delete();
            $prenda->telaFotos()->delete();
            foreach ($prenda->telas as $tela) {
                $tela->fotos()->delete();
            }
            $prenda->telas()->delete();
            $prenda->tallas()->delete();
            $prenda->variantes()->delete();
            $prenda->delete();
        }
    }

    private function sincronizarTallasPrenda(PrendaCot $prenda, array $prendaData): void
    {
        $tallasColor = $prendaData['tallas_color'] ?? null;
        $cantidades = $prendaData['cantidades'] ?? [];
        $tallasJson = $prendaData['tallas'] ?? '';

        if (is_string($tallasColor)) {
            $tallasColor = json_decode($tallasColor, true) ?? [];
        }

        $prenda->tallas()->delete();

        if (!empty($tallasColor) && is_array($tallasColor)) {
            foreach ($tallasColor as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $assignmentType = $item['assignmentType'] ?? null;
                $color = $item['color'] ?? null;

                if ($assignmentType === 'Sobremedida') {
                    $prenda->tallas()->create([
                        'talla' => 'sobremedida',
                        'color' => $color,
                        'cantidad' => 1,
                        'genero_id' => null,
                    ]);
                    continue;
                }

                $sizesByGender = $item['sizesByGender'] ?? null;
                if (is_array($sizesByGender) && !empty($sizesByGender)) {
                    foreach ($sizesByGender as $genderName => $sizesList) {
                        $generoId = null;
                        if ($genderName === 'DAMA') {
                            $generoId = 2;
                        } elseif ($genderName === 'CABALLERO') {
                            $generoId = 1;
                        }

                        if (!is_array($sizesList)) {
                            continue;
                        }

                        foreach ($sizesList as $talla) {
                            if (!$talla) {
                                continue;
                            }
                            $prenda->tallas()->create([
                                'talla' => (string) $talla,
                                'color' => $color,
                                'cantidad' => 1,
                                'genero_id' => $generoId,
                            ]);
                        }
                    }

                    continue;
                }

                $genders = $item['genders'] ?? [];
                $sizes = $item['sizes'] ?? [];

                $generoId = null;
                if (is_array($genders) && count($genders) === 1) {
                    if ($genders[0] === 'DAMA') {
                        $generoId = 2;
                    } elseif ($genders[0] === 'CABALLERO') {
                        $generoId = 1;
                    }
                }

                if (is_array($sizes)) {
                    foreach ($sizes as $talla) {
                        if (!$talla) {
                            continue;
                        }
                        $prenda->tallas()->create([
                            'talla' => (string) $talla,
                            'color' => $color,
                            'cantidad' => 1,
                            'genero_id' => $generoId,
                        ]);
                    }
                }
            }

            return;
        }

        if (is_string($cantidades)) {
            $cantidades = json_decode($cantidades, true) ?? [];
        }
        if (is_string($tallasJson)) {
            $tallasJson = json_decode($tallasJson, true) ?? [];
        }

        if (!empty($tallasJson) && is_array($tallasJson)) {
            foreach ($tallasJson as $genero => $tallasGenero) {
                $generoId = null;
                if ($genero === 'dama') {
                    $generoId = 2;
                } elseif ($genero === 'caballero') {
                    $generoId = 1;
                }

                if (is_array($tallasGenero)) {
                    foreach ($tallasGenero as $talla) {
                        $cantidad = $cantidades[$talla] ?? 1;
                        if ($talla && $cantidad > 0) {
                            $prenda->tallas()->create([
                                'talla' => (string) $talla,
                                'color' => null,
                                'cantidad' => (int) $cantidad,
                                'genero_id' => $generoId,
                            ]);
                        }
                    }
                }
            }

            return;
        }

        if (!empty($cantidades) && is_array($cantidades)) {
            foreach ($cantidades as $talla => $cantidad) {
                if ($talla && $cantidad > 0) {
                    $prenda->tallas()->create([
                        'talla' => (string) $talla,
                        'color' => null,
                        'cantidad' => (int) $cantidad,
                        'genero_id' => null,
                    ]);
                }
            }
        }
    }

    private function sincronizarVarianteYTelasPrenda(PrendaCot $prenda, array $prendaData): void
    {
        $variantes = $prendaData['variantes'] ?? [];
        if (is_string($variantes)) {
            $variantes = json_decode($variantes, true) ?? [];
        }
        if (!is_array($variantes)) {
            $variantes = [];
        }

        $telasMultiples = $variantes['telas_multiples'] ?? [];
        if (is_string($telasMultiples)) {
            $telasMultiples = json_decode($telasMultiples, true) ?? [];
        }
        if (!is_array($telasMultiples)) {
            $telasMultiples = [];
        }

        $colorVariante = $variantes['color'] ?? null;
        if ((!$colorVariante || $colorVariante === '') && !empty($telasMultiples) && is_array($telasMultiples[0] ?? null)) {
            $colorVariante = $telasMultiples[0]['color'] ?? null;
        }

        $tipoMangaId = $variantes['tipo_manga_id'] ?? null;
        if (is_string($tipoMangaId) && $tipoMangaId !== '') {
            $tipoMangaId = (int) $tipoMangaId;
        }

        $generoIdAGuardar = $variantes['genero_id'] ?? null;
        if (is_string($generoIdAGuardar)) {
            $generoIdAGuardar = json_decode($generoIdAGuardar, true) ?? [];
        }
        if (!is_array($generoIdAGuardar)) {
            $generoIdAGuardar = $generoIdAGuardar ? [$generoIdAGuardar] : [];
        }
        $generoIdAGuardar = array_values(array_filter($generoIdAGuardar, fn($v) => $v !== null && $v !== '' && $v !== '0'));
        if (empty($generoIdAGuardar)) {
            $generoIdAGuardar = null;
        }

        $variante = PrendaVarianteCot::updateOrCreate(
            ['prenda_cot_id' => $prenda->id],
            [
                'genero_id' => is_array($generoIdAGuardar) ? json_encode($generoIdAGuardar) : $generoIdAGuardar,
                'color' => $colorVariante,
                'tipo_manga_id' => $tipoMangaId,
                'tipo_broche_id' => $variantes['tipo_broche_id'] ?? null,
                'obs_broche' => $variantes['obs_broche'] ?? null,
                'tiene_bolsillos' => $variantes['tiene_bolsillos'] ?? false,
                'obs_bolsillos' => $variantes['obs_bolsillos'] ?? null,
                'aplica_manga' => $variantes['aplica_manga'] ?? false,
                'obs_manga' => $variantes['obs_manga'] ?? null,
                'tiene_reflectivo' => $variantes['tiene_reflectivo'] ?? false,
                'obs_reflectivo' => $variantes['obs_reflectivo'] ?? null,
                'telas_multiples' => !empty($telasMultiples) ? $telasMultiples : null,
                'descripcion_adicional' => $variantes['descripcion_adicional'] ?? null,
            ]
        );

        $telaPairs = [];
        foreach ($telasMultiples as $telaInfo) {
            if (!is_array($telaInfo)) {
                continue;
            }

            $colorId = null;
            if (!empty($telaInfo['color'])) {
                $colorModel = \App\Models\ColorPrenda::where('nombre', $telaInfo['color'])->first();
                $colorId = $colorModel?->id;
            }

            $telaId = null;
            if (!empty($telaInfo['tela'])) {
                $telaModel = \App\Models\TelaPrenda::where('nombre', $telaInfo['tela'])->first();
                $telaId = $telaModel?->id;
            }

            if (!$colorId || !$telaId) {
                continue;
            }

            $telaPairs[] = [$colorId, $telaId];

            PrendaTelaCot::firstOrCreate([
                'prenda_cot_id' => $prenda->id,
                'variante_prenda_cot_id' => $variante->id,
                'color_id' => $colorId,
                'tela_id' => $telaId,
            ]);
        }

        $telasExistentes = PrendaTelaCot::where('prenda_cot_id', $prenda->id)
            ->where('variante_prenda_cot_id', $variante->id)
            ->get();

        foreach ($telasExistentes as $telaExistente) {
            $enRequest = false;
            foreach ($telaPairs as [$cId, $tId]) {
                if ((int) $telaExistente->color_id === (int) $cId && (int) $telaExistente->tela_id === (int) $tId) {
                    $enRequest = true;
                    break;
                }
            }
            if (!$enRequest) {
                $telaExistente->fotos()->delete();
                $telaExistente->delete();
            }
        }
    }
}
