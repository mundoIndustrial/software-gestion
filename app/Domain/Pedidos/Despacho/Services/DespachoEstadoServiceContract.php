<?php

namespace App\Domain\Pedidos\Despacho\Services;

interface DespachoEstadoServiceContract
{
    public function call(string $method, array $arguments = []): mixed;
}
