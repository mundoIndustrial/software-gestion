<?php

namespace App\Application\SupervisorPedidos\DTOs;

class TogglePedidoVistoResponse
{
    public function __construct(
        private bool $success,
        private string $message,
        private bool $visto
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'visto' => $this->visto
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

    public function isVisto(): bool
    {
        return $this->visto;
    }
}
