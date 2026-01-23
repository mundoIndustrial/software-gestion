<?php

namespace App\Application\Pedidos\DTOs;

/**
 * DTO para agregar combinación color-tela a una prenda
 * 
 * Maneja campos de prenda_pedido_colores_telas:
 * - color_id: referencia a colores_prenda
 * - tela_id: referencia a telas_prenda
 */
final class AgregarColorTelaDTO
{
    public function __construct(
        public readonly int|string $prendaId,
        public readonly int $colorId,
        public readonly int $telaId,
    ) {}

    public static function fromRequest(int|string $prendaId, array $data): self
    {
        return new self(
            prendaId: $prendaId,
            colorId: $data['color_id'] ?? throw new \InvalidArgumentException('color_id requerido'),
            telaId: $data['tela_id'] ?? throw new \InvalidArgumentException('tela_id requerido'),
        );
    }
}
