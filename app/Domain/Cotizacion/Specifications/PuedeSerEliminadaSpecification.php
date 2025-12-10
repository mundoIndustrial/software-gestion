<?php

namespace App\Domain\Cotizacion\Specifications;

use App\Domain\Cotizacion\Entities\Cotizacion;

/**
 * PuedeSerEliminadaSpecification - Verifica si una cotización puede ser eliminada
 *
 * Regla: Solo los borradores pueden ser eliminados
 */
final class PuedeSerEliminadaSpecification
{
    /**
     * Verificar si se cumple la especificación
     */
    public function isSatisfiedBy(Cotizacion $cotizacion): bool
    {
        return $cotizacion->puedeSerEliminada();
    }

    /**
     * Lanzar excepción si no se cumple
     */
    public function throwIfNotSatisfied(Cotizacion $cotizacion): void
    {
        if (!$this->isSatisfiedBy($cotizacion)) {
            throw new \DomainException(
                'Solo se pueden eliminar cotizaciones en estado borrador'
            );
        }
    }
}
