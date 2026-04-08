<?php

namespace App\Domain\SupervisorPedidos\DomainEvents;

use App\Domain\SupervisorPedidos\ValueObjects\OrderId;
use Carbon\Carbon;

class OrderReturnedEvent
{
    private OrderId $orderId;
    private string $reason;
    private \DateTime $occurredAt;

    public function __construct(OrderId $orderId, string $reason)
    {
        $this->orderId = $orderId;
        $this->reason = $reason;
        $this->occurredAt = Carbon::now();
    }

    public function getOrderId(): OrderId
    {
        return $this->orderId;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getOccurredAt(): \DateTime
    {
        return $this->occurredAt;
    }
}
