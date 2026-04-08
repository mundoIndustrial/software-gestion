<?php

namespace App\Application\Insumos\UseCases;

use App\Domain\Insumos\Repositories\MaterialesWriteRepository;

class EliminarMaterialPorNombreUseCase
{
    public function __construct(
        private readonly MaterialesWriteRepository $repository
    ) {
    }

    public function execute(string $numeroPedido, string $nombreMaterial, ?int $prendaId = null): array
    {
        return $this->repository->eliminarMaterialPorNombre($numeroPedido, $nombreMaterial, $prendaId);
    }
}

