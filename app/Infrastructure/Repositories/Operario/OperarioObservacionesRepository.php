<?php

namespace App\Infrastructure\Repositories\Operario;

use Illuminate\Support\Facades\DB;

class OperarioObservacionesRepository
{
    public function obtenerPrendaParcialId(int $parcialId, int $pedidoId): int
    {
        return (int) DB::table('pedidos_parciales')
            ->where('id', $parcialId)
            ->where('pedido_produccion_id', $pedidoId)
            ->value('prenda_pedido_id');
    }

    public function buscarObservacionPorPedidoPrendaYProceso(
        int $pedidoId,
        int $prendaId,
        string $tipoProceso
    ): ?object {
        return DB::table('observaciones_recibos_procesos')
            ->where('pedido_produccion_id', $pedidoId)
            ->where('prenda_pedido_id', $prendaId)
            ->where('tipo_proceso', $tipoProceso)
            ->orderByDesc('updated_at')
            ->first();
    }

    public function buscarObservacionPorPedidoYProceso(int $pedidoId, string $tipoProceso): ?object
    {
        return DB::table('observaciones_recibos_procesos')
            ->where('pedido_produccion_id', $pedidoId)
            ->where('tipo_proceso', $tipoProceso)
            ->orderByDesc('updated_at')
            ->first();
    }
}

