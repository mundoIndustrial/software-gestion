<?php

namespace App\Domain\Procesos\Services;

use App\Domain\Procesos\Repositories\ProcesoPrendaDetalleRepository;

/**
 * Domain Service: ActivarReciboProcesoService
 *
 * Activa o desactiva el recibo de un proceso.
 * - activar=true  → aprueba el proceso (PENDIENTE → APROBADO)
 * - activar=false → revierte a pendiente (APROBADO → PENDIENTE)
 */
class ActivarReciboProcesoService
{
    public function __construct(
        private ProcesoPrendaDetalleRepository $repository
    ) {}

    /**
     * @throws \DomainException Si el proceso no existe o la transición no es válida
     */
    public function ejecutar(int $procesoId, bool $activar, int $usuarioId)
    {
        $proceso = $this->repository->obtenerPorId($procesoId);

        if (!$proceso) {
            throw new \DomainException('Proceso no encontrado');
        }

        if ($activar) {
            $proceso->aprobar($usuarioId);
        } else {
            $proceso->revertirAPendiente();
        }

        return $this->repository->actualizar($proceso);
    }
}
