<?php

namespace App\Application\Cotizacion\Commands;

use App\Application\Cotizacion\DTOs\ActualizarCotizacionDTO;

/**
 * ActualizarCotizacionCommand - Comando para actualizar una cotización
 *
 * Caso de uso: Actualizar datos de una cotización existente
 */
final readonly class ActualizarCotizacionCommand
{
    public function __construct(
        public ActualizarCotizacionDTO $datos
    ) {
    }

    /**
     * Factory method
     */
    public static function crear(ActualizarCotizacionDTO $datos): self
    {
        return new self($datos);
    }
}
