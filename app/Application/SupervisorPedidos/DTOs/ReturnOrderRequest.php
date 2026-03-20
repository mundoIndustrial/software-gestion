<?php

namespace App\Application\SupervisorPedidos\DTOs;

class ReturnOrderRequest
{
    private int $orderId;
    private string $reason;

    public function __construct(int $orderId, string $reason)
    {
        $this->orderId = $orderId;
        $this->reason = $reason;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) $data['order_id'],
            $data['reason'] ?? ''
        );
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
