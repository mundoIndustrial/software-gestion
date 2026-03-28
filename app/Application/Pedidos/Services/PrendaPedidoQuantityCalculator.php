<?php

namespace App\Application\Pedidos\Services;

use App\Domain\Pedidos\Services\PrendaPedidoQuantityCalculatorContract;

class PrendaPedidoQuantityCalculator
{
    public function __construct(private readonly PrendaPedidoQuantityCalculatorContract $service)
    {
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->service->call($name, $arguments);
    }
}
