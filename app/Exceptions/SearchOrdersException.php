<?php

namespace App\Exceptions;

use Exception;

/**
 * SearchOrdersException
 * 
 * Excepción personalizada para errores en la búsqueda de órdenes
 * Proporciona contexto específico y manejo centralizado de búsquedas
 */
class SearchOrdersException extends Exception
{
    protected $logContext = [];

    public function __construct(
        string $message = "Error durante búsqueda de órdenes",
        int $code = 0,
        ?Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->logContext = $context;
    }

    /**
     * Obtener contexto para logging
     */
    public function getLogContext(): array
    {
        return $this->logContext;
    }
}
