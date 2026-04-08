<?php

namespace App\Exceptions;

use Illuminate\Http\Response;

/**
 * UnauthorizedException - Excepción cuando el usuario no tiene permisos
 * 
 * Se lanza cuando el usuario no tiene permisos para realizar la acción.
 */
class UnauthorizedException extends DomainException
{
    protected string $errorCode = 'UNAUTHORIZED';
    protected int $httpStatusCode = Response::HTTP_FORBIDDEN;

    public function __construct(
        string $message = 'No tienes permisos para realizar esta acción',
        string $action = '',
        array $context = []
    ) {
        parent::__construct(
            $message,
            'UNAUTHORIZED',
            Response::HTTP_FORBIDDEN,
            array_merge($context, [
                'action' => $action,
            ])
        );
    }
}
