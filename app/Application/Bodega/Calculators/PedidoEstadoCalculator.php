<?php

namespace App\Application\Bodega\Calculators;

use App\Domain\Bodega\Services\PedidoEstadoCalculatorContract;

class PedidoEstadoCalculator
{
    public function __construct(private readonly PedidoEstadoCalculatorContract $calculator)
    {
    }

    public function calcular(string $numeroPedido): array
    {
        return $this->calculator->calcular($numeroPedido);
    }

    public function existePendiente(string $numeroPedido): bool
    {
        return $this->calculator->existePendiente($numeroPedido);
    }
}

