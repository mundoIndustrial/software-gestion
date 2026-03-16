<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Domain\Pedidos\Contracts\ConsecutivosService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ConsecutivosServiceImpl
 * 
 * Implementación de ConsecutivosService
 * Obtiene datos de consecutivos de recibos del pedido
 */
class ConsecutivosServiceImpl implements ConsecutivosService
{
    public function obtenerConsecutivosPrenda(int $pedidoId, int $prendaId): ?array
    {
        try {
            Log::info('[ConsecutivosService] Buscando consecutivos para prenda', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId
            ]);

            // Obtener consecutivos base
            $consecutivos = DB::table('consecutivos_recibos_pedidos')
                ->where('pedido_produccion_id', $pedidoId)
                ->where(function ($query) use ($prendaId) {
                    $query->where('prenda_id', $prendaId)
                          ->orWhereNull('prenda_id');
                })
                ->select([
                    'id', 'pedido_produccion_id', 'prenda_id', 'tipo_recibo',
                    'consecutivo_actual', 'consecutivo_inicial', 'activo',
                    'marcar_plooter', 'color_costura', 'estado', 'area',
                    'notas', 'created_at', 'updated_at'
                ])
                ->get();

            // Obtener parciales
            $parciales = DB::table('pedidos_parciales')
                ->where('pedido_produccion_id', $pedidoId)
                ->where('prenda_pedido_id', $prendaId)
                ->whereNull('deleted_at')
                ->select([
                    'id', 'pedido_produccion_id', 'prenda_pedido_id as prenda_id',
                    'tipo_recibo', 'consecutivo_actual', 'consecutivo_inicial',
                    'activo', 'estado', 'notas', 'created_at', 'updated_at',
                    DB::raw("'PARCIAL' as origen")
                ])
                ->get();

            if ($consecutivos->isEmpty() && $parciales->isEmpty()) {
                Log::info('[ConsecutivosService] No hay consecutivos', [
                    'pedido_id' => $pedidoId,
                    'prenda_id' => $prendaId
                ]);
                return null;
            }

            return $this->estructurarConsecutivos($consecutivos, $parciales);

        } catch (\Exception $e) {
            Log::error('[ConsecutivosService] Error', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function obtenerUltimoReciboCostura(int $pedidoId, int $prendaId): ?array
    {
        try {
            $recibo = DB::table('consecutivos_recibos_pedidos')
                ->where('pedido_produccion_id', $pedidoId)
                ->where(function ($query) use ($prendaId) {
                    $query->where('prenda_id', $prendaId)
                          ->orWhereNull('prenda_id');
                })
                ->where('tipo_recibo', 'COSTURA')
                ->where('activo', 1)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$recibo) {
                return null;
            }

            return [
                'consecutivo_actual' => $recibo->consecutivo_actual,
                'activo' => $recibo->activo,
                'created_at' => $recibo->created_at,
                'tipo_recibo' => $recibo->tipo_recibo,
                'notas' => $recibo->notas
            ];

        } catch (\Exception $e) {
            Log::error('[ConsecutivosService] Error obtener ultimo COSTURA', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function estructurarConsecutivos($consecutivos, $parciales): array
    {
        $recibos = [
            'COSTURA' => null,
            'ESTAMPADO' => null,
            'BORDADO' => null,
            'DTF' => null,
            'SUBLIMADO' => null,
            'REFLECTIVO' => null,
            'COSTURA-BODEGA' => null
        ];

        // Agrupar y priorizar registros base
        $agrupados = [];
        foreach ($consecutivos as $c) {
            $tipo = $c->tipo_recibo;
            if (!isset($agrupados[$tipo])) {
                $agrupados[$tipo] = [];
            }
            $agrupados[$tipo][] = $c;
        }

        foreach ($agrupados as $tipo => $items) {
            if (!array_key_exists($tipo, $recibos)) {
                continue;
            }

            $base = collect($items)->first(function ($item) {
                $notas = (string)($item->notas ?? '');
                return stripos($notas, 'parcial_id:') === false;
            });

            if ($base) {
                $recibos[$tipo] = [
                    'consecutivo_actual' => $base->consecutivo_actual,
                    'activo' => $base->activo,
                    'created_at' => $base->created_at,
                    'tipo_recibo' => $base->tipo_recibo,
                    'notas' => $base->notas
                ];
                continue;
            }

            $menor = collect($items)
                ->filter(fn($item) => !empty($item->consecutivo_actual))
                ->sortBy(fn($item) => (int)$item->consecutivo_actual)
                ->first();

            if ($menor) {
                $recibos[$tipo] = [
                    'consecutivo_actual' => $menor->consecutivo_actual,
                    'activo' => $menor->activo,
                    'created_at' => $menor->created_at,
                    'tipo_recibo' => $menor->tipo_recibo,
                    'notas' => $menor->notas
                ];
            }
        }

        $recibos['parciales'] = $parciales->toArray();

        return $recibos;
    }
}
