<?php

namespace App\Domain\Pedidos\Events;

use App\Domain\Shared\DomainEvent;

/**
 * Domain Event: Pedido Creado
 */
class PedidoCreado extends DomainEvent
{
    public function __construct(
        public ?int $pedidoId,
        public string $numero,
        public int $clienteId,
        public string $descripcion,
        public int $totalPrendas
    ) {}
}

