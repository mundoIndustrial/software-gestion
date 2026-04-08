<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Domain\Pedidos\Services\PrendaPedidoQuantityCalculatorContract;

use App\Models\PrendaPedido;
use App\Models\PrendaPedidoTalla;

class PrendaPedidoQuantityCalculator implements PrendaPedidoQuantityCalculatorContract
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

    public function call(string $method, array $arguments = []): mixed
    {
        if (!method_exists($this, $method)) {
            throw new \BadMethodCallException("Method {PrendaPedidoQuantityCalculator}::$method does not exist");
        }

        return $this->{$method}(...$arguments);
    }
}
