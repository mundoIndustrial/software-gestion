<?php

namespace App\Application\Insumos\UseCases;

use App\Domain\Insumos\Repositories\PrendaMaterialMetricsRepository;

class ObtenerAnchoMetrajePrendaInsumosUseCase
{
    public function __construct(
        private readonly PrendaMaterialMetricsRepository $repository
    ) {
    }

    public function execute(string $numeroPedido, int $prendaId): array
    {
        return $this->repository->obtenerAnchoMetrajePrenda($numeroPedido, $prendaId);
    }
}

