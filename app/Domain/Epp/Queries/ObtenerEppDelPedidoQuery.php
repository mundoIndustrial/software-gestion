<?php

namespace App\Domain\Epp\Queries;

use App\Domain\Shared\CQRS\Query;

/**
 * ObtenerEppDelPedidoQuery
 * 
 * Query para obtener todos los EPP agregados a un pedido
 */
class ObtenerEppDelPedidoQuery implements Query
{
    public function __construct(
        private int $pedidoId,
    ) {}

    public function getPedidoId(): int
    {
        return $this->pedidoId;
    }
}
