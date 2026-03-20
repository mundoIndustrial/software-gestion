<?php

namespace App\Application\SupervisorPedidos\DTOs;

class GetOrderDetailsResponse
{
    private array $orderData;

    public function __construct(array $orderData)
    {
        $this->orderData = $orderData;
    }

    public function toArray(): array
    {
        return $this->orderData;
    }

    public function getOrderData(): array
    {
        return $this->orderData;
    }
}
