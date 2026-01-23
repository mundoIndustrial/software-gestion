<?php

namespace App\Application\Pedidos\DTOs;

class AgregarPrendaSimpleDTO
{
    public function __construct(
        public string $pedidoId,
        public string $nombrePrenda,
        public int $cantidad,
        public ?string $descripcion = null
    ) {}

    public static function fromRequest(string $pedidoId, array $data): self
    {
        return new self(
            pedidoId: $pedidoId,
            nombrePrenda: $data['nombre_prenda'],
            cantidad: (int)$data['cantidad'],
            descripcion: $data['descripcion'] ?? null
        );
    }
}
