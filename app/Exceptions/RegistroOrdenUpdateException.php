<?php

namespace App\Exceptions;

/**
 * RegistroOrdenUpdateException
 * 
 * Se lanza cuando falla la actualización de una orden
 * - Error en actualización de campos
 * - Error en manejo de área/proceso
 * - Error en cálculo de fechas
 * 
 * HTTP 400 Bad Request / 500 Server Error
 */
class RegistroOrdenUpdateException extends RegistroOrdenException
{
    protected $statusCode = 400;

    public function __construct(
        string $message = 'Error al actualizar orden',
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
            'ORDER_UPDATE_ERROR',
            $statusCode,
            $context,
            $previous
        );
    }

    /**
     * Factory para error en actualización de área
     */
    public static function areaUpdateFailed(int $pedido, string $area, string $reason): self
    {
        return new self(
            "Error al actualizar área para pedido #{$pedido}",
            $reason,
            $pedido,
            500,
            ['area' => $area]
        );
    }

    /**
     * Factory para error en cálculo de fechas
     */
    public static function dateCalculationFailed(int $pedido, string $reason): self
    {
        return new self(
            "Error al recalcular fechas para pedido #{$pedido}",
            $reason,
            $pedido,
            500
        );
    }

    /**
     * Factory para error de transacción en actualización
     */
    public static function transactionFailed(int $pedido, string $reason): self
    {
        return new self(
            "Error al procesar actualización de pedido #{$pedido}",
            $reason,
            $pedido,
            500
        );
    }
}
