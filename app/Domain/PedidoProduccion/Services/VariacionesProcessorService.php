<?php

namespace App\Domain\PedidoProduccion\Services;

use App\Application\Services\ColorGeneroMangaBrocheService;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de Dominio para procesar variaciones (manga, broche, color, tela)
 */
class VariacionesProcessorService
{
    public function __construct(
        private ColorGeneroMangaBrocheService $colorGeneroService
    ) {}

    /**
     * Procesar variaciones desde item
     * Extrae IDs de manga, broche, color y tela
     */
    public function procesarVariaciones(array $item): array
    {
        $resultado = [
            'tipo_manga_id' => null,
            'tipo_broche_boton_id' => null,
            'color_id' => null,
            'tela_id' => null,
        ];

        // Procesar manga y broche desde variaciones JSON
        $variaciones_data = $item['variaciones'] ?? [];
        if (is_string($variaciones_data)) {
            $variaciones_parsed = json_decode($variaciones_data, true);

            if (is_array($variaciones_parsed)) {
                // Procesar manga
                if (isset($variaciones_parsed['manga']['tipo']) && !empty($variaciones_parsed['manga']['tipo'])) {
                    try {
                        $tipoManga = $this->colorGeneroService->buscarOCrearManga($variaciones_parsed['manga']['tipo']);
                        $resultado['tipo_manga_id'] = $tipoManga->id;
                    } catch (\Exception $e) {
                        Log::warning(' Error procesando tipo manga', ['error' => $e->getMessage()]);
                    }
                }

                // Procesar broche
                if (isset($variaciones_parsed['broche']['tipo']) && !empty($variaciones_parsed['broche']['tipo'])) {
                    try {
                        $tipoBroche = $this->colorGeneroService->buscarOCrearBroche($variaciones_parsed['broche']['tipo']);
                        $resultado['tipo_broche_boton_id'] = $tipoBroche->id;
                    } catch (\Exception $e) {
                        Log::warning(' Error procesando tipo broche', ['error' => $e->getMessage()]);
                    }
                }
            }
        }

        // Procesar color
        if (!empty($item['color_id'])) {
            $resultado['color_id'] = $item['color_id'];
        } elseif (!empty($item['color'])) {
            try {
                $color = $this->colorGeneroService->buscarOCrearColor($item['color']);
                $resultado['color_id'] = $color->id;
            } catch (\Exception $e) {
                Log::warning(' Error procesando color', ['error' => $e->getMessage()]);
            }
        }

        // Procesar tela
        if (!empty($item['tela_id'])) {
            $resultado['tela_id'] = $item['tela_id'];
        } elseif (!empty($item['tela'])) {
            try {
                $tela = $this->colorGeneroService->obtenerOCrearTela($item['tela']);
                $resultado['tela_id'] = $tela->id;
            } catch (\Exception $e) {
                Log::warning(' Error procesando tela', ['error' => $e->getMessage()]);
            }
        }

        return $resultado;
    }
}
