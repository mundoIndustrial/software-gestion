<?php

namespace App\Domain\Pedidos\Services;

interface CaracteristicasPrendaCatalogServiceContract
{
    public function call(string $method, array $arguments = []): mixed;
}
