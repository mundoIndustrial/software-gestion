<?php

namespace App\Application\Pedidos\DTOs;

final class AgregarPrendaCompletaDTO
{
    public function __construct(
        public readonly int|string $pedidoId,
        public readonly string $nombrePrenda,
        public readonly string $origen,
        public readonly string $novedad,
        public readonly ?string $descripcion = null,
        public readonly ?string $tallaJson = null,
        public readonly ?array $imagenes = null,
        public readonly ?array $telas = null,
    ) {}

    public static function fromRequest(int|string $pedidoId, array $data, ?array $imagenes = null): self
    {
        return new self(
            pedidoId: $pedidoId,
            nombrePrenda: $data['nombre_prenda'] ?? throw new \InvalidArgumentException('nombre_prenda requerido'),
            origen: $data['origen'] ?? throw new \InvalidArgumentException('origen requerido'),
            novedad: $data['novedad'] ?? throw new \InvalidArgumentException('novedad requerida'),
            descripcion: $data['descripcion'] ?? null,
            tallaJson: $data['cantidad_talla'] ?? null,
            imagenes: $imagenes,
            telas: $data['telas'] ?? null,
        );
    }
}
