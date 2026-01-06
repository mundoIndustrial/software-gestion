<?php

namespace App\DTOs\LogoCotizacion;

/**
 * AgregarPrendaTecnicaDTO - Data Transfer Object para agregar prenda a técnica
 */
final class AgregarPrendaTecnicaDTO
{
    public function __construct(
        public readonly int $logoCotizacionTecnicaId,
        public readonly string $nombrePrenda,
        public readonly string $descripcion,
        public readonly array $ubicaciones,
        public readonly ?array $tallas = null,
        public readonly int $cantidad = 1,
        public readonly ?string $especificaciones = null,
        public readonly ?string $colorHilo = null,
        public readonly ?int $puntosEstimados = null,
    ) {
    }

    public static function fromRequest(array $data): self
    {
        return new self(
            logoCotizacionTecnicaId: (int) $data['logo_cotizacion_tecnica_id'],
            nombrePrenda: $data['nombre_prenda'],
            descripcion: $data['descripcion'],
            ubicaciones: $data['ubicaciones'] ?? [],
            tallas: $data['tallas'] ?? null,
            cantidad: (int) ($data['cantidad'] ?? 1),
            especificaciones: $data['especificaciones'] ?? null,
            colorHilo: $data['color_hilo'] ?? null,
            puntosEstimados: isset($data['puntos_estimados']) ? (int) $data['puntos_estimados'] : null,
        );
    }
}
