<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetOrderDetailsViewRequest;
use App\Application\SupervisorPedidos\DTOs\GetOrderDetailsViewResponse;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

class GetOrderDetailsViewUseCase
{
    public function execute(GetOrderDetailsViewRequest $request): GetOrderDetailsViewResponse
    {
        try {
            $orden = PedidoProduccion::with(['prendas', 'prendas.procesos'])
                ->findOrFail($request->getOrderId());

            Log::info('[GetOrderDetailsViewUseCase] Retrieved order details for: ' . $orden->numero_pedido);

            return new GetOrderDetailsViewResponse($orden);

        } catch (\Exception $e) {
            Log::error('[GetOrderDetailsViewUseCase] Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
