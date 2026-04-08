<?php

namespace App\Domain\Pedidos\UseCases;

interface AgregarEppUseCaseContract
{
    public function call(string $method, array $arguments = []): mixed;
}
