<?php

namespace App\Domain\Pedidos\Events;

use App\Domain\Shared\DomainEvent;

/**
 * Domain Event: ItemEliminadoDelPedido
 * 
 * Se dispara cuando un item es eliminado del pedido
 */
class ItemEliminadoDelPedido extends DomainEvent
{
    public function __construct(
        public readonly int $pedidoId,
        public readonly int $itemId,
        public readonly string $tipo,  // 'prenda' o 'epp'
        public readonly int $referenciaId,
        ?\DateTimeImmutable $ocurridoEn = null
    ) {
        parent::__construct($ocurridoEn);
    }

    public function nombreEvento(): string
    {
        return 'item.eliminado.pedido';
    }
}
