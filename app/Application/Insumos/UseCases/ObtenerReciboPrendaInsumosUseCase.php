<?php

namespace App\Application\Insumos\UseCases;

use App\Domain\Insumos\Repositories\MaterialesReadRepository;

class ObtenerReciboPrendaInsumosUseCase
{
    public function __construct(
        private readonly MaterialesReadRepository $repository
    ) {
    }

    public function execute(string $numeroPedido, int $prendaId): array
    {
        return $this->repository->obtenerReciboPrenda($numeroPedido, $prendaId);
    }
}

