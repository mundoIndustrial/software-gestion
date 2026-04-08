<?php

namespace App\Domain\Insumos\Repositories;

interface PedidoWorkflowRepository
{
    public function cambiarEstadoPorNumeroPedido(string $numeroPedido, string $nuevoEstado): array;

    public function cambiarEstado(int $pedidoId, string $nuevoEstado): array;
}

