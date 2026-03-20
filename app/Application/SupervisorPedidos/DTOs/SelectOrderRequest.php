<?php

namespace App\Application\SupervisorPedidos\DTOs;

class SelectOrderRequest
{
    public function __construct(
        private int $orderId,
        private int $userId
    ) {}

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
