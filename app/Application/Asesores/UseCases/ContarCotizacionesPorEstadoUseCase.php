<?php

namespace App\Application\Asesores\UseCases;

use App\Domain\Cotizacion\Repositories\CotizacionRepositoryInterface;

final class ContarCotizacionesPorEstadoUseCase
{
    public function __construct(
        private readonly CotizacionRepositoryInterface $cotizacionRepository
    ) {}

    public function ejecutar(string $estado): int
    {
        return $this->cotizacionRepository->countByEstado($estado);
    }
}

