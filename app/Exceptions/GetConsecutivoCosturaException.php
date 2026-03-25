<?php

namespace App\Exceptions;

/**
 * GetConsecutivoCosturaException
 * 
 * Excepción para errores al obtener consecutivo de costura
 * Casos: Pedido no encontrado, datos incompletos, errores de consulta
 */
class GetConsecutivoCosturaException extends RegistroOrdenException
{
    public function __construct(
        string $message = '',
        string $errorCode = 'GET_CONSECUTIVO_COSTURA_ERROR',
        int $statusCode = 400,
        array $context = [],
        \Exception $previous = null
    ) {
        parent::__construct($message, $errorCode, $statusCode, $context, $previous);
    }

    /**
     * Excepción cuando el pedido es inválido o vacío
     */
    public static function pedidoInvalido(): self
    {
        return new self(
            'Identificador de pedido requerido',
            'PEDIDO_REQUERIDO',
            400
        );
    }

    /**
     * Excepción cuando el pedido no es encontrado
     */
    public static function pedidoNoEncontrado(string $pedido): self
    {
        return new self(
            "Pedido con identificador '$pedido' no encontrado",
            'PEDIDO_NO_ENCONTRADO',
            404,
            ['pedido' => $pedido]
        );
    }

    /**
     * Excepción cuando no hay datos para este pedido
     */
    public static function sinDatos(string $pedido): self
    {
        return new self(
            'No se encontraron datos de costura para este pedido',
            'SIN_DATOS_COSTURA',
            404,
            ['pedido' => $pedido]
        );
    }

    /**
     * Excepción genérica de consulta
     */
    public static function errorConsulta(\Exception $original): self
    {
        return new self(
            'Error al obtener consecutivo de costura',
            'ERROR_CONSULTA_CONSECUTIVO',
            500,
            ['original_error' => $original->getMessage()],
            $original
        );
    }
}
