<?php

namespace App\Exceptions;

use Exception;

/**
 * FilterOrdersException
 * 
 * Excepción personalizada para errores en el filtrado de órdenes
 * Proporciona contexto específico y manejo centralizado de filtrados
 */
class FilterOrdersException extends Exception
{
    protected $logContext = [];

    public function __construct(
        string $message = "Error durante filtrado de órdenes",
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
