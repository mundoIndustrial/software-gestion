<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\TogglePedidoVistoRequest;
use App\Application\SupervisorPedidos\DTOs\TogglePedidoVistoResponse;
use App\Models\PedidoVistoSupervisor;

class TogglePedidoVistoUseCase
{
    public function execute(TogglePedidoVistoRequest $request): TogglePedidoVistoResponse
    {
        try {
            $existing = PedidoVistoSupervisor::where('pedido_id', $request->getPedidoId())
                ->where('user_id', $request->getUserId())
                ->first();

            $visto = false;

            if ($existing) {
                $existing->delete();
            } else {
                PedidoVistoSupervisor::create([
                    'pedido_id' => $request->getPedidoId(),
                    'user_id' => $request->getUserId()
                ]);
                $visto = true;
            }

            return new TogglePedidoVistoResponse(
                success: true,
                message: $visto ? 'Pedido marcado como visto' : 'Pedido desmarcado',
                visto: $visto
            );

        } catch (\Throwable $e) {
            throw new \DomainException('Error al cambiar estado de visualización del pedido: ' . $e->getMessage());
        }
    }
}
