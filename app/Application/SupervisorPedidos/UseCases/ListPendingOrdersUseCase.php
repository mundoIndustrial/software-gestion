<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Domain\SupervisorPedidos\Repositories\OrderRepository;
use App\Application\SupervisorPedidos\DTOs\ListPendingOrdersResponse;
use Illuminate\Support\Facades\Log;

class ListPendingOrdersUseCase
{
    private OrderRepository $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function execute(): ListPendingOrdersResponse
    {
        try {
            $pendingOrders = $this->orderRepository->findAllPending();

            $orders = array_map(fn($order) => [
                'id' => $order->getId()->value(),
                'numero' => $order->getOrderNumber(),
                'cliente' => $order->getCustomerName(),
                'estado' => $order->getStatus()->value(),
                'aprobado_en' => $order->getApprovedBySupervisorAt(),
            ], $pendingOrders);

            Log::info('Fetched pending orders', ['count' => count($orders)]);

            return new ListPendingOrdersResponse(true, $orders);

        } catch (\Exception $e) {
            Log::error('Error in ListPendingOrders: ' . $e->getMessage());
            throw $e;
        }
    }
}
