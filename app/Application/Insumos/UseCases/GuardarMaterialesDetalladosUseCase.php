<?php

namespace App\Application\Insumos\UseCases;

use App\Domain\Insumos\Repositories\MaterialesWriteRepository;

class GuardarMaterialesDetalladosUseCase
{
    public function __construct(
        private readonly MaterialesWriteRepository $repository
    ) {
    }

    public function execute(
        string $numeroPedido,
        array $materiales,
        ?int $prendaId = null,
        ?int $prendaBodegaId = null,
        ?int $numeroRecibo = null,
        ?string $tipoRecibo = null
    ): array
    {
        return $this->repository->guardarMaterialesDetallados(
            $numeroPedido,
            $materiales,
            $prendaId,
            $prendaBodegaId,
            $numeroRecibo,
            $tipoRecibo
        );
    }
}
