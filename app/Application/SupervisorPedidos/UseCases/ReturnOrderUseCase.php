<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Domain\SupervisorPedidos\Repositories\OrderRepository;
use App\Domain\SupervisorPedidos\ValueObjects\OrderId;
use App\Application\SupervisorPedidos\DTOs\ReturnOrderRequest;
use App\Application\SupervisorPedidos\DTOs\ReturnOrderResponse;
use Illuminate\Support\Facades\Log;

class ReturnOrderUseCase
{
    private OrderRepository $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function execute(ReturnOrderRequest $request): ReturnOrderResponse
    {
        try {
            $orderId = new OrderId($request->getOrderId());
            
            $order = $this->orderRepository->findById($orderId);
            if (!$order) {
                throw new \RuntimeException("Pedido #{$request->getOrderId()} no encontrado");
            }

            $order->returnToAdvisor($request->getReason());
            $this->orderRepository->save($order);

            Log::info("Pedido #{$order->getOrderNumber()} devuelto a asesora", [
                'order_id' => $orderId->value(),
                'reason' => $request->getReason(),
                'timestamp' => now(),
            ]);

            return new ReturnOrderResponse(
                true,
                'Pedido devuelto a revisión correctamente',
                $order->getStatus()->value()
            );

        } catch (\DomainException $e) {
            Log::warning('Domain error in ReturnOrder: ' . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error in ReturnOrder: ' . $e->getMessage());
            throw $e;
        }
    }
}
