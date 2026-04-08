<?php

namespace App\Domain\Operario\Repositories;

use App\Models\PedidoProduccion;

interface PedidoProduccionOperarioReadRepository
{
    public function findByNumeroWithPrendas(int $numeroPedido): ?PedidoProduccion;
}

