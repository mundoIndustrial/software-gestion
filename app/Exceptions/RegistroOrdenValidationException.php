<?php

namespace App\Exceptions;

/**
 * RegistroOrdenValidationException
 * 
 * Se lanza cuando los datos de entrada no pasan validación
 * HTTP 422 Unprocessable Entity
 */
class RegistroOrdenValidationException extends RegistroOrdenException
{
    protected $statusCode = 422;

    public function __construct(
        string $message = 'Datos de validación inválidos',
        array $errors = [],
        array $context = [],
        Exception $previous = null
    ) {
        $context['validation_errors'] = $errors;
        
        parent::__construct(
            $message,
            'VALIDATION_ERROR',
            422,
            $context,
            $previous
        );
    }
}
