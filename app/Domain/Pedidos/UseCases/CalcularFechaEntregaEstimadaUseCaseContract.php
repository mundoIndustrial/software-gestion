<?php

namespace App\Domain\Pedidos\UseCases;

interface CalcularFechaEntregaEstimadaUseCaseContract
{
    public function call(string $method, array $arguments = []): mixed;
}
