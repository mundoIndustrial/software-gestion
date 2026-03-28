<?php

namespace App\Application\Pedidos\UseCases\Orders;

use App\Models\PedidoProduccion;

/**
 * UseCase: Obtener el área más reciente de un pedido
 *
 * Responsabilidades:
 * - Buscar el pedido por su ID de base de datos
 * - Retornar el campo área con valor por defecto 'Insumos'
 */
class GetAreaRecienteUseCase
{
    public function execute(int $pedidoId): ?array
    {
        $pedido = PedidoProduccion::find($pedidoId);

        if (!$pedido) {
            return null;
        }

        return [
            'area'      => $pedido->area ?? 'Insumos',
            'pedido_id' => $pedidoId,
        ];
    }
}

