<?php

namespace App\Application\Pedidos\Despacho\Services;

use App\Domain\Pedidos\Despacho\Services\DespachoEstadoServiceContract;

class DespachoEstadoService
{
    public function __construct(private readonly DespachoEstadoServiceContract $service)
    {
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->service->call($name, $arguments);
    }
}
