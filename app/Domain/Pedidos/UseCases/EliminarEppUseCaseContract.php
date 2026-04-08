<?php

namespace App\Domain\Pedidos\UseCases;

interface EliminarEppUseCaseContract
{
    public function ejecutar(int $pedidoId, int $pedidoEppId, string $motivo): array;
}

