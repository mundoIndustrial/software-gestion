<?php

namespace App\Application\Pedidos\DTOs;

/**
 * DTO para actualizar datos de una prenda existente
 * 
 * Solo permite actualizar campos en prendas_pedido:
 * - nombre_prenda
 * - descripcion
 * - de_bodega
 */
final class ActualizarPrendaPedidoDTO
{
    public function __construct(
        public readonly int $prendaId,
        public readonly ?string $nombrePrenda = null,
        public readonly ?string $descripcion = null,
        public readonly ?bool $deBodega = null,
    ) {}

    public static function fromRequest(int $prendaId, array $data): self
    {
        return new self(
            prendaId: $prendaId,
            nombrePrenda: $data['nombre_prenda'] ?? null,
            descripcion: $data['descripcion'] ?? null,
            deBodega: isset($data['de_bodega']) ? (bool) $data['de_bodega'] : null,
        );
    }
}
