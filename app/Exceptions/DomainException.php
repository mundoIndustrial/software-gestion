<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * DomainException - Excepción base para excepciones del dominio
 * 
 * Todas las excepciones de negocio heredan de esta clase.
 * Proporciona métodos convenientes para respuestas JSON y HTTP.
 */
class DomainException extends Exception
{
    protected string $errorCode = 'DOMAIN_ERROR';
    protected int $httpStatusCode = Response::HTTP_BAD_REQUEST;
    protected array $context = [];

    public function __construct(
        string $message = '',
        string $errorCode = '',
        int $httpStatusCode = 0,
        array $context = [],
        ?Exception $previous = null
    ) {
        parent::__construct($message, 0, $previous);

        if ($errorCode) {
            $this->errorCode = $errorCode;
        }

        if ($httpStatusCode) {
            $this->httpStatusCode = $httpStatusCode;
        }

        $this->context = $context;
    }

    /**
     * Obtiene el código de error del dominio
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Obtiene el código HTTP de estado
     */
    public function getStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    /**
     * Obtiene contexto adicional del error
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Convierte la excepción a array para respuesta JSON
     */
    public function toArray(): array
    {
        return [
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
            'context' => $this->context,
        ];
    }

    /**
     * Devuelve una respuesta JSON formateada
     */
    public function getJsonResponse(): array
    {
        return $this->toArray();
    }
}
