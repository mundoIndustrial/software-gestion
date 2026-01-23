<?php

namespace App\Domain\Pedidos\Queries;

use App\Domain\Shared\CQRS\Query;

/**
 * ObtenerPedidoQuery
 * 
 * Query para obtener detalles completos de un pedido
 * 
 * @param int|string $pedidoId ID del pedido a obtener
 */
class ObtenerPedidoQuery implements Query
{
    public function __construct(
        private int|string $pedidoId,
    ) {}

    public function getPedidoId(): int|string
    {
        return $this->pedidoId;
    }
}

