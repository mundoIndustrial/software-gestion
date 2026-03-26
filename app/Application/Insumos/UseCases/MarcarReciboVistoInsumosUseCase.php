<?php

namespace App\Application\Insumos\UseCases;

use App\Domain\Insumos\Repositories\RecibosPendientesRepository;

class MarcarReciboVistoInsumosUseCase
{
    public function __construct(
        private readonly RecibosPendientesRepository $repository
    ) {
    }

    public function execute(int $reciboId, int $userId): array
    {
        return $this->repository->marcarReciboVisto($reciboId, $userId);
    }
}

