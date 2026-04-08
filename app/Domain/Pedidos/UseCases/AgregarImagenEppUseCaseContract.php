<?php

namespace App\Domain\Pedidos\UseCases;

interface AgregarImagenEppUseCaseContract
{
    public function call(string $method, array $arguments = []): mixed;
}
