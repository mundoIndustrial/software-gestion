<?php

namespace App\Domain\SupervisorPedidos\DomainEvents;

use App\Domain\SupervisorPedidos\ValueObjects\OrderId;
use Carbon\Carbon;

class OrderApprovedEvent
{
    private OrderId $orderId;
    private \DateTime $occurredAt;

    public function __construct(OrderId $orderId)
    {
        $this->orderId = $orderId;
        $this->occurredAt = Carbon::now();
    }

    public function getOrderId(): OrderId
    {
        return $this->orderId;
    }

    public function getOccurredAt(): \DateTime
    {
        return $this->occurredAt;
    }
}
