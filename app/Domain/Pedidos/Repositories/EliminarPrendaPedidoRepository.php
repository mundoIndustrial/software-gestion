<?php

namespace App\Domain\Pedidos\Repositories;

interface EliminarPrendaPedidoRepository
{
    public function eliminarDePedido(int $pedidoId, int $prendaId, string $motivo): array;
}

