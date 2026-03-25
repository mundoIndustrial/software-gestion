<?php

namespace App\Application\Asesores\UseCases;

use App\Domain\BodegaDetalleTalla\Repositories\BodegaDetalleTallaRepositoryInterface;

class ContarPendientesAsesorUseCase
{
    public function __construct(
        private BodegaDetalleTallaRepositoryInterface $bodegaDetalleTallaRepository
    ) {}

    public function ejecutar(string $asesorNombre): int
    {
        return $this->bodegaDetalleTallaRepository->contarPendientesAsesor($asesorNombre);
    }
}
