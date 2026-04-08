<?php

namespace App\Application\Pedidos\Exceptions;

final class ObtenerDatosEdicionException extends \RuntimeException
{
    public static function fromThrowable(\Throwable $throwable): self
    {
        return new self(
            'Error al obtener datos de edición: ' . $throwable->getMessage(),
            0,
            $throwable
        );
    }
}

