<?php

namespace App\Application\Operario\Services;

use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;

final class ReciboTallerContextService
{
    public function resolver(
        int $pedidoIdParam,
        int $numeroRecibo,
        string $tipoRecibo,
        ?int $prendaBodegaId = null
    ): array {
        $tipoReciboNormalizado = strtoupper(trim($tipoRecibo));
        $esBodega = $tipoReciboNormalizado === 'CORTE-PARA-BODEGA';

        $pedido = null;
        $recibo = null;
        $pedidoIdResuelto = $pedidoIdParam;

        if ($esBodega) {
            $recibo = ConsecutivoReciboPedido::query()
                ->with('pedido')
                ->find($pedidoIdParam);

            if (!$recibo && $prendaBodegaId && $numeroRecibo > 0) {
                $recibo = ConsecutivoReciboPedido::query()
                    ->with('pedido')
                    ->where('prenda_bodega_id', $prendaBodegaId)
                    ->where('consecutivo_actual', $numeroRecibo)
                    ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [$tipoReciboNormalizado])
                    ->first();
            }

            if (!$recibo && $numeroRecibo > 0) {
                $recibo = ConsecutivoReciboPedido::query()
                    ->with('pedido')
                    ->where('consecutivo_actual', $numeroRecibo)
                    ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [$tipoReciboNormalizado])
                    ->first();
            }

            if ($recibo) {
                $pedido = $recibo->pedido ?: PedidoProduccion::find((int) ($recibo->pedido_produccion_id ?? 0));

                if (!$pedido && $prendaBodegaId > 0) {
                    $pedidoProduccionIdBodega = (int) (DB::table('recibo_por_partes')
                        ->where('consecutivo_original', $recibo->consecutivo_actual)
                        ->where('prenda_pedido_id', $prendaBodegaId)
                        ->whereRaw('UPPER(TRIM(tipo_recibo)) = ?', [$tipoReciboNormalizado])
                        ->orderByDesc('id')
                        ->value('pedido_produccion_id') ?? 0);

                    if ($pedidoProduccionIdBodega > 0) {
                        $pedido = PedidoProduccion::find($pedidoProduccionIdBodega);
                    }
                }
            }
        }

        if (!$pedido) {
            $pedido = PedidoProduccion::find($pedidoIdParam);
        }

        if ($pedido) {
            $pedidoIdResuelto = (int) $pedido->id;
        }

        return [
            'es_bodega' => $esBodega,
            'pedido' => $pedido,
            'recibo' => $recibo,
            'pedido_id' => $pedidoIdResuelto,
        ];
    }
}
