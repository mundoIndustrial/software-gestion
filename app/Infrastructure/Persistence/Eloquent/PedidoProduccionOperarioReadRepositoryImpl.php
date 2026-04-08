<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Operario\Repositories\PedidoProduccionOperarioReadRepository;
use App\Models\PedidoProduccion;

class PedidoProduccionOperarioReadRepositoryImpl implements PedidoProduccionOperarioReadRepository
{
    public function findByNumeroWithPrendas(int $numeroPedido): ?PedidoProduccion
    {
        return PedidoProduccion::query()
            ->where('numero_pedido', (int) $numeroPedido)
            ->with('prendas')
            ->first();
    }
}

