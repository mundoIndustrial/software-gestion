<?php

namespace App\Domain\Pedidos\UseCases;

interface PrepararCreacionProduccionPedidoUseCaseContract
{
    public function call(string $method, array $arguments = []): mixed;
}
