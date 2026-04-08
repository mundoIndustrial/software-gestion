<?php

namespace App\Domain\Pedidos\UseCases;

interface ObtenerDatosParaCrearPedidoUseCaseContract
{
    public function call(string $method, array $arguments = []): mixed;
}
