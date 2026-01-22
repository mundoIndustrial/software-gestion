<?php

namespace App\Domain\Pedidos\Events;

use App\Domain\Shared\DomainEvent;

/**
 * Domain Event: Pedido Actualizado
 */
class PedidoActualizado extends DomainEvent
{
    public function __construct(
        public ?int $pedidoId,
        public string $numero,
        public string $campo
    ) {}
}
