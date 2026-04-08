<?php

namespace App\Application\Pedidos\DTOs;

class ListarProduccionPedidosDTO
{
    public function __construct(
        public ?string $tipo = null,
        public array $filtros = [],
        public ?int $usuarioId = null,
        public bool $soloAsesor = false
    ) {}

    public static function fromRequest(
        ?string $tipo = null,
        array $filtros = [],
        ?int $usuarioId = null,
        bool $soloAsesor = false
    ): self
    {
        return new self(
            tipo: $tipo,
            filtros: $filtros,
            usuarioId: $usuarioId,
            soloAsesor: $soloAsesor
        );
    }
}

