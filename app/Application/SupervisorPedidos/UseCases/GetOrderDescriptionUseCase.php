<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetOrderDescriptionRequest;
use App\Application\SupervisorPedidos\DTOs\GetOrderDescriptionResponse;
use App\Application\SupervisorPedidos\Services\OrderDescriptionBuilder;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

class GetOrderDescriptionUseCase
{
    private OrderDescriptionBuilder $descriptionBuilder;

    public function __construct(OrderDescriptionBuilder $descriptionBuilder)
    {
        $this->descriptionBuilder = $descriptionBuilder;
    }

    public function execute(GetOrderDescriptionRequest $request): GetOrderDescriptionResponse
    {
        try {
            $orderId = $request->getOrderId();
            $order = $this->findOrderWithPrendas($orderId);

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
            return new GetOrderDescriptionResponse(false, '', 'Error al generar descripción: ' . $e->getMessage());
        }
    }

    private function findOrderWithPrendas(int $orderId): ?PedidoProduccion
    {
        return PedidoProduccion::with('prendas')->find($orderId);
    }

    private function hasPrendas(PedidoProduccion $order): bool
    {
        return $order->prendas && !$order->prendas->isEmpty();
    }

    private function logError(\Exception $e, int $orderId): void
    {
        Log::error('Error al obtener descripción del pedido: ' . $e->getMessage(), [
            'order_id' => $orderId,
        ]);
    }
}