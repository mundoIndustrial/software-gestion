<?php

namespace App\Domain\Pedidos\UseCases;

interface AgregarImagenTelaUseCaseContract
{
    public function call(string $method, array $arguments = []): mixed;
}
