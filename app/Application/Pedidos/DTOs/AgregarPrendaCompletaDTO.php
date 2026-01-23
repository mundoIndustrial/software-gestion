<?php

namespace App\Application\Pedidos\DTOs;

/**
 * DTO para agregar prenda completa con fotos y tallas
 * 
 * Maneja campos de prendas_pedido + fotos + tallas
 * - nombre_prenda: nombre de la prenda
 * - descripcion: descripción de la prenda
 * - de_bodega: si viene de bodega
 * - imagenes: array de rutas de fotos
 * - tallas: array de tallas para poblar prenda_pedido_tallas
 *   Estructura: [{ genero: 'DAMA'|'CABALLERO'|'UNISEX', talla: 'S'|'M'|'L'|'XL', cantidad: 5 }, ...]
 */
final class AgregarPrendaCompletaDTO
{
    public function __construct(
        public readonly int|string $pedidoId,
        public readonly string $nombre_prenda,
        public readonly ?string $descripcion = null,
        public readonly bool $de_bodega = false,
        public readonly ?array $imagenes = null,
        public readonly ?array $tallas = null,
    ) {}

    public static function fromRequest(int|string $pedidoId, array $data, ?array $imagenes = null): self
    {
        return new self(
            pedidoId: $pedidoId,
            nombre_prenda: $data['nombre_prenda'] ?? throw new \InvalidArgumentException('nombre_prenda requerido'),
            descripcion: $data['descripcion'] ?? null,
            de_bodega: $data['de_bodega'] ?? false,
            imagenes: $imagenes,
            tallas: $data['tallas'] ?? null,
        );
    }
}
