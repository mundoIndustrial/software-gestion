<?php

namespace App\Application\Pedidos\Services;

use App\Domain\Pedidos\Services\EppTransformadorServiceContract;

class EppTransformadorService
{
    public function __construct(private readonly EppTransformadorServiceContract $service)
    {
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->service->call($name, $arguments);
    }
}
