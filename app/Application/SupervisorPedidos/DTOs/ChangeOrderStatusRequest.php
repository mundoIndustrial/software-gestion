<?php

namespace App\Application\SupervisorPedidos\DTOs;

class ChangeOrderStatusRequest
{
    public function __construct(
        private int $orderId,
        private string $status
    ) {}

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
