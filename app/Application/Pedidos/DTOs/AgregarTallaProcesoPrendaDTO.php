<?php

namespace App\Application\Pedidos\DTOs;

/**
 * DTO para agregar talla a un proceso de prenda
 * 
 * Maneja campos de pedidos_procesos_prenda_tallas:
 * - genero: 'DAMA', 'CABALLERO', 'UNISEX'
 * - talla: 'S', 'M', 'L', 'XL', etc
 * - cantidad: número de prendas en esta talla
 */
final class AgregarTallaProcesoPrendaDTO
{
    public function __construct(
        public readonly int|string $procesoId,
        public readonly string $genero,
        public readonly string $talla,
        public readonly int $cantidad,
    ) {}

    public static function fromRequest(int|string $procesoId, array $data): self
    {
        return new self(
            procesoId: $procesoId,
            genero: $data['genero'] ?? throw new \InvalidArgumentException('genero requerido'),
            talla: $data['talla'] ?? throw new \InvalidArgumentException('talla requerida'),
            cantidad: (int) ($data['cantidad'] ?? throw new \InvalidArgumentException('cantidad requerida')),
        );
    }
}
