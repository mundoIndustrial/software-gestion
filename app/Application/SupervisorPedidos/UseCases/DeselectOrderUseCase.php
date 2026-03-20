<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\SelectOrderRequest;
use App\Application\SupervisorPedidos\DTOs\SelectOrderResponse;
use App\Models\SeleccionPedido;

class DeselectOrderUseCase
{
    public function execute(SelectOrderRequest $request): SelectOrderResponse
    {
        try {
            // Buscar y eliminar la selección
            $selection = SeleccionPedido::where('pedido_id', $request->getOrderId())
                ->where('user_id', $request->getUserId())
                ->first();

            if ($selection) {
                $selection->delete();
            }

            return new SelectOrderResponse(
                success: true,
                message: 'Pedido deseleccionado correctamente',
                selection: []
            );

        } catch (\Throwable $e) {
            throw new \DomainException('Error al deseleccionar el pedido: ' . $e->getMessage());
        }
    }
}
