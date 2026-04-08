<?php

namespace App\Application\Insumos\UseCases;

use App\Domain\Insumos\Repositories\MaterialesReadRepository;

class MarcarNotificacionesInsumosLeidasUseCase
{
    public function __construct(
        private readonly MaterialesReadRepository $repository
    ) {
    }

    public function execute(int $userId): array
    {
        return $this->repository->marcarTodasNotificacionesLeidas($userId);
    }
}

