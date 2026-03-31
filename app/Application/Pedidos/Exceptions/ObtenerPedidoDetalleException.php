<?php

namespace App\Application\Pedidos\Exceptions;

final class ObtenerPedidoDetalleException extends \RuntimeException
{
    public static function sinPermiso(): self
    {
        return new self('No tienes permiso para ver este pedido', 403);
    }

    public static function pedidoNoEncontrado(): self
    {
        return new self('Pedido no encontrado', 404);
    }
}
