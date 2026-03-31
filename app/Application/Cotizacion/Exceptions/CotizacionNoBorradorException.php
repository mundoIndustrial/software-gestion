<?php

namespace App\Application\Cotizacion\Exceptions;

use Exception;

class CotizacionNoBorradorException extends Exception
{
    public function __construct(int $cotizacionId)
    {
        $message = "La cotización {$cotizacionId} no es un borrador y no puede ser editada";
        parent::__construct($message, 422);
    }
}
