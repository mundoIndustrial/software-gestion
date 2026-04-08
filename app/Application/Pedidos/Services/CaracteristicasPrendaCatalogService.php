<?php

namespace App\Application\Pedidos\Services;

use App\Domain\Pedidos\Services\CaracteristicasPrendaCatalogServiceContract;

class CaracteristicasPrendaCatalogService
{
    public function __construct(private readonly CaracteristicasPrendaCatalogServiceContract $service)
    {
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->service->call($name, $arguments);
    }
}
