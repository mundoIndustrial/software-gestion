<?php

namespace App\Domain\Pedidos\UseCases;

interface EliminarProcesosListaUseCaseContract
{
    public function call(string $method, array $arguments = []): mixed;
}
