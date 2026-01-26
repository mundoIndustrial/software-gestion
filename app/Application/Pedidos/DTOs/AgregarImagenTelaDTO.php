<?php

namespace App\Application\Pedidos\DTOs;

/**
 * DTO para agregar imagen de tela a una combinación color-tela
 * 
 * Maneja campos de prenda_fotos_tela_pedido:
 * - ruta_original: ruta de la imagen original
 * - ruta_webp: ruta de la imagen en formato WebP
 * - orden: orden en el que se muestran
 */
final class AgregarImagenTelaDTO
{
    public function __construct(
        public readonly int|string $colorTelaId,
        public readonly string $rutaOriginal,
        public readonly ?string $rutaWebp = null,
        public readonly int $orden = 1,
    ) {}

    public static function fromRequest(int|string $colorTelaId, array $data): self
    {
        return new self(
            colorTelaId: $colorTelaId,
            rutaOriginal: $data['ruta_original'] ?? throw new \InvalidArgumentException('ruta_original requerida'),
            rutaWebp: $data['ruta_webp'] ?? null,
            orden: (int) ($data['orden'] ?? 1),
        );
    }
}

