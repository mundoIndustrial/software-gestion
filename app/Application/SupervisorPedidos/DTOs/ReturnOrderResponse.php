<?php

namespace App\Application\SupervisorPedidos\DTOs;

class ReturnOrderResponse
{
    private bool $success;
    private string $message;
    private string $newStatus;

    public function __construct(bool $success, string $message, string $newStatus)
    {
        $this->success = $success;
        $this->message = $message;
        $this->newStatus = $newStatus;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'estado' => $this->newStatus,
        ];
    }
}
