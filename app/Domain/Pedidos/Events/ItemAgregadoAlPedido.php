<?php

namespace App\Domain\Pedidos\Events;

use App\Domain\Shared\DomainEvent;
use App\Domain\Pedidos\ValueObjects\TipoItem;

/**
 * Domain Event: ItemAgregadoAlPedido
 * 
 * Se dispara cuando un item (Prenda o EPP) es agregado exitosamente a un pedido
 */
class ItemAgregadoAlPedido extends DomainEvent
{
    public function __construct(
        public readonly int $pedidoId,
        public readonly int $itemId,
        public readonly int $referenciaId,
        public readonly string $tipo,  // 'prenda' o 'epp'
        public readonly string $nombre,
        public readonly int $orden,
        ?\DateTimeImmutable $ocurridoEn = null
    ) {
        parent::__construct($ocurridoEn);
    }

    public static function desde(
        int $pedidoId,
        int $itemId,
        int $referenciaId,
        TipoItem $tipo,
        string $nombre,
        int $orden
    ): self {
        return new self(
            pedidoId: $pedidoId,
            itemId: $itemId,
            referenciaId: $referenciaId,
            tipo: $tipo->valor(),
            nombre: $nombre,
            orden: $orden
        );
    }

    public function nombreEvento(): string
    {
        return 'item.agregado.pedido';
    }
}
