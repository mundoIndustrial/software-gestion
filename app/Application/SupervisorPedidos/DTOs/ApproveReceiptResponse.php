<?php

namespace App\Application\SupervisorPedidos\DTOs;

class ApproveReceiptResponse
{
    public function __construct(
        private bool $success,
        private string $message,
        private int $receiptId = 0,
        private int $processesUpdated = 0,
        private array $data = []
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'receiptId' => $this->receiptId,
            'processesUpdated' => $this->processesUpdated,
            'data' => $this->data
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

    public function getReceiptId(): int
    {
        return $this->receiptId;
    }

    public function getProcessesUpdated(): int
    {
        return $this->processesUpdated;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
