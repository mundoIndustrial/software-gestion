<?php

namespace App\Domain\Bodega\Events;

use App\Domain\Bodega\ValueObjects\NumeroPedidoBodega;

final class OrdenBodegaCreada
{
    public function __construct(
        private NumeroPedidoBodega $numeroPedido,
        private string $cliente
    ) {}

    public function numeroPedido(): NumeroPedidoBodega
    {
        return $this->numeroPedido;
    }

    public function cliente(): string
    {
        return $this->cliente;
    }
}
