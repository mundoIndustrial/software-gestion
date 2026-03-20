<?php

namespace App\Application\SupervisorPedidos\DTOs;

class GetReceiptDetailsResponse
{
    public function __construct(
        private bool $success,
        private string $message,
        private array $details
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => $this->details
        ];
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }
}
