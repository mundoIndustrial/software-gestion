<?php

namespace App\Domain\Pedidos\UseCases;

interface AgregarColorTelaUseCaseContract
{
    public function call(string $method, array $arguments = []): mixed;
}
