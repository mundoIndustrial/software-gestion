<?php

namespace App\Application\Insumos\UseCases;

use App\Domain\Insumos\Repositories\RecibosPendientesRepository;

class CambiarEstadoReciboInsumosUseCase
{
    public function __construct(
        private readonly RecibosPendientesRepository $repository
    ) {
    }

    public function execute(int $reciboId, string $nuevoEstado): array
    {
        return $this->repository->cambiarEstadoRecibo($reciboId, $nuevoEstado);
    }
}

