<?php

namespace App\Domain\Ordenes\Events;

use App\Domain\Ordenes\Entities\Orden;
use App\Domain\Ordenes\Entities\Prenda;
use Carbon\Carbon;

/**
 * Domain Event: PrendaAgregada
 * 
 * Se dispara cuando se agrega una prenda a la orden.
 */
class PrendaAgregada
{
    public readonly int $numeroPedido;
    public readonly string $nombrePrenda;
    public readonly int $cantidadTotal;
    public readonly Carbon $fecha;

    public function __construct(Orden $orden, Prenda $prenda)
    {
        $this->numeroPedido = $orden->getNumeroPedido()->toInt();
        $this->nombrePrenda = $prenda->getNombrePrenda();
        $this->cantidadTotal = $prenda->getCantidadTotal();
        $this->fecha = Carbon::now();
    }
}
