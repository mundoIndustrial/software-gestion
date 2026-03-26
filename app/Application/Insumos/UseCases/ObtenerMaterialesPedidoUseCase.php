<?php

namespace App\Application\Insumos\UseCases;

use App\Domain\Insumos\Repositories\MaterialesReadRepository;

class ObtenerMaterialesPedidoUseCase
{
    public function __construct(
        private readonly MaterialesReadRepository $repository
    ) {
    }

    public function execute(string $numeroPedido, ?int $prendaId = null): array
    {
        return $this->repository->obtenerMaterialesPedido($numeroPedido, $prendaId);
    }
}

