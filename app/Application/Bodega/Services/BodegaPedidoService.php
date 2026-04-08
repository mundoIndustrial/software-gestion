<?php

namespace App\Application\Bodega\Services;

use App\Domain\Bodega\Services\BodegaPedidoServiceContract;

class BodegaPedidoService
{
    public function __construct(private readonly BodegaPedidoServiceContract $service)
    {
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->service->call($name, $arguments);
    }
}
