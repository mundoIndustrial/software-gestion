<?php

namespace App\Application\Pedidos\Services;

use App\Application\Pedidos\Contracts\PedidoTransformService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PedidoTransformServiceImpl
 * 
 * Implementación de transformación de datos de pedidos
 */
class PedidoTransformServiceImpl implements PedidoTransformService
{
    public function transformarDetalleCompleto(array $datos): array
    {
        return $this->transformarProcesos($datos['prendas'] ?? []);
    }

    public function transformarDatosEdicion(array $datos): array
    {
        if (!isset($datos['prendas']) || !is_array($datos['prendas'])) {
            return $datos;
        }

        foreach ($datos['prendas'] as &$prenda) {
            if (isset($prenda['procesos']) && is_array($prenda['procesos'])) {
                foreach ($prenda['procesos'] as &$proceso) {
                    if (isset($proceso['ubicaciones']) && is_string($proceso['ubicaciones'])) {
                        $decodedUb = json_decode($proceso['ubicaciones'], true);
                        if (is_array($decodedUb)) {
                            $proceso['ubicaciones_array'] = $decodedUb;
                        }
                    }
                }
                unset($proceso);
            }
        }
        unset($prenda);

        return $datos;
    }

    public function transformarProcesos(array $prendas): array
    {
        if (!is_array($prendas)) {
            return [];
        }

        foreach ($prendas as &$prenda) {
            if (!is_array($prenda) || !isset($prenda['procesos'])) {
                continue;
            }

            foreach ($prenda['procesos'] as &$proceso) {
                $this->transformarProcesoIndividual($proceso, $prenda['id'] ?? null);
            }
            unset($proceso);
        }
        unset($prenda);

        return $prendas;
    }

    public function transformarTalasConColores(array $tallas): array
    {
        $talasTransformadas = [
            'dama' => [],
            'caballero' => [],
            'unisex' => []
        ];

        foreach ($tallas as $talla) {
            $genero = strtolower($talla->genero ?? 'caballero');
            if ($genero === 'dama') $genero = 'dama';
            elseif ($genero === 'caballero') $genero = 'caballero';
            else $genero = 'unisex';

            $colores = DB::table('pedidos_procesos_prenda_talla_colores')
                ->where('pedidos_procesos_prenda_talla_id', $talla->id)
                ->get();

            if ($colores->count() > 0) {
                $talasTransformadas[$genero][$talla->talla] = $colores->map(fn($color) => [
                    'color' => $color->color_nombre,
                    'cantidad' => $color->cantidad
                ])->toArray();
            } else {
                $talasTransformadas[$genero][$talla->talla] = $talla->cantidad;
            }
        }

        return $talasTransformadas;
    }

    private function transformarProcesoIndividual(&$proceso, ?int $prendaId = null): void
    {
        if (!is_array($proceso)) {
            return;
        }

        // Decodificar ubicaciones si es string JSON
        if (isset($proceso['ubicaciones']) && is_string($proceso['ubicaciones'])) {
            $decodedUb = json_decode($proceso['ubicaciones'], true);
            if (is_array($decodedUb)) {
                $proceso['ubicaciones_array'] = $decodedUb;
            }
        }

        // Transformar tallas si existen
        if (isset($proceso['id']) && isset($proceso['modo_tallas']) && $proceso['modo_tallas'] === 'general') {
            $this->transformarTalasDelProceso($proceso);
        }
    }

    private function transformarTalasDelProceso(&$proceso): void
    {
        try {
            $tallas = DB::table('pedidos_procesos_prenda_tallas')
                ->where('proceso_prenda_detalle_id', $proceso['id'])
                ->get();

            $talasTransformadas = [
                'dama' => [],
                'caballero' => [],
                'unisex' => []
            ];

            foreach ($tallas as $talla) {
                $genero = strtolower($talla->genero ?? 'caballero');
                if ($genero === 'dama') $genero = 'dama';
                elseif ($genero === 'caballero') $genero = 'caballero';
                else $genero = 'unisex';

                $colores = DB::table('pedidos_procesos_prenda_talla_colores')
                    ->where('pedidos_procesos_prenda_talla_id', $talla->id)
                    ->get();

                if ($colores->count() > 0) {
                    $talasTransformadas[$genero][$talla->talla] = $colores->map(fn($color) => [
                        'color' => $color->color_nombre,
                        'cantidad' => $color->cantidad
                    ])->toArray();
                } else {
                    $talasTransformadas[$genero][$talla->talla] = $talla->cantidad;
                }
            }

            $proceso['tallas'] = $talasTransformadas;

            // Observaciones por talla
            $tallasObs = DB::table('pedidos_procesos_prenda_tallas')
                ->where('proceso_prenda_detalle_id', $proceso['id'])
                ->whereNotNull('observaciones')
                ->where('observaciones', '!=', '')
                ->get(['genero', 'talla', 'observaciones']);

            $obsPorTalla = [
                'dama' => [],
                'caballero' => [],
                'unisex' => [],
            ];

            foreach ($tallasObs as $row) {
                $obs = trim((string)($row->observaciones ?? ''));
                if ($obs === '') {
                    continue;
                }

                $genero = strtolower((string)($row->genero ?? ''));
                if ($genero !== 'dama' && $genero !== 'caballero' && $genero !== 'unisex') {
                    $genero = 'caballero';
                }

                $tallaKey = $row->talla !== null ? (string)$row->talla : 'SOBREMEDIDA';
                $obsPorTalla[$genero][$tallaKey] = $obs;
            }

            $proceso['observaciones_por_talla'] = $obsPorTalla;

        } catch (\Exception $e) {
            Log::debug('[PedidoTransformService] Error transformar tallas del proceso', [
                'proceso_id' => $proceso['id'] ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }
}
