<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\SelectOrderRequest;
use App\Application\SupervisorPedidos\DTOs\SelectOrderResponse;
use App\Models\SeleccionPedido;
use App\Models\PedidoProduccion;

class SelectOrderUseCase
{
    public function execute(SelectOrderRequest $request): SelectOrderResponse
    {
        try {
            // Verificar que el pedido existe
            $order = PedidoProduccion::findOrFail($request->getOrderId());

            // Usar updateOrCreate para garantizar que el registro sea creado o actualizado
            $selection = SeleccionPedido::updateOrCreate([
                'pedido_id' => $request->getOrderId(),
                'user_id' => $request->getUserId(),
            ], [
                'seleccionado' => true,
            ]);

            return new SelectOrderResponse(
                success: true,
                message: 'Pedido seleccionado correctamente',
                selection: [
                    'id' => $selection->id,
                    'pedido_id' => $selection->pedido_id,
                    'user_id' => $selection->user_id,
                    'seleccionado' => $selection->seleccionado
                ]
            );

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            throw new \DomainException('El pedido especificado no existe');
        } catch (\Throwable $e) {
            throw new \DomainException('Error al seleccionar el pedido: ' . $e->getMessage());
        }
    }
}
