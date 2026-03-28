<?php

namespace App\Domain\Bodega\Services;

interface BodegaPedidoServiceContract
{
    public function call(string $method, array $arguments = []): mixed;
}
