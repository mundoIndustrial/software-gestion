<?php

namespace App\Application\Cotizacion\Commands;

/**
 * CambiarEstadoCotizacionCommand - Comando para cambiar estado de cotización
 *
 * Caso de uso: Cambiar estado de una cotización
 */
final readonly class CambiarEstadoCotizacionCommand
{
    public function __construct(
        public int $cotizacionId,
        public string $nuevoEstado,
        public int $usuarioId
    ) {
    }

    /**
     * Factory method
     */
    public static function crear(int $cotizacionId, string $nuevoEstado, int $usuarioId): self
    {
        return new self($cotizacionId, $nuevoEstado, $usuarioId);
    }
}
