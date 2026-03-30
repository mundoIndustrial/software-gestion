<?php

namespace App\Application\Pedidos\Exceptions;

final class ActualizarProduccionPedidoException extends \RuntimeException
{
    public static function pedidoNoEncontrado(int $pedidoId): self
    {
        return new self("Pedido con ID {$pedidoId} no encontrado");
    }

    public static function estadoNoPermitido(string $estadoActual): self
    {
        return new self(
            "No se puede actualizar un pedido en estado '{$estadoActual}'. Solo se pueden actualizar pedidos pendientes."
        );
    }

    public static function fromThrowable(\Throwable $throwable): self
    {
        return new self(
            'Error al actualizar pedido: ' . $throwable->getMessage(),
            0,
            $throwable
        );
    }
}
