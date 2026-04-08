<?php

namespace App\Application\Asesores\UseCases;

use App\Domain\BodegaDetalleTalla\Repositories\BodegaDetalleTallaRepositoryInterface;

class ObtenerPendientesAsesorUseCase
{
    public function __construct(
        private BodegaDetalleTallaRepositoryInterface $bodegaDetalleTallaRepository
    ) {}

    public function ejecutar(
        string $asesorNombre,
        string $search = '',
        string $tipo = 'todos',
        int $page = 1,
        int $perPage = 20
    ): array {
        return $this->bodegaDetalleTallaRepository->obtenerPendientesAsesor(
            $asesorNombre,
            $search,
            $tipo,
            $page,
            $perPage
        );
    }
}
