<?php

namespace App\Application\InventarioTelas\UseCases;

use App\Domain\InventarioTelas\Repositories\InventarioTelaRepositoryInterface;

class ObtenerInventarioTelasUseCase
{
    public function __construct(
        private InventarioTelaRepositoryInterface $repository
    ) {}

    public function ejecutar()
    {
        return $this->repository->obtenerTodas();
    }
}
