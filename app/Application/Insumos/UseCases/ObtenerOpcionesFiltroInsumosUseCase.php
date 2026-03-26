<?php

namespace App\Application\Insumos\UseCases;

use App\Domain\Insumos\Repositories\MaterialesReadRepository;

class ObtenerOpcionesFiltroInsumosUseCase
{
    public function __construct(
        private readonly MaterialesReadRepository $repository
    ) {
    }

    public function execute(string $column): array
    {
        return $this->repository->obtenerOpcionesFiltro($column);
    }
}

