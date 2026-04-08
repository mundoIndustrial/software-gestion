<?php

namespace App\Application\Bodega\Services;

use App\Domain\Bodega\Services\BodegaDatosServiceContract;

class BodegaDatosService
{
    public function __construct(private readonly BodegaDatosServiceContract $service)
    {
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->service->call($name, $arguments);
    }
}
