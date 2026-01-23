<?php

namespace App\Application\Pedidos\DTOs;

final class ObtenerPrendasPedidoDTO
{
    public function __construct(
        public readonly int|string $pedidoId,
    ) {}

    public static function fromRoute(int|string $pedidoId): self
    {
        return new self(pedidoId: $pedidoId);
    }
}

