<?php

namespace App\Application\SupervisorPedidos\DTOs;

class GetOrderDetailsViewResponse
{
    private bool $success;
    private string $message;
    private array $order;

    public function __construct(bool $success, string $message, array $order)
    {
        $this->success = $success;
        $this->message = $message;
        $this->order = $order;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'order' => $this->order
        ];
    }

    public function getOrder(): array
    {
        return $this->order;
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
