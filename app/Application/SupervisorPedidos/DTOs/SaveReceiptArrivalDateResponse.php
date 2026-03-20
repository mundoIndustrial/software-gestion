<?php

namespace App\Application\SupervisorPedidos\DTOs;

use Carbon\Carbon;

class SaveReceiptArrivalDateResponse
{
    public function __construct(
        private bool $success,
        private string $message,
        private ?Carbon $arrivalDate = null,
        private array $data = []
    ) {}

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => [
                'fecha_llegada' => $this->arrivalDate?->format('Y-m-d H:i:s'),
                ...$this->data
            ]
        ];
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }
}
