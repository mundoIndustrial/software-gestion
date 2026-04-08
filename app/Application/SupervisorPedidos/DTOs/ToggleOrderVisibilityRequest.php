<?php

namespace App\Application\SupervisorPedidos\DTOs;

class ToggleOrderVisibilityRequest
{
    private int $orderId;
    private bool $isHidden;

    public function __construct(int $orderId, bool $isHidden)
    {
        $this->orderId = $orderId;
        $this->isHidden = $isHidden;
    }

    public function getOrderId(): int { return $this->orderId; }
    public function isHidden(): bool { return $this->isHidden; }
}
