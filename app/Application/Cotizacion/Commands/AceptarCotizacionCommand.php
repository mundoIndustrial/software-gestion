<?php

namespace App\Application\Cotizacion\Commands;

/**
 * AceptarCotizacionCommand - Comando para aceptar una cotización
 *
 * Caso de uso: Aceptar una cotización (cliente acepta)
 */
final readonly class AceptarCotizacionCommand
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
