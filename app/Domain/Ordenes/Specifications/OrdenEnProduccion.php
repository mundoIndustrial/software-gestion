<?php

namespace App\Domain\Ordenes\Specifications;

use App\Domain\Ordenes\Entities\Orden;

/**
 * Specification: OrdenEnProduccion
 * 
 * Encapsula la lógica: "Una orden está en producción"
 * Separa la lógica de negocio de infraestructura.
 */
class OrdenEnProduccion
{
    public function isSatisfiedBy(Orden $orden): bool
    {
        return $orden->getEstado()->esEnProduccion();
    }
}
