<?php

namespace App\Domain\Ordenes\Events;

use App\Domain\Ordenes\Entities\Orden;
use Carbon\Carbon;

/**
 * Domain Event: OrdenCreada
 * 
 * Se dispara cuando se crea una nueva orden.
 */
class OrdenCreada
{
    public readonly int $numeroPedido;
    public readonly string $cliente;
    public readonly string $formaPago;
    public readonly string $area;
    public readonly Carbon $fechaCreacion;

    public function __construct(Orden $orden)
    {
        $this->numeroPedido = $orden->getNumeroPedido()->toInt();
        $this->cliente = $orden->getCliente();
        $this->formaPago = $orden->getFormaPago()->toString();
        $this->area = $orden->getArea()->toString();
        $this->fechaCreacion = $orden->getFechaCreacion();
    }
}
