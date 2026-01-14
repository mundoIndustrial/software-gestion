<?php

namespace App\Domain\Procesos\Services;

use App\Domain\Procesos\Repositories\ProcesoPrendaDetalleRepository;

/**
 * Domain Service: AprobarProcesoPrendaService
 * 
 * Orquesta la lógica de negocio para aprobar un proceso
 */
class AprobarProcesoPrendaService
{
    public function __construct(
        private ProcesoPrendaDetalleRepository $repository
    ) {}

    /**
     * Aprobar un proceso
     * 
     * @throws \DomainException Si el proceso no está en estado PENDIENTE
     */
    public function ejecutar(int $procesoId, int $usuarioId)
    {
        $proceso = $this->repository->obtenerPorId($procesoId);

        if (!$proceso) {
            throw new \DomainException("Proceso no encontrado");
        }

        $proceso->aprobar($usuarioId);

        return $this->repository->actualizar($proceso);
    }
}
