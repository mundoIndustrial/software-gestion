<?php

namespace App\Application\Insumos\UseCases;

use App\Domain\Insumos\Repositories\MaterialesWriteRepository;

class GuardarObservacionesMaterialUseCase
{
    public function __construct(
        private readonly MaterialesWriteRepository $repository
    ) {
    }

    public function execute(string $numeroPedido, string $nombreMaterial, ?string $observaciones): array
    {
        return $this->repository->guardarObservaciones($numeroPedido, $nombreMaterial, $observaciones);
    }
}

