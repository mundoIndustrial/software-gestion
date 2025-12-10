<?php

namespace App\Application\Cotizacion\Queries;

/**
 * ObtenerCotizacionQuery - Query para obtener una cotización
 *
 * Caso de uso: Obtener los detalles de una cotización específica
 */
final readonly class ObtenerCotizacionQuery
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
