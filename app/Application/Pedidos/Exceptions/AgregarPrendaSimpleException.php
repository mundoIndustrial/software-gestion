<?php

namespace App\Application\Pedidos\Exceptions;

final class AgregarPrendaSimpleException extends \RuntimeException
{
    public static function sinPermiso(): self
    {
        return new self('No tienes permiso para agregar prendas a este pedido');
    }
}
