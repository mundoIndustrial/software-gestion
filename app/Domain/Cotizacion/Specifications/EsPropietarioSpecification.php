<?php

namespace App\Domain\Cotizacion\Specifications;

use App\Domain\Cotizacion\Entities\Cotizacion;
use App\Domain\Shared\ValueObjects\UserId;

/**
 * EsPropietarioSpecification - Verifica si un usuario es propietario de una cotización
 */
final class EsPropietarioSpecification
{
    public function __construct(private readonly UserId $usuarioId)
    {
    }

    /**
     * Verificar si se cumple la especificación
     */
    public function isSatisfiedBy(Cotizacion $cotizacion): bool
    {
        return $cotizacion->esPropietarioDe($this->usuarioId);
    }

    /**
     * Lanzar excepción si no se cumple
     */
    public function throwIfNotSatisfied(Cotizacion $cotizacion): void
    {
        if (!$this->isSatisfiedBy($cotizacion)) {
            throw new \DomainException(
                'No tienes permiso para acceder a esta cotización'
            );
        }
    }
}
