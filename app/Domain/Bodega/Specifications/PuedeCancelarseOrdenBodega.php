<?php

namespace App\Domain\Bodega\Specifications;

use App\Domain\Bodega\Entities\OrdenBodega;

final class PuedeCancelarseOrdenBodega
{
    public function isSatisfiedBy(OrdenBodega $orden): bool
    {
        return $orden->puedeSerCancelada();
    }
}
