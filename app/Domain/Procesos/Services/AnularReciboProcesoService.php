<?php

namespace App\Domain\Procesos\Services;

use App\Domain\Procesos\Repositories\ProcesoPrendaDetalleRepository;

/**
 * Domain Service: AnularReciboProcesoService
 *
 * Anula un proceso (transición irrevocable a estado ANULADO).
 * Solo supervisores pueden ejecutar esta acción (validado en el controller).
 */
class AnularReciboProcesoService
{
    public function __construct(
        private ProcesoPrendaDetalleRepository $repository
    ) {}

    /**
     * @throws \DomainException Si el proceso no existe o ya está anulado
     */
    public function ejecutar(int $procesoId)
    {
        $proceso = $this->repository->obtenerPorId($procesoId);

        if (!$proceso) {
            throw new \DomainException('Proceso no encontrado');
        }

        $proceso->anular();

        return $this->repository->actualizar($proceso);
    }
}
