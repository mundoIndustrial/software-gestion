<?php

namespace App\Exceptions;

/**
 * RegistroOrdenCreationException
 * 
 * Se lanza cuando falla la creación de una orden
 * - Error de transacción
 * - Error al crear prendas
 * - Error de datos inválidos en creación
 * 
 * HTTP 400 Bad Request / 500 Server Error
 */
class RegistroOrdenCreationException extends RegistroOrdenException
{
    protected $statusCode = 400;

    public function __construct(
        string $message = 'Error al crear orden',
        string $reason = '',
        int $statusCode = 400,
        array $context = [],
        Exception $previous = null
    ) {
        $context['reason'] = $reason;
        
        parent::__construct(
            $message,
            'ORDER_CREATION_ERROR',
            $statusCode,
            $context,
            $previous
        );
    }

    /**
     * Factory para error de transacción
     */
    public static function transactionFailed(string $reason): self
    {
        return new self(
            'Error al procesar la creación de orden',
            $reason,
            500
        );
    }

    /**
     * Factory para error al crear prendas
     */
    public static function prendasCreationFailed(int $pedido, string $reason): self
    {
        return new self(
            "Error al crear prendas para pedido #{$pedido}",
            $reason,
            500,
            ['pedido' => $pedido]
        );
    }
}
