<?php

namespace App\Infrastructure\Pedidos\Persistence\Eloquent;

use App\Domain\Pedidos\Repositories\PrendaPedidoReadRepository;
use App\Models\PrendaPedido;

class PrendaPedidoReadRepositoryImpl implements PrendaPedidoReadRepository
{
    public function obtenerPorId(int $prendaId): ?PrendaPedido
    {
        return PrendaPedido::query()->find($prendaId);
    }
}

