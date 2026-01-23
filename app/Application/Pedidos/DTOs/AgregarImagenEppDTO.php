<?php

namespace App\Application\Pedidos\DTOs;

/**
 * DTO para agregar imagen a un EPP
 * 
 * Maneja campos de pedido_epp_imagenes:
 * - ruta_original: ruta de la imagen original
 * - ruta_web: ruta de la imagen para web
 * - principal: si es la imagen principal
 * - orden: orden en el que se muestran
 */
final class AgregarImagenEppDTO
{
    public function __construct(
        public readonly int|string $eppId,
        public readonly string $rutaOriginal,
        public readonly ?string $rutaWeb = null,
        public readonly bool $principal = false,
        public readonly int $orden = 1,
    ) {}

    public static function fromRequest(int|string $eppId, array $data): self
    {
        return new self(
            eppId: $eppId,
            rutaOriginal: $data['ruta_original'] ?? throw new \InvalidArgumentException('ruta_original requerida'),
            rutaWeb: $data['ruta_web'] ?? null,
            principal: $data['principal'] ?? false,
            orden: (int) ($data['orden'] ?? 1),
        );
    }
}

