<?php

namespace App\Domain\Ordenes\Events;

use App\Domain\Ordenes\Entities\Orden;
use App\Domain\Ordenes\ValueObjects\EstadoOrden;
use Carbon\Carbon;

/**
 * Domain Event: OrdenActualizada
 * 
 * Se dispara cuando cambia el estado de la orden.
 */
class OrdenActualizada
{
    public readonly int $numeroPedido;
    public readonly string $estadoAnterior;
    public readonly string $estadoNuevo;
    public readonly Carbon $fecha;

    public function __construct(Orden $orden, EstadoOrden $estadoAnterior, EstadoOrden $estadoNuevo)
    {
        $this->numeroPedido = $orden->getNumeroPedido()->toInt();
        $this->estadoAnterior = $estadoAnterior->toString();
        $this->estadoNuevo = $estadoNuevo->toString();
        $this->fecha = Carbon::now();
    }
}
