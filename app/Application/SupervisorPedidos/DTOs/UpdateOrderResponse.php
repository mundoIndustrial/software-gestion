<?php

namespace App\Application\SupervisorPedidos\DTOs;

class UpdateOrderResponse
{
    private bool $success;
    private string $message;
    private $orden;

    public function __construct(bool $success, string $message, $orden = null)
    {
        $this->success = $success;
        $this->message = $message;
        $this->orden = $orden;
    }

    public function isSuccess(): bool { return $this->success; }
    public function getMessage(): string { return $this->message; }
    public function getOrden() { return $this->orden; }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'orden' => $this->orden
        ];
    }
}
