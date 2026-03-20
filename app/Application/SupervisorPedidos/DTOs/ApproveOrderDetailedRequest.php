<?php

namespace App\Application\SupervisorPedidos\DTOs;

class ApproveOrderDetailedRequest
{
    public function __construct(
        private int $orderId
    ) {}

    public function getOrderId(): int
    {
        return $this->orderId;
    }
}
