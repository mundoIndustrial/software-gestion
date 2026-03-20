<?php

namespace App\Application\SupervisorPedidos\DTOs;

class GetOrderDetailsRequest
{
    private int $orderId;

    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    public static function fromArray(array $data): self
    {
        return new self((int) $data['order_id']);
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }
}
