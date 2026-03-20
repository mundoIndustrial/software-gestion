<?php

namespace App\Application\SupervisorPedidos\DTOs;

class SaveSewingReceiptColorRequest
{
    public function __construct(
        private string $receiptNumber,
        private string $color
    ) {}

    public function getReceiptNumber(): string
    {
        return $this->receiptNumber;
    }

    public function getColor(): string
    {
        return $this->color;
    }
}
