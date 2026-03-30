<?php

namespace App\Application\Pedidos\Exceptions;

final class CrearProduccionPedidoException extends \RuntimeException
{
    public static function fromThrowable(\Throwable $throwable): self
    {
        return new self(
            'Error al crear pedido de produccion: ' . $throwable->getMessage(),
            0,
            $throwable
        );
    }
}
