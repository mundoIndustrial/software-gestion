<?php

namespace App\Domain\Ordenes\Specifications;

use App\Domain\Ordenes\Entities\Orden;

/**
 * Specification: OrdenCompleta
 * 
 * Encapsula la lógica: "Una orden está completa"
 */
class OrdenCompleta
{
    public function isSatisfiedBy(Orden $orden): bool
    {
        return $orden->estaCompleta();
    }
}
