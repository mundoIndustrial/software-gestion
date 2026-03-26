<?php

namespace App\Application\Pedidos\Services;

use App\Models\PrendaPedido;
use App\Models\PrendaPedidoTalla;

class PrendaPedidoQuantityCalculator
{
    public function calculate(PrendaPedido $prenda): int
    {
        if ($prenda->relationLoaded('tallas') && $prenda->tallas) {
            $total = 0;
            foreach ($prenda->tallas as $tallaRecord) {
                $total += $tallaRecord->cantidad;
            }
            return $total;
        }

        return PrendaPedidoTalla::where('prenda_pedido_id', $prenda->id)->sum('cantidad');
    }
}
