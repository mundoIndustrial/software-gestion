<?php

namespace App\Application\Asesores\UseCases;

use App\Domain\Cotizacion\Repositories\CotizacionDetalleRepositoryInterface;
use DomainException;

final class ObtenerCotizacionEditableAsesorUseCase
{
    public function __construct(
        private CotizacionDetalleRepositoryInterface $cotizacionDetalleRepository
    ) {}

    public function ejecutar(int $cotizacionId, int $asesorId): array
    {
        $cotizacion = $this->cotizacionDetalleRepository->obtenerResumenCotizacion($cotizacionId);

        if ($cotizacion === null) {
            throw new DomainException('Cotización no encontrada.');
        }

        if (($cotizacion['asesor_id'] ?? null) !== $asesorId) {
            throw new DomainException('No tienes permiso para editar esta cotización.');
        }

        return $cotizacion;
    }
}
