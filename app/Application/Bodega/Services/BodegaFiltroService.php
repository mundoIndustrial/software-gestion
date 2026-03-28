<?php

namespace App\Application\Bodega\Services;

use App\Domain\Bodega\Services\BodegaFiltroServiceContract;

class BodegaFiltroService
{
    public function __construct(private readonly BodegaFiltroServiceContract $service)
    {
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->service->call($name, $arguments);
    }
}
