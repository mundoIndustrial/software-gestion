<?php

namespace App\Application\Pedidos\Services;

use App\Domain\Pedidos\Services\PrendaTransformerServiceContract;

class PrendaTransformerService
{
    public function __construct(private readonly PrendaTransformerServiceContract $service)
    {
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->service->call($name, $arguments);
    }
}
