<?php

namespace App\Domain\Pedidos\Services;

interface PrendaPedidoQuantityCalculatorContract
{
    public function call(string $method, array $arguments = []): mixed;
}
