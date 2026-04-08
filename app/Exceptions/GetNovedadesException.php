<?php

namespace App\Exceptions;

/**
 * GetNovedadesException
 * 
 * Excepción para errores en la obtención de novedades de un pedido
 * Casos: Pedido no encontrado, errores de base de datos
 */
class GetNovedadesException extends RegistroOrdenException
{
    public function __construct(
        string $message = '',
        string $errorCode = 'GET_NOVEDADES_ERROR',
        int $statusCode = 400,
        array $context = [],
        \Exception $previous = null
    ) {
        parent::__construct($message, $errorCode, $statusCode, $context, $previous);
    }

    /**
     * Excepción cuando el número de pedido es inválido o vacío
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
     * Excepción cuando el pedido no es encontrado
     */
    public static function pedidoNoEncontrado(string $numeroPedido): self
    {
        return new self(
            "Pedido con número '$numeroPedido' no encontrado",
            'PEDIDO_NO_ENCONTRADO',
            404,
            ['numero_pedido' => $numeroPedido]
        );
    }

    /**
     * Excepción genérica de base de datos
     */
    public static function errorConsulta(\Exception $original): self
    {
        return new self(
            'Error al obtener novedades del pedido',
            'ERROR_CONSULTA_NOVEDADES',
            500,
            ['original_error' => $original->getMessage()],
            $original
        );
    }
}
