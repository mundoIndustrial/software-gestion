<?php

namespace App\Application\SupervisorPedidos\DTOs;

class CancelReceiptResponse
{
    public function __construct(
        private bool $success,
        private string $message,
        private int $receiptId,
        private int $consecutive,
        private array $data
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => [
                'id' => $this->receiptId,
                'consecutivo' => $this->consecutive,
                ...$this->data
            ]
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
}
