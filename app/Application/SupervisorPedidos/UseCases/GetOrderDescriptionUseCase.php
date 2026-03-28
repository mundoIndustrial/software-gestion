<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetOrderDescriptionRequest;
use App\Application\SupervisorPedidos\DTOs\GetOrderDescriptionResponse;
use App\Application\SupervisorPedidos\Services\OrderDescriptionBuilder;
use App\Application\SupervisorPedidos\Services\PedidoProduccionReadService;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

class GetOrderDescriptionUseCase
{
    private OrderDescriptionBuilder $descriptionBuilder;

    public function __construct(
        OrderDescriptionBuilder $descriptionBuilder,
        private readonly PedidoProduccionReadService $readService
    ) {
        $this->descriptionBuilder = $descriptionBuilder;
    }

    public function execute(GetOrderDescriptionRequest $request): GetOrderDescriptionResponse
    {
        try {
            $orderId = $request->getOrderId();
            $order = $this->readService->findOrderWithPrendas($orderId);

            if (!$order) {
                return new GetOrderDescriptionResponse(false, '', 'Pedido no encontrado');
            }

            if (!$this->hasPrendas($order)) {
                return new GetOrderDescriptionResponse(true, '', null);
            }

            $descripcionCompleta = $this->descriptionBuilder->build($order);

            return new GetOrderDescriptionResponse(true, $descripcionCompleta, null);
        } catch (\Exception $e) {
            $this->logError($e, $request->getOrderId());
            return new GetOrderDescriptionResponse(false, '', 'Error al generar descripcion: ' . $e->getMessage());
        }
    }

    private function hasPrendas(PedidoProduccion $order): bool
    {
        return $order->prendas && !$order->prendas->isEmpty();
    }

    private function logError(\Exception $e, int $orderId): void
    {
        Log::error('Error al obtener descripcion del pedido: ' . $e->getMessage(), [
            'order_id' => $orderId,
        ]);
    }
}
