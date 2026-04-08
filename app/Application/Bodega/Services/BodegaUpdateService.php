<?php

namespace App\Application\Bodega\Services;

use App\Domain\Bodega\Services\BodegaUpdateServiceContract;

class BodegaUpdateService
{
    public function __construct(private readonly BodegaUpdateServiceContract $service)
    {
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->service->call($name, $arguments);
    }
}
