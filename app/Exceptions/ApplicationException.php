<?php

namespace App\Exceptions;

use Illuminate\Http\Response;

/**
 * ApplicationException - Excepción para errores generales de aplicación
 * 
 * Se lanza cuando ocurre un error no previsto durante la ejecución de la lógica de negocio.
 */
class ApplicationException extends DomainException
{
    protected string $errorCode = 'APPLICATION_ERROR';
    protected int $httpStatusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

    public function __construct(
        string $message = 'Ocurrió un error durante la operación',
        string $operation = '',
        ?string $errorCode = null,
        array $context = []
    ) {
        parent::__construct(
            $message,
            $errorCode ?? 'APPLICATION_ERROR',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            array_merge($context, [
                'operation' => $operation,
            ])
        );
    }
}
