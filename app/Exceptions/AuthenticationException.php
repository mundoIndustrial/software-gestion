<?php

namespace App\Exceptions;

use Illuminate\Http\Response;

/**
 * AuthenticationException - Excepción cuando el usuario no está autenticado
 * 
 * Se lanza cuando se intenta acceder a un recurso sin estar autenticado.
 */
class AuthenticationException extends DomainException
{
    protected string $errorCode = 'AUTHENTICATION_FAILED';
    protected int $httpStatusCode = Response::HTTP_UNAUTHORIZED;

    public function __construct(
        string $message = 'Debe iniciar sesión para continuar',
        string $reason = '',
        array $context = []
    ) {
        parent::__construct(
            $message,
            'AUTHENTICATION_FAILED',
            Response::HTTP_UNAUTHORIZED,
            array_merge($context, [
                'reason' => $reason,
            ])
        );
    }
}
