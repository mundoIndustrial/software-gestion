<?php

namespace App\Domain\Pedidos\UseCases;

interface AgregarTallaPrendaUseCaseContract
{
    public function call(string $method, array $arguments = []): mixed;
}
