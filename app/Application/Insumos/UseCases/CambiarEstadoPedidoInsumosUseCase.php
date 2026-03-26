<?php

namespace App\Application\Insumos\UseCases;

use App\Domain\Insumos\Repositories\PedidoWorkflowRepository;

class CambiarEstadoPedidoInsumosUseCase
{
    public function __construct(
        private readonly PedidoWorkflowRepository $repository
    ) {
    }

    public function execute(string $numeroPedido, string $nuevoEstado): array
    {
        return $this->repository->cambiarEstadoPorNumeroPedido($numeroPedido, $nuevoEstado);
    }
}

