<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * RegistroOrdenNotFoundException
 * 
 * Se lanza cuando una orden no existe
 * HTTP 404 Not Found
 */
class RegistroOrdenNotFoundException extends RegistroOrdenException
{
    protected $statusCode = 404;

    public function __construct(
        string $pedido = '',
        array $context = [],
        ?Exception $previous = null
    ) {
        $context['pedido'] = $pedido;
        
        parent::__construct(
            "Orden #{$pedido} no encontrada",
            'ORDER_NOT_FOUND',
            404,
            $context,
            $previous
        );
    }

    /**
     * Factory method para crear desde ModelNotFoundException
     */
    public static function fromModelNotFound(string $pedido, ModelNotFoundException $e): self
    {
        return new self($pedido, ['original_exception' => get_class($e)], $e);
    }
}
