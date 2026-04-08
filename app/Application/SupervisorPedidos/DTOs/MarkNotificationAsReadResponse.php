<?php

namespace App\Application\SupervisorPedidos\DTOs;

class MarkNotificationAsReadResponse
{
    public function __construct(
        private bool $success,
        private string $message
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message
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
