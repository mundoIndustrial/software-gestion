<?php

namespace App\Domain\Pedidos\Services;

interface PrendaTransformadorServiceContract
{
    public function call(string $method, array $arguments = []): mixed;
}
