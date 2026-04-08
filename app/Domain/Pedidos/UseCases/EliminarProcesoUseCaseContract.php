<?php

namespace App\Domain\Pedidos\UseCases;

interface EliminarProcesoUseCaseContract
{
    public function ejecutar(int $id, int $numeroPedido): array;
}

