<?php

namespace App\Application\Pedidos\Services;

use Illuminate\Support\Facades\Log;

/**
 * Resuelve el contexto necesario para persistir variantes de una prenda.
 */
class PrendaVarianteContextResolver
{
    public function __construct(
        private ColorTelaCatalogService $colorTelaService,
        private CaracteristicasPrendaCatalogService $caracteristicasService
    ) {}

    public function resolver(array $prendaData): array
    {
        $variacionesParsed = $prendaData['variaciones'] ?? [];
        if (is_string($variacionesParsed)) {
            $variacionesParsed = json_decode($variacionesParsed, true) ?? [];
        }

        $tipoMangaId = $variacionesParsed['tipo_manga_id'] ?? $prendaData['tipo_manga_id'] ?? null;
        if (empty($tipoMangaId)) {
            $nombreManga = $variacionesParsed['tipo_manga'] ?? $prendaData['manga'] ?? null;
            if (!empty($nombreManga)) {
                $tipoMangaId = $this->caracteristicasService->obtenerOCrearManga($nombreManga);
            }
        }

        $tipoBrocheBotonId = $variacionesParsed['tipo_broche_boton_id'] ?? $prendaData['tipo_broche_boton_id'] ?? null;
        if (empty($tipoBrocheBotonId)) {
            $nombreBroche = $variacionesParsed['tipo_broche'] ?? $prendaData['broche'] ?? null;
            if (!empty($nombreBroche)) {
                $tipoBrocheBotonId = $this->caracteristicasService->obtenerOCrearBroche($nombreBroche);
            }
        }

        $obsManga = $variacionesParsed['obs_manga'] ?? $prendaData['obs_manga'] ?? $prendaData['manga_obs'] ?? '';
        $obsBroche = $variacionesParsed['obs_broche'] ?? $prendaData['obs_broche'] ?? $prendaData['broche_obs'] ?? '';
        $tieneBolsillos = (bool) ($variacionesParsed['tiene_bolsillos'] ?? $prendaData['tiene_bolsillos'] ?? false);
        $obsBolsillos = $variacionesParsed['obs_bolsillos'] ?? $prendaData['obs_bolsillos'] ?? $prendaData['bolsillos_obs'] ?? '';

        $colorId = $prendaData['color_id'] ?? null;
        $telaId = $prendaData['tela_id'] ?? null;

        if ((!$colorId || !$telaId) && isset($prendaData['telas']) && is_array($prendaData['telas']) && count($prendaData['telas']) > 0) {
            try {
                $primeraTela = $prendaData['telas'][0];

                if (!$colorId && isset($primeraTela['color'])) {
                    $colorId = $this->colorTelaService->obtenerOCrearColor($primeraTela['color']);
                    Log::info('[PrendaVarianteContextResolver] Color procesado desde telas', [
                        'color_nombre' => $primeraTela['color'],
                        'color_id' => $colorId,
                    ]);
                }

                if (!$telaId && isset($primeraTela['tela'])) {
                    $telaId = $this->colorTelaService->obtenerOCrearTela($primeraTela['tela']);
                    Log::info('[PrendaVarianteContextResolver] Tela procesada desde telas', [
                        'tela_nombre' => $primeraTela['tela'],
                        'tela_id' => $telaId,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('[PrendaVarianteContextResolver] Error procesando color/tela', [
                    'error' => $e->getMessage(),
                    'prenda_data' => $prendaData,
                ]);
            }
        }

        return [
            'tipo_manga_id' => $tipoMangaId,
            'tipo_broche_boton_id' => $tipoBrocheBotonId,
            'obs_manga' => $obsManga,
            'obs_broche' => $obsBroche,
            'tiene_bolsillos' => $tieneBolsillos,
            'obs_bolsillos' => $obsBolsillos,
            'color_id' => $colorId,
            'tela_id' => $telaId,
        ];
    }
}
