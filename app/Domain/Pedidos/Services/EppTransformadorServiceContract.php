<?php

namespace App\Domain\Pedidos\Services;

interface EppTransformadorServiceContract
{
    public function call(string $method, array $arguments = []): mixed;
}
