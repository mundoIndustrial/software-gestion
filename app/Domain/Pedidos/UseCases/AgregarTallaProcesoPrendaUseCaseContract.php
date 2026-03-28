<?php

namespace App\Domain\Pedidos\UseCases;

interface AgregarTallaProcesoPrendaUseCaseContract
{
    public function call(string $method, array $arguments = []): mixed;
}
