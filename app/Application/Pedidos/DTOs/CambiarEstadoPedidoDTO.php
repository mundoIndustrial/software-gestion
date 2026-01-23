<?php

namespace App\Application\Pedidos\DTOs;

final class CambiarEstadoPedidoDTO
{
    public function __construct(
        public readonly int|string $pedidoId,
        public readonly string $nuevoEstado,
        public readonly ?string $razon = null,
    ) {}

    public static function fromRequest(int|string $pedidoId, array $data): self
    {
        return new self(
            pedidoId: $pedidoId,
            nuevoEstado: $data['nuevo_estado'] ?? throw new \InvalidArgumentException('nuevo_estado requerido'),
            razon: $data['razon'] ?? null,
        );
    }
}
