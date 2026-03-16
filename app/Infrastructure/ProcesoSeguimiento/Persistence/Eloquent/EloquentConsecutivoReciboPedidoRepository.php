<?php

namespace App\Infrastructure\ProcesoSeguimiento\Persistence\Eloquent;

use App\Domain\ProcesoSeguimiento\Repositories\ConsecutivoReciboPedidoRepository;
use App\Models\ConsecutivoReciboPedido;

/**
 * Eloquent implementation of ConsecutivoReciboPedidoRepository.
 */
class EloquentConsecutivoReciboPedidoRepository implements ConsecutivoReciboPedidoRepository
{
    public function encontrarPorPedidoYPrenda(int $pedidoProduccionId, int $prendaId): ?ConsecutivoReciboPedido
    {
        return ConsecutivoReciboPedido::where('pedido_produccion_id', $pedidoProduccionId)
            ->where('prenda_id', $prendaId)
            ->first();
    }

    public function actualizarArea(ConsecutivoReciboPedido $consecutivo, string $area, ?string $estado = null): void
    {
        $datos = ['area' => $area];

        if ($estado !== null) {
            $datos['estado'] = $estado;
        }

        $consecutivo->update($datos);
    }
}
