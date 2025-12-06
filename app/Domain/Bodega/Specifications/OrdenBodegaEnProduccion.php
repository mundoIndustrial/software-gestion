<?php

namespace App\Domain\Bodega\Specifications;

use App\Domain\Bodega\Entities\OrdenBodega;
use App\Domain\Bodega\ValueObjects\EstadoBodega;

final class OrdenBodegaEnProduccion
{
    public function isSatisfiedBy(OrdenBodega $orden): bool
    {
        return $orden->estado()->valor() === EstadoBodega::EN_EJECUCION;
    }
}
