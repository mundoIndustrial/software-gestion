<?php

namespace App\Domain\Bodega\Events;

use App\Domain\Bodega\ValueObjects\NumeroPedidoBodega;
use App\Domain\Bodega\ValueObjects\EstadoBodega;

final class OrdenBodegaActualizada
{
    public function __construct(
        private NumeroPedidoBodega $numeroPedido,
        private EstadoBodega $estadoAnterior,
        private EstadoBodega $estadoNuevo
    ) {}

    public function numeroPedido(): NumeroPedidoBodega
    {
        return $this->numeroPedido;
    }

    public function estadoAnterior(): EstadoBodega
    {
        return $this->estadoAnterior;
    }

    public function estadoNuevo(): EstadoBodega
    {
        return $this->estadoNuevo;
    }
}
