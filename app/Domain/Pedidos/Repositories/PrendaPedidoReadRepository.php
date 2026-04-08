<?php

namespace App\Domain\Pedidos\Repositories;

use App\Models\PrendaPedido;

interface PrendaPedidoReadRepository
{
    public function obtenerPorId(int $prendaId): ?PrendaPedido;
}

