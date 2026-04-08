<?php

namespace App\Domain\Bodega\Services;

interface PedidoEstadoCalculatorContract
{
    public function calcular(string $numeroPedido): array;

    public function existePendiente(string $numeroPedido): bool;
}

