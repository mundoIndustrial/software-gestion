<?php

namespace App\Exceptions;

/**
 * CalcularFechaEstimadaException
 * 
 * Excepción para errores al calcular fecha estimada de entrega
 * Casos: Orden no encontrada, día inválido, fecha de creación faltante, errores de cálculo
 */
class CalcularFechaEstimadaException extends RegistroOrdenException
{
    public function __construct(
        string $message = '',
        string $errorCode = 'CALCULAR_FECHA_ESTIMADA_ERROR',
        int $statusCode = 400,
        array $context = [],
        \Exception $previous = null
    ) {
        parent::__construct($message, $errorCode, $statusCode, $context, $previous);
    }

    /**
     * Excepción cuando el día de entrega es inválido
     */
    public static function diaInvalido(int $dia): self
    {
        return new self(
            'Día de entrega debe ser mayor o igual a 1',
            'DIA_ENTREGA_INVALIDO',
            400,
            ['dia_proporcionado' => $dia]
        );
    }

    /**
     * Excepción cuando la orden no es encontrada
     */
    public static function ordenNoEncontrada(int $orderId): self
    {
        return new self(
            "Orden con ID $orderId no encontrada",
            'ORDEN_NO_ENCONTRADA',
            404,
            ['orden_id' => $orderId]
        );
    }

    /**
     * Excepción cuando la orden no tiene fecha de creación
     */
    public static function sinFechaCreacion(int $orderId): self
    {
        return new self(
            'La orden no tiene fecha de creación',
            'SIN_FECHA_CREACION',
            400,
            ['orden_id' => $orderId]
        );
    }

    /**
     * Excepción cuando el cálculo de fecha falla
     */
    public static function errorCalculo(int $orderId): self
    {
        return new self(
            'No se pudo calcular la fecha estimada',
            'ERROR_CALCULO_FECHA',
            500,
            ['orden_id' => $orderId]
        );
    }

    /**
     * Excepción genérica
     */
    public static function errorConsulta(\Exception $original): self
    {
        return new self(
            'Error al calcular fecha estimada',
            'ERROR_CONSULTA_FECHA',
            500,
            ['original_error' => $original->getMessage()],
            $original
        );
    }
}
