<?php

namespace App\Domain\Procesos\Services;

use App\Domain\Procesos\Entities\ProcesoPrendaDetalle;
use App\Domain\Procesos\Repositories\ProcesoPrendaDetalleRepository;

/**
 * Domain Service: CrearProcesoPrendaService
 * 
 * Orquesta la lógica de negocio para crear un nuevo proceso para una prenda
 */
class CrearProcesoPrendaService
{
    public function __construct(
        private ProcesoPrendaDetalleRepository $repository
    ) {}

    /**
     * Crear un nuevo proceso para una prenda
     * 
     * @throws \DomainException Si ya existe un proceso del mismo tipo
     */
    public function ejecutar(
        int $prendaId,
        int $tipoProcesoId,
        array $ubicaciones,
        ?string $observaciones = null,
        ?array $tallasDama = null,
        ?array $tallasCalabrero = null,
        ?array $datosAdicionales = null
    ): ProcesoPrendaDetalle {
        
        // Validar que no exista otro proceso del mismo tipo
        $procesoExistente = $this->repository->obtenerPorPrendaYTipo($prendaId, $tipoProcesoId);
        
        if ($procesoExistente) {
            throw new \DomainException(
                "Esta prenda ya tiene asignado un proceso del tipo solicitado"
            );
        }

        // Validar ubicaciones
        if (empty($ubicaciones)) {
            throw new \DomainException("Debe especificar al menos una ubicación");
        }

        // Crear entity
        $proceso = new ProcesoPrendaDetalle(
            id: null,
            prendaPedidoId: $prendaId,
            tipoProcesoId: $tipoProcesoId,
            ubicaciones: $ubicaciones,
            observaciones: $observaciones,
            tallasDama: $tallasDama,
            tallasCalabrero: $tallasCalabrero,
            datosAdicionales: $datosAdicionales
        );

        // Guardar
        return $this->repository->guardar($proceso);
    }
}
