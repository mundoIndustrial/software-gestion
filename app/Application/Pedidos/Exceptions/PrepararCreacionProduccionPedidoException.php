<?php

namespace App\Application\Pedidos\Exceptions;

final class PrepararCreacionProduccionPedidoException extends \RuntimeException
{
    public static function sinPermisoEditarCotizacion(): self
    {
        return new self('No tienes permiso para editar esta cotizacion');
    }

    public static function sinPermisoEditarBorrador(): self
    {
        return new self('No tienes permiso para editar este borrador');
    }
}

