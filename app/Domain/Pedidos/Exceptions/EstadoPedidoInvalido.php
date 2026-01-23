<?php

namespace App\Domain\Pedidos\Exceptions;

/**
 * Exception: Estado Pedido InvÃ¡lido
 */
class EstadoPedidoInvalido extends \Exception
{
    public static function transicionNoPermitida(string $estadoActual, string $estadoNuevo): self
    {
        return new self("No se puede pasar de $estadoActual a $estadoNuevo");
    }
}

