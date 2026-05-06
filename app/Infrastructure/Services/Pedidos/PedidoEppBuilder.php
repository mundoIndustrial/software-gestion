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
        $localId = trim((string) ($eppData['local_id'] ?? $eppData['_local_id'] ?? $eppData['uid'] ?? ''));

        Log::info('[PedidoEppBuilder] Creando EPP', [
            'pedido_id' => $pedido->id,
            'nombre' => $nombreEpp,
            'cantidad' => $cantidad,
            'epp_id' => $eppId,
            'index' => $eppIndex,
            'local_id_input' => $localId !== '' ? $localId : null,
        ]);

        $epp = PedidoEpp::create([
            'pedido_produccion_id' => $pedido->id,
            'epp_id' => $eppId,
            'cantidad' => $cantidad,
            'observaciones' => $observaciones,
            'nombre' => $nombreEpp,
            'local_id' => $localId !== '' ? $localId : null,
        ]);

        Log::info('[PedidoEppBuilder] EPP creado', [
            'epp_id' => $epp->id,
            'nombre' => $epp->nombre,
            'cantidad' => $epp->cantidad,
            'local_id' => $epp->local_id,
        ]);

        return $epp;
    }
}
