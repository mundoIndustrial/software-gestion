<?php

namespace App\Application\Insumos\UseCases;

use App\Domain\Insumos\Repositories\MaterialesReadRepository;

class ObtenerPrendasPedidoInsumosUseCase
{
    public function __construct(
        private readonly MaterialesReadRepository $repository
    ) {
    }

    public function execute(string $numeroPedido): array
    {
        return $this->repository->obtenerPrendasPedido($numeroPedido);
    }
}

