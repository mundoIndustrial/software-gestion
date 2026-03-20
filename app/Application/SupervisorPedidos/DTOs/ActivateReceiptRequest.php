<?php

namespace App\Application\SupervisorPedidos\DTOs;

class ActivateReceiptRequest
{
    private int $orderId;
    private int $prendaId;

    public function __construct(int $orderId, int $prendaId)
    {
        $this->orderId = $orderId;
        $this->prendaId = $prendaId;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) $data['order_id'],
            (int) $data['prenda_id']
        );
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getPrendaId(): int
    {
        return $this->prendaId;
    }
}
