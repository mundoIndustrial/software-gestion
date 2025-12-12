<?php

namespace App\Application\Cotizacion\DTOs;

/**
 * CrearReflectivoCotizacionDTO - Data Transfer Object para crear reflectivo
 *
 * Contiene los datos necesarios para crear un reflectivo en una cotización
 */
final class CrearReflectivoCotizacionDTO
{
    public function __construct(
        public readonly int $cotizacionId,
        public readonly string $descripcion,
        public readonly string $ubicacion = '',
        public readonly array $imagenes = [],
        public readonly array $observacionesGenerales = []
    ) {
    }

    /**
     * Crear DTO desde array (típicamente desde request)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            cotizacionId: (int) $data['cotizacion_id'] ?? 0,
            descripcion: (string) $data['descripcion'] ?? '',
            ubicacion: (string) $data['ubicacion'] ?? '',
            imagenes: (array) $data['imagenes'] ?? [],
            observacionesGenerales: (array) $data['observaciones_generales'] ?? []
        );
    }

    /**
     * Convertir a array
     */
    public function toArray(): array
    {
        return [
            'cotizacion_id' => $this->cotizacionId,
            'descripcion' => $this->descripcion,
            'ubicacion' => $this->ubicacion,
            'imagenes' => $this->imagenes,
            'observaciones_generales' => $this->observacionesGenerales,
        ];
    }
}
