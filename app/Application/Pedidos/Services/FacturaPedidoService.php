<?php

namespace App\Application\Pedidos\Services;

use App\Domain\Pedidos\Services\FacturaPedidoServiceContract;

class FacturaPedidoService
{
    public function __construct(private readonly FacturaPedidoServiceContract $service)
    {
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->service->call($name, $arguments);
    }
}
