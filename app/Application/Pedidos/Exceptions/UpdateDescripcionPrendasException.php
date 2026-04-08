<?php

namespace App\Application\Pedidos\Exceptions;

final class UpdateDescripcionPrendasException extends \RuntimeException
{
    public static function pedidoNoEncontrado(string $pedido): self
    {
        return new self("Pedido {$pedido} no encontrado");
    }
}
