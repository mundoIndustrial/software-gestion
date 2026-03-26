<?php

namespace App\Application\Insumos\UseCases;

use App\Domain\Insumos\Repositories\PrendaMaterialMetricsRepository;

class GuardarAnchoMetrajePrendaInsumosUseCase
{
    public function __construct(
        private readonly PrendaMaterialMetricsRepository $repository
    ) {
    }

    public function execute(string $numeroPedido, int $prendaId, array $datos): array
    {
        return $this->repository->guardarAnchoMetrajePrenda($numeroPedido, $prendaId, $datos);
    }
}

