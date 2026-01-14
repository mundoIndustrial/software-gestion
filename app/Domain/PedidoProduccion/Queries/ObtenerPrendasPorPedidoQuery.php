<?php

namespace App\Domain\PedidoProduccion\Queries;

use App\Domain\Shared\CQRS\Query;

/**
 * ObtenerPrendasPorPedidoQuery
 * 
 * Query para obtener todas las prendas de un pedido
 * 
 * @param int|string $pedidoId ID del pedido
 */
class ObtenerPrendasPorPedidoQuery implements Query
{
    public function __construct(
        private int|string $pedidoId,
    ) {}

    public function getPedidoId(): int|string
    {
        return $this->pedidoId;
    }
}
