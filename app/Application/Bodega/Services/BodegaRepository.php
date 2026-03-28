<?php

namespace App\Application\Bodega\Services;

use App\Domain\Bodega\Services\BodegaRepositoryContract;

class BodegaRepository
{
    public function __construct(private readonly BodegaRepositoryContract $service)
    {
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->service->call($name, $arguments);
    }
}
