<?php

namespace App\Application\SupervisorPedidos\DTOs;

class GetOrderSelectionsResponse
{
    public function __construct(
        private bool $success,
        private string $message,
        private array $selections = [],
        private int $totalSelections = 0
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'selections' => $this->selections,
            'totalSelections' => $this->totalSelections
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

    public function getSelections(): array
    {
        return $this->selections;
    }

    public function getTotalSelections(): int
    {
        return $this->totalSelections;
    }
}
