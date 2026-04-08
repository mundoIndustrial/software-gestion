<?php

namespace App\Application\SupervisorPedidos\DTOs;

class ActivateReceiptResponse
{
    private bool $success;
    private string $message;
    private string $receiptNumber;
    private ?int $receiptId;

    public function __construct(
        bool $success,
        string $message,
        string $receiptNumber,
        ?int $receiptId = null
    ) {
        $this->success = $success;
        $this->message = $message;
        $this->receiptNumber = $receiptNumber;
        $this->receiptId = $receiptId;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => [
                'consecutivo' => $this->receiptNumber,
                'id' => $this->receiptId,
            ],
        ];
    }
}
