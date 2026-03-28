<?php

namespace App\Domain\Pedidos\UseCases;

interface EliminarImagenPedidoUseCaseContract
{
    public function call(string $method, array $arguments = []): mixed;
}
