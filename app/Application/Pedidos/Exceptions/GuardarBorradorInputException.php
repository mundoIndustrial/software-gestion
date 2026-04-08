<?php

namespace App\Application\Pedidos\Exceptions;

final class GuardarBorradorInputException extends \InvalidArgumentException
{
    public static function campoPedidoRequerido(): self
    {
        return new self('Campo "pedido" JSON requerido');
    }

    public static function jsonInvalido(): self
    {
        return new self('JSON inválido en campo "pedido"');
    }
}
