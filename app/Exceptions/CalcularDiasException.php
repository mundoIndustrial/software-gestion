<?php

namespace App\Exceptions;

/**
 * CalcularDiasException
 * 
 * Excepción para errores en el cálculo de días hábiles
 * Casos: Orden no encontrada, entrada inválida, errores de cálculo
 */
class CalcularDiasException extends RegistroOrdenException
{
    public function __construct(
        string $message = '',
        string $errorCode = 'CALCULAR_DIAS_ERROR',
        int $statusCode = 400,
        array $context = [],
        \Exception $previous = null
    ) {
        parent::__construct($message, $errorCode, $statusCode, $context, $previous);
    }

    /**
     * Excepción cuando el número de pedido es inválido
     */
    public static function numeroPedidoInvalido(): self
    {
        return new self(
            'Número de pedido requerido',
            'NUMERO_PEDIDO_REQUERIDO',
            400
        );
    }

    /**
     * Excepción cuando la orden no existe
     */
    public static function ordenNoEncontrada(string $numeroPedido): self
    {
        return new self(
            "Orden con número '$numeroPedido' no encontrada",
            'ORDEN_NO_ENCONTRADA',
            404,
            ['numero_pedido' => $numeroPedido]
        );
    }

    /**
     * Excepción genérica de cálculo
     */
    public static function errorCalculo(\Exception $original): self
    {
        return new self(
            'Error al calcular días: ' . $original->getMessage(),
            'ERROR_CALCULO_DIAS',
            500,
            ['original_error' => $original->getMessage()],
            $original
        );
    }
}
