<?php

namespace App\Exceptions;

use Exception;

/**
 * RegistroOrdenException
 * 
 * Excepción base para todas las operaciones de órdenes
 * Cumple con DRY y permite manejo centralizado
 */
class RegistroOrdenException extends Exception
{
    protected $statusCode = 500;
    protected $errorCode;
    protected $context = [];

    public function __construct(
        string $message = '',
        string $errorCode = 'REGISTRO_ORDEN_ERROR',
        int $statusCode = 500,
        array $context = [],
        Exception $previous = null
    ) {
        $this->errorCode = $errorCode;
        $this->statusCode = $statusCode;
        $this->context = $context;

        parent::__construct($message, 0, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getJsonResponse(): array
    {
        return [
            'success' => false,
            'error_code' => $this->errorCode,
            'message' => $this->message,
            'context' => $this->context,
            'timestamp' => now()->toIso8601String()
        ];
    }
}
