<?php

namespace App\Domain\Pedidos\Services;

interface FacturaPedidoServiceContract
{
    public function call(string $method, array $arguments = []): mixed;
}
