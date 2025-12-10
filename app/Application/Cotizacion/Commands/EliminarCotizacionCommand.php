<?php

namespace App\Application\Cotizacion\Commands;

/**
 * EliminarCotizacionCommand - Comando para eliminar una cotización
 *
 * Caso de uso: Eliminar una cotización (solo borradores)
 */
final readonly class EliminarCotizacionCommand
{
    public function __construct(
        public int $cotizacionId,
        public int $usuarioId
    ) {
    }

    /**
     * Factory method
     */
    public static function crear(int $cotizacionId, int $usuarioId): self
    {
        return new self($cotizacionId, $usuarioId);
    }
}
