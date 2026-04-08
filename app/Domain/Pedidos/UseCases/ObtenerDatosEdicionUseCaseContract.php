<?php

namespace App\Domain\Pedidos\UseCases;

interface ObtenerDatosEdicionUseCaseContract
{
    public function call(string $method, array $arguments = []): mixed;
}
