<?php

namespace App\Domain\Pedidos\UseCases;

interface ObtenerFilasDespachoUseCaseContract
{
    public function call(string $method, array $arguments = []): mixed;
}
