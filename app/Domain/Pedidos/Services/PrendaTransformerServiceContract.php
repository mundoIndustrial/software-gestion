<?php

namespace App\Domain\Pedidos\Services;

interface PrendaTransformerServiceContract
{
    public function call(string $method, array $arguments = []): mixed;
}
