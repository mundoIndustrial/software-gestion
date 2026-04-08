<?php

namespace App\Exceptions;

/**
 * CalcularDiasBatchException
 * 
 * Excepción para errores en el cálculo batch de días hábiles
 * Casos: Lista vacía, órdenes no encontradas, errores de cálculo
 */
class CalcularDiasBatchException extends RegistroOrdenException
{
    public function __construct(
        string $message = '',
        string $errorCode = 'CALCULAR_DIAS_BATCH_ERROR',
        int $statusCode = 400,
        array $context = [],
        \Exception $previous = null
    ) {
        parent::__construct($message, $errorCode, $statusCode, $context, $previous);
    }

    /**
     * Excepción cuando la lista de pedidos está vacía
     */
    public static function listaPedidosVacia(): self
    {
        return new self(
            'Lista de números de pedido requerida',
            'LISTA_PEDIDOS_VACIA',
            400
        );
    }

    /**
     * Excepción cuando ninguna orden es encontrada
     */
    public static function ordenesNoEncontradas(array $numeroPedidos): self
    {
        return new self(
            'No se encontraron órdenes para los números de pedido proporcionados',
            'ORDENES_NO_ENCONTRADAS',
            404,
            ['pedidos_solicitados' => count($numeroPedidos)]
        );
    }

    /**
     * Excepción genérica de cálculo
     */
    public static function errorCalculo(\Exception $original): self
    {
        return new self(
            'Error al calcular días para el lote de pedidos',
            'ERROR_CALCULO_DIAS_BATCH',
            500,
            ['original_error' => $original->getMessage()],
            $original
        );
    }
}
