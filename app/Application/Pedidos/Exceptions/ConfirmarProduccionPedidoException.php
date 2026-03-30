<?php

namespace App\Application\Pedidos\Exceptions;

final class ConfirmarProduccionPedidoException extends \RuntimeException
{
    public static function fromThrowable(\Throwable $throwable): self
    {
        return new self(
            'Error al confirmar pedido: ' . $throwable->getMessage(),
            0,
            $throwable
        );
    }
}
