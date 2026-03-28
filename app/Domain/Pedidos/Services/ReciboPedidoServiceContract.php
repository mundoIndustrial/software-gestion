<?php

namespace App\Domain\Pedidos\Services;

interface ReciboPedidoServiceContract
{
    public function call(string $method, array $arguments = []): mixed;
}
