<?php

namespace App\Application\Pedidos\DTOs;

/**
 * DTO para agregar prenda a un pedido
 * 
 * Campos reales de prendas_pedido:
 * - nombre_prenda (required)
 * - descripcion (optional)
 * - de_bodega (boolean, optional)
 * 
 * Las variantes (manga, broche, colores, telas, tallas) se crean despuÃ©s en tablas relacionadas
 */
final class AgregarPrendaAlPedidoDTO
{
    public function __construct(
        public readonly int|string $pedidoId,
        public readonly string $nombrePrenda,
        public readonly ?string $descripcion = null,
        public readonly bool $deBodega = false,
    ) {}

    public static function fromRequest(int|string $pedidoId, array $data): self
    {
        return new self(
            pedidoId: $pedidoId,
            nombrePrenda: $data['nombre_prenda'] ?? throw new \InvalidArgumentException('nombre_prenda requerido'),
            descripcion: $data['descripcion'] ?? null,
            deBodega: $data['de_bodega'] ?? false,
        );
    }
}

