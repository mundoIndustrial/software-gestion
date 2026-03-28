<?php

namespace App\Application\Pedidos\Services;

use App\Domain\Pedidos\Services\ReciboPedidoServiceContract;

class ReciboPedidoService
{
    public function __construct(private readonly ReciboPedidoServiceContract $service)
    {
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->service->call($name, $arguments);
    }
}
