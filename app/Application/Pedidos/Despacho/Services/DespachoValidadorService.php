<?php

namespace App\Application\Pedidos\Despacho\Services;

use App\Domain\Pedidos\Despacho\Services\DespachoValidadorServiceContract;

class DespachoValidadorService
{
    public function __construct(private readonly DespachoValidadorServiceContract $service)
    {
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->service->call($name, $arguments);
    }
}
