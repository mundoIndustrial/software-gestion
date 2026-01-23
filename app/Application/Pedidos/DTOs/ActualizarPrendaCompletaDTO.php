<?php

namespace App\Application\Pedidos\DTOs;

/**
 * DTO para actualizar prenda y fotos
 * 
 * Nota: Solo maneja campos de prendas_pedido + fotos de referencia
 * Para variantes, colores, telas y procesos: usar Use Cases específicos
 */
final class ActualizarPrendaCompletaDTO
{
    public function __construct(
        public readonly int|string $prendaId,
        public readonly ?string $nombrePrenda = null,
        public readonly ?string $descripcion = null,
        public readonly ?bool $deBodega = null,
        public readonly ?array $imagenes = null,
    ) {}

    public static function fromRequest(int|string $prendaId, array $data, ?array $imagenes = null): self
    {
        return new self(
            prendaId: $prendaId,
            nombrePrenda: $data['nombre_prenda'] ?? null,
            descripcion: $data['descripcion'] ?? null,
            deBodega: isset($data['de_bodega']) ? (bool) $data['de_bodega'] : null,
            imagenes: $imagenes,
        );
    }
}
