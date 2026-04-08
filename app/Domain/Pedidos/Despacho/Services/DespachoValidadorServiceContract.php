<?php

namespace App\Domain\Pedidos\Despacho\Services;

interface DespachoValidadorServiceContract
{
    public function call(string $method, array $arguments = []): mixed;
}
