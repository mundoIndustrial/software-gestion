<?php

namespace App\Infrastructure\Services\Bodega;

use App\Models\PedidoAnexoHistorial;
use App\Models\PedidoProduccion;
use Carbon\Carbon;

class BodegaPedidoHistorialService
{
    public function obtenerUltimaActualizacionPrendas(string $numeroPedido): ?Carbon
    {
        $pedidoProduccion = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();

        if (!$pedidoProduccion) {
            return null;
        }

        $ultimoCambio = PedidoAnexoHistorial::where('pedido_produccion_id', $pedidoProduccion->id)
            ->whereIn('tipo', ['PRENDA', 'EPP'])
            ->latest('created_at')
            ->first();

        return $ultimoCambio?->created_at;
    }
}
