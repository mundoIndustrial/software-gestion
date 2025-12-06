<?php

namespace App\Domain\Bodega\Specifications;

use App\Domain\Bodega\Entities\OrdenBodega;
use App\Domain\Bodega\ValueObjects\EstadoBodega;

final class OrdenBodegaCompleta
{
    public function isSatisfiedBy(OrdenBodega $orden): bool
    {
        return $orden->estado()->valor() === EstadoBodega::ENTREGADO
            && !empty($orden->prendas())
            && $orden->cantidadTotal() > 0;
    }
}
