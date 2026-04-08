<?php

namespace App\Application\SupervisorPedidos\DTOs;

class SelectOrderResponse
{
    public function __construct(
        private bool $success,
        private string $message,
        private array $selection = []
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'selection' => $this->selection
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

    public function getSelection(): array
    {
        return $this->selection;
    }
}
