<?php

namespace App\Domain\Pedidos\Exceptions;

/**
 * Exception: Pedido No Encontrado
 */
class PedidoNoEncontrado extends \Exception
{
    public static function conId(int $id): self
    {
        return new self("Pedido con ID $id no encontrado");
    }

    public static function conNumero(string $numero): self
    {
        return new self("Pedido con nÃºmero $numero no encontrado");
    }
}

