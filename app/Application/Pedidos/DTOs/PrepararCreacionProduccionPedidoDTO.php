<?php

namespace App\Application\Pedidos\DTOs;

class PrepararCreacionProduccionPedidoDTO
{
    public function __construct(
        public ?string $tipo = null,
        public ?string $editarId = null,
        public ?int $usuarioId = null,
        public bool $allowEditarCotizacionCreada = false
    ) {}

    public static function fromRequest(?string $tipo = null, ?string $editarId = null, ?int $usuarioId = null, bool $allowEditarCotizacionCreada = false): self
    {
        return new self(
            tipo: $tipo,
            editarId: $editarId,
            usuarioId: $usuarioId,
            allowEditarCotizacionCreada: $allowEditarCotizacionCreada
        );
    }
}

