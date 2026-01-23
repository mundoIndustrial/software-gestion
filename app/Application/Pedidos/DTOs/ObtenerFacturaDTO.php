<?php

namespace App\Application\Pedidos\DTOs;

class ObtenerFacturaDTO
{
    public function __construct(
        public string $pedidoId
    ) {}

    public static function fromRequest(string $pedidoId): self
    {
        return new self($pedidoId);
    }
}

