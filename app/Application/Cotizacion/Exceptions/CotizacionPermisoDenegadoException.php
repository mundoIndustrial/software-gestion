<?php

namespace App\Application\Cotizacion\Exceptions;

use Exception;

class CotizacionPermisoDenegadoException extends Exception
{
    public function __construct(int $cotizacionId, int $asesorId)
    {
        $message = "El asesor {$asesorId} no tiene permiso para editar la cotización {$cotizacionId}";
        parent::__construct($message, 403);
    }
}
