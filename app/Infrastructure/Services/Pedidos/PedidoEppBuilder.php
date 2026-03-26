<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Models\PedidoEpp;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

class PedidoEppBuilder
{
    public function crear(PedidoProduccion $pedido, array $eppData, int $eppIndex): PedidoEpp
    {
        $nombreEpp = $eppData['nombre'] ?? 'SIN NOMBRE';
        $cantidad = $eppData['cantidad'] ?? 1;
        $observaciones = $eppData['observaciones'] ?? null;
        $eppId = $eppData['epp_id'] ?? null;

        Log::info('[PedidoEppBuilder] Creando EPP', [
            'pedido_id' => $pedido->id,
            'nombre' => $nombreEpp,
            'cantidad' => $cantidad,
            'epp_id' => $eppId,
            'index' => $eppIndex,
        ]);

        $epp = PedidoEpp::create([
            'pedido_produccion_id' => $pedido->id,
            'epp_id' => $eppId,
            'cantidad' => $cantidad,
            'observaciones' => $observaciones,
            'nombre' => $nombreEpp,
        ]);

        Log::info('[PedidoEppBuilder] EPP creado', [
            'epp_id' => $epp->id,
            'nombre' => $epp->nombre,
            'cantidad' => $epp->cantidad,
        ]);

        return $epp;
    }
}
