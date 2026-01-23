<?php

namespace App\Application\Pedidos\DTOs;

/**
 * DTO para agregar imagen a un proceso de prenda
 * 
 * Maneja campos de pedidos_procesos_imagenes:
 * - ruta_original: ruta de la imagen original
 * - ruta_webp: ruta de la imagen en formato WebP
 * - orden: orden en el que se muestran
 * - es_principal: si es la imagen principal del proceso
 */
final class AgregarImagenProcesoDTO
{
    public function __construct(
        public readonly int|string $procesoId,
        public readonly string $rutaOriginal,
        public readonly ?string $rutaWebp = null,
        public readonly int $orden = 1,
        public readonly bool $esPrincipal = false,
    ) {}

    public static function fromRequest(int|string $procesoId, array $data): self
    {
        return new self(
            procesoId: $procesoId,
            rutaOriginal: $data['ruta_original'] ?? throw new \InvalidArgumentException('ruta_original requerida'),
            rutaWebp: $data['ruta_webp'] ?? null,
            orden: (int) ($data['orden'] ?? 1),
            esPrincipal: $data['es_principal'] ?? false,
        );
    }
}

