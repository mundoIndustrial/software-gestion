<?php

namespace App\Application\Pedidos\DTOs;

/**
 * DTO para agregar talla y cantidad a una prenda
 * 
 * Maneja campos de prenda_pedido_tallas:
 * - genero: 'M', 'F', etc (enum)
 * - talla: 'S', 'M', 'L', 'XL', 'XXL', etc
 * - cantidad: número de prendas en esta talla
 */
final class AgregarTallaPrendaDTO
{
    public function __construct(
        public readonly int|string $prendaId,
        public readonly string $genero,
        public readonly string $talla,
        public readonly int $cantidad,
    ) {}

    public static function fromRequest(int|string $prendaId, array $data): self
    {
        return new self(
            prendaId: $prendaId,
            genero: $data['genero'] ?? throw new \InvalidArgumentException('genero requerido'),
            talla: $data['talla'] ?? throw new \InvalidArgumentException('talla requerida'),
            cantidad: (int) ($data['cantidad'] ?? throw new \InvalidArgumentException('cantidad requerida')),
        );
    }
}
