<?php

namespace App\Exceptions;

/**
 * GetDescripcionPrendasException
 * 
 * Excepción para errores al obtener descripción de prendas
 * Casos: Orden no encontrada, errores de base de datos
 */
class GetDescripcionPrendasException extends RegistroOrdenException
{
    public function __construct(
        string $message = '',
        string $errorCode = 'GET_DESCRIPCION_PRENDAS_ERROR',
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
     * Excepción cuando la orden no es encontrada
     */
    public static function ordenNoEncontrada(string $pedido): self
    {
        return new self(
            "Pedido con identificador '$pedido' no encontrado",
            'ORDEN_NO_ENCONTRADA',
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
            'Error al obtener descripción de prendas',
            'ERROR_CONSULTA_DESCRIPCION',
            500,
            ['original_error' => $original->getMessage()],
            $original
        );
    }
}
