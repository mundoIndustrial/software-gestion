<?php

namespace App\Application\Cotizacion\Commands;

use App\Application\Cotizacion\DTOs\CrearReflectivoCotizacionDTO;

/**
 * CrearReflectivoCotizacionCommand - Command para crear un reflectivo
 *
 * Este comando encapsula la intención de crear un nuevo reflectivo
 * en una cotización existente.
 */
final class CrearReflectivoCotizacionCommand
{
    public function __construct(
        public readonly CrearReflectivoCotizacionDTO $dto
    ) {
    }

    /**
     * Factory method para crear comando desde array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            CrearReflectivoCotizacionDTO::fromArray($data)
        );
    }
}
