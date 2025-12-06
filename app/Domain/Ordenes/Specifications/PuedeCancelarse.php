<?php

namespace App\Domain\Ordenes\Specifications;

use App\Domain\Ordenes\Entities\Orden;

/**
 * Specification: PuedeCancelarse
 * 
 * Encapsula la lógica: "Una orden puede ser cancelada"
 * (No está completada ni ya cancelada)
 */
class PuedeCancelarse
{
    public function isSatisfiedBy(Orden $orden): bool
    {
        $estado = $orden->getEstado()->toString();
        return !in_array($estado, ['Completada', 'Cancelada']);
    }
}
