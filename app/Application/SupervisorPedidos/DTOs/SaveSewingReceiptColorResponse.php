<?php

namespace App\Application\SupervisorPedidos\DTOs;

class SaveSewingReceiptColorResponse
{
    public function __construct(
        private bool $success,
        private string $message,
        private string $receiptNumber = ''
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'receiptNumber' => $this->receiptNumber
        ];
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getReceiptNumber(): string
    {
        return $this->receiptNumber;
    }
}
