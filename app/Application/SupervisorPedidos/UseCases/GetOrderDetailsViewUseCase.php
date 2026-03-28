<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetOrderDetailsViewRequest;
use App\Application\SupervisorPedidos\DTOs\GetOrderDetailsViewResponse;
use App\Application\SupervisorPedidos\Services\PedidoProduccionReadService;
use Illuminate\Support\Facades\Log;

class GetOrderDetailsViewUseCase
{
    public function __construct(
        private readonly PedidoProduccionReadService $readService
    ) {}

    public function execute(GetOrderDetailsViewRequest $request): GetOrderDetailsViewResponse
    {
        try {
            $orden = $this->readService->findOrderForDetailsView($request->getOrderId());
            if (!$orden) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Pedido no encontrado');
            }

            Log::info('[GetOrderDetailsViewUseCase] Retrieved order details for: ' . $orden->numero_pedido);

            return new GetOrderDetailsViewResponse($orden);
        } catch (\Exception $e) {
            Log::error('[GetOrderDetailsViewUseCase] Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
