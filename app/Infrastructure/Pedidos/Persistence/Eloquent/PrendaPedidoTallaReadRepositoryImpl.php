<?php

namespace App\Infrastructure\Pedidos\Persistence\Eloquent;

use App\Domain\Pedidos\Repositories\PrendaPedidoTallaReadRepository;
use App\Models\PrendaPedidoTalla;

class PrendaPedidoTallaReadRepositoryImpl implements PrendaPedidoTallaReadRepository
{
    public function obtenerPorId(int $tallaId): ?array
    {
        $talla = PrendaPedidoTalla::query()->find($tallaId);

        return $talla?->toArray();
    }
}

