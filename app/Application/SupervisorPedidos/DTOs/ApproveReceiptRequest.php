<?php

namespace App\Application\SupervisorPedidos\DTOs;

class ApproveReceiptRequest
{
    public function __construct(
        private int $receiptId
    ) {}

    public function getReceiptId(): int
    {
        return $this->receiptId;
    }
}
