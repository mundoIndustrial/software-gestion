<?php

namespace App\Application\Bodega\Services;

use App\Domain\Bodega\Services\BodegaGuardadoServiceContract;

class BodegaGuardadoService
{
    public function __construct(private readonly BodegaGuardadoServiceContract $service)
    {
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->service->call($name, $arguments);
    }
}
