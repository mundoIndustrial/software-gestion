<?php

namespace App\Application\Insumos\UseCases;

use App\Domain\Insumos\Repositories\RecibosPendientesRepository;

class ObtenerRecibosCosturaPendientesInsumosUseCase
{
    public function __construct(
        private readonly RecibosPendientesRepository $repository
    ) {
    }

    public function execute(): array
    {
        return $this->repository->obtenerRecibosCosturaPendientes();
    }
}

