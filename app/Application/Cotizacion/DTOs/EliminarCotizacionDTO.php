<?php

namespace App\Application\Cotizacion\DTOs;

/**
 * EliminarCotizacionDTO - DTO para eliminar una cotización
 *
 * Datos de entrada para el caso de uso de eliminación
 */
final readonly class EliminarCotizacionDTO
{
    public function __construct(
        public int $cotizacionId,
        public int $usuarioId,
    ) {
    }

    /**
     * Factory method desde array
     */
    public static function desdeArray(array $datos): self
    {
        return new self(
            cotizacionId: (int) $datos['cotizacion_id'] ?? 0,
            usuarioId: (int) $datos['usuario_id'] ?? 0,
        );
    }

    /**
     * Convertir a array
     */
    public function toArray(): array
    {
        return [
            'cotizacion_id' => $this->cotizacionId,
            'usuario_id' => $this->usuarioId,
        ];
    }
}
