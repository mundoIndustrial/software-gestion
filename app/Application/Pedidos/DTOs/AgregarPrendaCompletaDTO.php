<?php

namespace App\Application\Pedidos\DTOs;

/**
 * DTO para agregar prenda completa con fotos
 * 
 * Nota: Solo maneja campos de prendas_pedido + fotos de referencia
 * Para variantes, colores, telas y procesos: crear Use Cases específicos
 */
final class AgregarPrendaCompletaDTO
{
    public function __construct(
        public readonly int|string $pedidoId,
        public readonly string $nombrePrenda,
        public readonly ?string $descripcion = null,
        public readonly bool $deBodega = false,
        public readonly ?array $imagenes = null,
    ) {}

    public static function fromRequest(int|string $pedidoId, array $data, ?array $imagenes = null): self
    {
        return new self(
            pedidoId: $pedidoId,
            nombrePrenda: $data['nombre_prenda'] ?? throw new \InvalidArgumentException('nombre_prenda requerido'),
            descripcion: $data['descripcion'] ?? null,
            deBodega: $data['de_bodega'] ?? false,
            imagenes: $imagenes,
        );
    }
}
