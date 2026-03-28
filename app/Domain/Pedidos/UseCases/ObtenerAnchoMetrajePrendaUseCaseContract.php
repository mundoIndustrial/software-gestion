<?php

namespace App\Domain\Pedidos\UseCases;

interface ObtenerAnchoMetrajePrendaUseCaseContract
{
    public function call(string $method, array $arguments = []): mixed;
}
