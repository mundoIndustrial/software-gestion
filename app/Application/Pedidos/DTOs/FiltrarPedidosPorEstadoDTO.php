<?php

namespace App\Application\Pedidos\DTOs;

final class FiltrarPedidosPorEstadoDTO
{
    public function __construct(
        public readonly string $estado,
        public readonly int $page = 1,
        public readonly int $perPage = 15,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            estado: $data['estado'] ?? throw new \InvalidArgumentException('estado requerido'),
            page: $data['page'] ?? 1,
            perPage: $data['per_page'] ?? 15,
        );
    }
}

