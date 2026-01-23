<?php

namespace App\Application\Pedidos\DTOs;

class ObtenerProximoNumeroPedidoDTO
{
    public static function crear(): self
    {
        return new self();
    }
}
