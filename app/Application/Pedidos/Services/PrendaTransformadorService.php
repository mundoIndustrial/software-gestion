<?php

namespace App\Application\Pedidos\Services;

use App\Domain\Pedidos\Services\PrendaTransformadorServiceContract;

class PrendaTransformadorService
{
    public function __construct(private readonly PrendaTransformadorServiceContract $service)
    {
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->service->call($name, $arguments);
    }
}
