<?php

namespace App\Domain\Pedidos\UseCases;

interface ObtenerCotizacionesUseCaseContract
{
    public function call(string $method, array $arguments = []): mixed;
}
