<?php

namespace App\Infrastructure\Repositories\Bodega;

use App\Domain\BodegaNota\Repositories\BodegaNotaRepositoryInterface;
use Illuminate\Support\Facades\DB;

class BodegaNotaRepository implements BodegaNotaRepositoryInterface
{
    public function obtenerNotasPorNumeroPedido(string $numeroPedido)
    {
        return DB::table('bodega_notas')
            ->where('numero_pedido', $numeroPedido)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
