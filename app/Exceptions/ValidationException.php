<?php

namespace App\Exceptions;

use Illuminate\Http\Response;

/**
 * ValidationException - Excepción para errores de validación
 * 
 * Se lanza cuando los datos de entrada no cumplen con las reglas de validación.
 */
class ValidationException extends DomainException
{
    protected string $errorCode = 'VALIDATION_ERROR';
    protected int $httpStatusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
    protected array $errors = [];

    public function __construct(
        string $message = 'Datos de validación inválidos',
        array $errors = [],
        ?string $errorCode = null,
        array $context = []
    ) {
        $this->errors = $errors;

        parent::__construct(
            $message,
            $errorCode ?? 'VALIDATION_ERROR',
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $context
        );
    }

    /**
     * Obtiene los errores de validación
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Convierte a array para respuesta JSON
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'errors' => $this->errors,
        ]);
    }
}
