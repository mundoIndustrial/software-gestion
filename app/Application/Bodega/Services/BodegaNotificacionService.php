<?php

namespace App\Application\Bodega\Services;

use App\Domain\Bodega\Services\BodegaNotificacionServiceContract;

class BodegaNotificacionService
{
    public function __construct(private readonly BodegaNotificacionServiceContract $service)
    {
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->service->call($name, $arguments);
    }
}
