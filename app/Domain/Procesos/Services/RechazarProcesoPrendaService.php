<?php

namespace App\Domain\Procesos\Services;

use App\Domain\Procesos\Repositories\ProcesoPrendaDetalleRepository;

/**
 * Domain Service: RechazarProcesoPrendaService
 * 
 * Orquesta la lógica de negocio para rechazar un proceso
 */
class RechazarProcesoPrendaService
{
    public function __construct(
        private ProcesoPrendaDetalleRepository $repository
    ) {}

    /**
     * Rechazar un proceso con motivo
     * 
     * @throws \DomainException Si el proceso no está en estado PENDIENTE
     */
    public function ejecutar(int $procesoId, string $motivo)
    {
        if (strlen($motivo) < 5) {
            throw new \DomainException("El motivo debe tener al menos 5 caracteres");
        }

        $proceso = $this->repository->obtenerPorId($procesoId);

        if (!$proceso) {
            throw new \DomainException("Proceso no encontrado");
        }

        $proceso->rechazar($motivo);

        return $this->repository->actualizar($proceso);
    }
}
