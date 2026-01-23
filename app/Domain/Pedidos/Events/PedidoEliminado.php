<?php

namespace App\Domain\Pedidos\Events;

use App\Domain\Shared\DomainEvent;

/**
 * Domain Event: Pedido Eliminado
 */
class PedidoEliminado extends DomainEvent
{
    public function __construct(
        public int $pedidoId,
        public string $numero
    ) {}
}
