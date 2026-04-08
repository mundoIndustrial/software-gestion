<?php

namespace App\Domain\Pedidos\UseCases;

interface EditarProcesoUseCaseContract
{
    public function call(string $method, array $arguments = []): mixed;
}
