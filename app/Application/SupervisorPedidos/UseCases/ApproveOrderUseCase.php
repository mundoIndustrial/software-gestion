<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Domain\SupervisorPedidos\Repositories\OrderRepository;
use App\Domain\SupervisorPedidos\ValueObjects\OrderId;
use App\Application\SupervisorPedidos\DTOs\ApproveOrderRequest;
use App\Application\SupervisorPedidos\DTOs\ApproveOrderResponse;
use Illuminate\Support\Facades\Log;

class ApproveOrderUseCase
{
    private OrderRepository $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function execute(ApproveOrderRequest $request): ApproveOrderResponse
    {
        try {
            $orderId = new OrderId($request->getOrderId());
            
            $order = $this->orderRepository->findById($orderId);
            if (!$order) {
                throw new \RuntimeException("Pedido #{$request->getOrderId()} no encontrado");
            }

            if (!$order->isPending()) {
                throw new \DomainException('Solo se pueden aprobar órdenes pendientes');
            }

            $order->approve();
            $this->orderRepository->save($order);

            Log::info("Pedido #{$order->getOrderNumber()} aprobado por supervisor", [
                'order_id' => $orderId->value(),
                'timestamp' => now(),
            ]);

            return new ApproveOrderResponse(
                true,
                'Pedido aprobado correctamente',
                $order->getStatus()->value()
            );

        } catch (\DomainException $e) {
            Log::warning('Domain error in ApproveOrder: ' . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error in ApproveOrder: ' . $e->getMessage());
            throw $e;
        }
    }
}
