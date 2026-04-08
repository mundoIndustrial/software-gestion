<?php

namespace App\Domain\Pedidos\UseCases;

interface EliminarPrendaPedidoUseCaseContract
{
    public function ejecutar(int $pedidoId, int $prendaId, string $motivo): array;
}

