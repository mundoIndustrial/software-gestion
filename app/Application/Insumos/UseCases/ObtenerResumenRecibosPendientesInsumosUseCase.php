<?php

namespace App\Application\Insumos\UseCases;

use App\Domain\Insumos\Repositories\RecibosPendientesRepository;

class ObtenerResumenRecibosPendientesInsumosUseCase
{
    public function __construct(
        private readonly RecibosPendientesRepository $repository
    ) {
    }

    public function execute(int $userId): array
    {
        return $this->repository->obtenerResumenRecibosPendientes($userId);
    }
}

