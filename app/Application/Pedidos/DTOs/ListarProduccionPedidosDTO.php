<?php

namespace App\Application\Pedidos\DTOs;

class ListarProduccionPedidosDTO
{
    public function __construct(
        public ?string $tipo = null,
        public array $filtros = []
    ) {}

    public static function fromRequest(?string $tipo = null, array $filtros = []): self
    {
        return new self(
            tipo: $tipo,
            filtros: $filtros
        );
    }
}

