<?php

namespace App\Application\InventarioTelas\UseCases;

use App\Domain\InventarioTelas\Repositories\InventarioTelaRepositoryInterface;

class EliminarInventarioTelaUseCase
{
    public function __construct(
        private InventarioTelaRepositoryInterface $repository
    ) {}

    public function ejecutar(int $telaId)
    {
        $this->repository->eliminar($telaId);
    }
}
