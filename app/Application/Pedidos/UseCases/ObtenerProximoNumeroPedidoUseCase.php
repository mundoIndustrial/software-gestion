<?php

namespace App\Application\Pedidos\UseCases;

use App\Models\PedidoProduccion;

class ObtenerProximoNumeroPedidoUseCase
{
    public function ejecutar(): int
    {
        $ultimoNumero = PedidoProduccion::whereNotNull('numero_pedido')
            ->max('numero_pedido');

        return ((int) ($ultimoNumero ?? 0)) + 1;
    }
}
