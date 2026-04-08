<?php

namespace App\Domain\Pedidos\UseCases;

interface CambiarEstadoPedidoUseCaseContract
{
    public function call(string $method, array $arguments = []): mixed;
}
