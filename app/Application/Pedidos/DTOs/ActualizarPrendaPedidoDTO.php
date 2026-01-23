<?php

namespace App\Application\Pedidos\DTOs;

final class ActualizarPrendaPedidoDTO
{
    public function __construct(
        public readonly int|string $pedidoId,
        public readonly int $prendaIndex,
        public readonly ?string $nombre = null,
        public readonly ?string $descripcion = null,
        public readonly ?array $tallas = null,
    ) {}

    public static function fromRequest(int|string $pedidoId, array $data): self
    {
        return new self(
            pedidoId: $pedidoId,
            prendaIndex: $data['prendasIndex'] ?? throw new \InvalidArgumentException('prendasIndex requerido'),
            nombre: $data['nombre'] ?? null,
            descripcion: $data['descripcion'] ?? null,
            tallas: isset($data['tallas']) ? json_decode($data['tallas'], true) : null,
        );
    }
}
