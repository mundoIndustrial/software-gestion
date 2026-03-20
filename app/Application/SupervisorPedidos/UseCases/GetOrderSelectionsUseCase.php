<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetOrderSelectionsResponse;
use App\Models\SeleccionPedido;

class GetOrderSelectionsUseCase
{
    public function execute(int $userId): GetOrderSelectionsResponse
    {
        try {
            // Obtener todas las selecciones del usuario
            $selections = SeleccionPedido::paraUsuario($userId)
                ->get(['id', 'pedido_id', 'user_id', 'seleccionado']);

            return new GetOrderSelectionsResponse(
                success: true,
                message: 'Selecciones obtenidas correctamente',
                selections: $selections->toArray(),
                totalSelections: $selections->count()
            );

        } catch (\Throwable $e) {
            throw new \DomainException('Error al obtener las selecciones: ' . $e->getMessage());
        }
    }
}
