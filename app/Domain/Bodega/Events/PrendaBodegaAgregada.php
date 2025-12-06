<?php

namespace App\Domain\Bodega\Events;

use App\Domain\Bodega\ValueObjects\NumeroPedidoBodega;
use App\Domain\Bodega\Entities\PrendaBodega;

final class PrendaBodegaAgregada
{
    public function __construct(
        private NumeroPedidoBodega $numeroPedido,
        private PrendaBodega $prenda
    ) {}

    public function numeroPedido(): NumeroPedidoBodega
    {
        return $this->numeroPedido;
    }

    public function prenda(): PrendaBodega
    {
        return $this->prenda;
    }
}
