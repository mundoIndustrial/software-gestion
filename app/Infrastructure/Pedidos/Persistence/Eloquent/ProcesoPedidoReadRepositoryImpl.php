<?php

namespace App\Infrastructure\Pedidos\Persistence\Eloquent;

use App\Domain\Pedidos\Repositories\ProcesoPedidoReadRepository;
use App\Models\ProcesoPrenda;

class ProcesoPedidoReadRepositoryImpl implements ProcesoPedidoReadRepository
{
    public function obtenerProcesosPorNumeroPedidoYPrenda(int|string $numeroPedido, ?int $prendaId = null): array
    {
        $query = ProcesoPrenda::query()
            ->where('numero_pedido', $numeroPedido)
            ->orderBy('fecha_inicio', 'asc')
            ->select([
                'id',
                'numero_pedido',
                'prenda_pedido_id',
                'proceso',
                'fecha_inicio',
                'encargado',
                'estado_proceso',
            ]);

        if ($prendaId !== null) {
            $query->where('prenda_pedido_id', $prendaId);
        }

        return $query->get()->all();
    }
}

