<?php

namespace App\Application\Cotizacion\Commands;

use App\Application\Cotizacion\DTOs\CrearCotizacionDTO;

/**
 * CrearCotizacionCommand - Comando para crear una cotización
 *
 * Caso de uso: Crear una nueva cotización (borrador o enviada)
 */
final readonly class CrearCotizacionCommand
{
    public function __construct(
        public CrearCotizacionDTO $datos
    ) {
    }

    /**
     * Factory method
     */
    public static function crear(CrearCotizacionDTO $datos): self
    {
        return new self($datos);
    }
}
