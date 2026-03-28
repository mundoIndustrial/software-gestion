<?php

namespace App\Domain\Pedidos\UseCases;

interface ObtenerProcesosPorPedidoUseCaseContract
{
    public function call(string $method, array $arguments = []): mixed;
}
