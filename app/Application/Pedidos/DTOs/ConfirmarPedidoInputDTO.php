<?php

namespace App\Application\Pedidos\DTOs;

final class ConfirmarPedidoInputDTO
{
    public function __construct(
        public readonly int $id
    ) {}

    public static function fromId(int|string $id): self
    {
        return new self((int) $id);
    }
}

