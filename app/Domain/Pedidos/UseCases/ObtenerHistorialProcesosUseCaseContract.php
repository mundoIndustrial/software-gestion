<?php

namespace App\Domain\Pedidos\UseCases;

interface ObtenerHistorialProcesosUseCaseContract
{
    public function call(string $method, array $arguments = []): mixed;
}
