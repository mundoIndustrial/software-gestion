<?php

namespace App\Application\Bodega\Services;

use App\Domain\Bodega\Services\BodegaAuditoriaServiceContract;

class BodegaAuditoriaService
{
    public function __construct(private readonly BodegaAuditoriaServiceContract $service)
    {
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->service->call($name, $arguments);
    }
}
