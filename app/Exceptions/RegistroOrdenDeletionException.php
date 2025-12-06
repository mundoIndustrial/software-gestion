<?php

namespace App\Exceptions;

/**
 * RegistroOrdenDeletionException
 * 
 * Se lanza cuando falla la eliminación de una orden
 * - Error al eliminar prendas
 * - Error de cascada
 * - Error de caché
 * 
 * HTTP 400 Bad Request / 500 Server Error
 */
class RegistroOrdenDeletionException extends RegistroOrdenException
{
    protected $statusCode = 400;

    public function __construct(
        string $message = 'Error al eliminar orden',
        string $reason = '',
        int $pedido = 0,
        int $statusCode = 400,
        array $context = [],
        Exception $previous = null
    ) {
        $context['reason'] = $reason;
        $context['pedido'] = $pedido;
        
        parent::__construct(
            $message,
            'ORDER_DELETION_ERROR',
            $statusCode,
            $context,
            $previous
        );
    }

    /**
     * Factory para error en cascada de eliminación
     */
    public static function cascadeFailed(int $pedido, string $reason): self
    {
        return new self(
            "Error al eliminar prendas y procesos asociados a pedido #{$pedido}",
            $reason,
            $pedido,
            500
        );
    }

    /**
     * Factory para error de transacción
     */
    public static function transactionFailed(int $pedido, string $reason): self
    {
        return new self(
            "Error al procesar eliminación de pedido #{$pedido}",
            $reason,
            $pedido,
            500
        );
    }
}
