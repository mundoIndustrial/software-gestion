<?php

namespace App\Application\SupervisorPedidos\DTOs;

class ApproveOrderDetailedResponse
{
    public function __construct(
        private bool $success,
        private string $message,
        private int $orderId,
        private ?string $quotationType,
        private string $newStatus,
        private string $newArea,
        private array $order
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'order' => $this->order,
            'approval' => [
                'quotationType' => $this->quotationType,
                'status' => $this->newStatus,
                'area' => $this->newArea,
                'timestamp' => now()->toIso8601String()
            ]
        ];
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getQuotationType(): ?string
    {
        return $this->quotationType;
    }

    public function getNewStatus(): string
    {
        return $this->newStatus;
    }

    public function getNewArea(): string
    {
        return $this->newArea;
    }
}
