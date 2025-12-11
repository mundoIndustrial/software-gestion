<?php

namespace App\Application\Commands;

/**
 * EnviarCotizacionCommand
 *
 * Application Command para enviar una cotización.
 * Encapsula la intención de enviar una cotización con todos sus datos.
 *
 * Responsabilidad: Transportar los datos necesarios para enviar una cotización
 */
class EnviarCotizacionCommand
{
    /**
     * ID de la cotización a enviar
     */
    public int $cotizacionId;

    /**
     * ID del tipo de cotización
     */
    public int $tipoCotizacionId;

    /**
     * Constructor
     */
    public function __construct(int $cotizacionId, int $tipoCotizacionId)
    {
        $this->cotizacionId = $cotizacionId;
        $this->tipoCotizacionId = $tipoCotizacionId;
    }
}
