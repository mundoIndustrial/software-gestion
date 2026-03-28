<?php

namespace App\Domain\Pedidos\UseCases;

interface AgregarImagenProcesoUseCaseContract
{
    public function call(string $method, array $arguments = []): mixed;
}
