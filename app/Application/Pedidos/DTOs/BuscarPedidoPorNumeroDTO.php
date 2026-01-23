<?php

namespace App\Application\Pedidos\DTOs;

final class BuscarPedidoPorNumeroDTO
{
    public function __construct(
        public readonly string $numero,
    ) {}

    public static function fromRequest(string $numero): self
    {
        return new self(numero: $numero);
    }
}

