<?php

namespace App\Application\SupervisorPedidos\DTOs;

class ToggleOrderVisibilityResponse
{
    private bool $success;
    private string $message;

    public function __construct(bool $success, string $message)
    {
        $this->success = $success;
        $this->message = $message;
    }

    public function isSuccess(): bool { return $this->success; }
    public function getMessage(): string { return $this->message; }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
        ];
    }
}
