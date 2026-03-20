<?php

namespace App\Application\SupervisorPedidos\DTOs;

class ListPendingOrdersResponse
{
    private bool $success;
    private array $orders;

    public function __construct(bool $success, array $orders = [])
    {
        $this->success = $success;
        $this->orders = $orders;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'orders' => $this->orders,
            'total' => count($this->orders),
        ];
    }

    public function getOrders(): array
    {
        return $this->orders;
    }
}
