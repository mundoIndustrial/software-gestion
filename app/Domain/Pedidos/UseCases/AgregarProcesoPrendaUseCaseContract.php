<?php

namespace App\Domain\Pedidos\UseCases;

interface AgregarProcesoPrendaUseCaseContract
{
    public function call(string $method, array $arguments = []): mixed;
}
