<?php

namespace App\Application\SupervisorPedidos\DTOs;

class ChangeOrderStatusResponse
{
    public function __construct(
        private bool $success,
        private string $message,
        private int $orderId,
        private ?string $oldStatus,
        private string $newStatus,
        private array $order
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'order' => $this->order,
            'statusChange' => [
                'from' => $this->oldStatus,
                'to' => $this->newStatus,
                'timestamp' => now()->toIso8601String()
            ]
        ];
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }
}
