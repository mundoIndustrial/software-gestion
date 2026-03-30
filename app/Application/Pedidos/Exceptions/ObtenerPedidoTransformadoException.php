<?php

namespace App\Application\Pedidos\Exceptions;

final class ObtenerPedidoTransformadoException extends \RuntimeException
{
    public static function inesperado(\Throwable $throwable): self
    {
        return new self(
            'Error al obtener y transformar pedido: ' . $throwable->getMessage(),
            0,
            $throwable
        );
    }
}
