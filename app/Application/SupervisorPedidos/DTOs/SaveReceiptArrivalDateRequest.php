<?php

namespace App\Application\SupervisorPedidos\DTOs;

use Carbon\Carbon;

class SaveReceiptArrivalDateRequest
{
    public function __construct(
        private int $receiptId,
        private ?string $arrivalDateRaw = null
    ) {}

    public function getReceiptId(): int
    {
        return $this->receiptId;
    }

    public function getArrivalDate(): ?Carbon
    {
        if (!$this->arrivalDateRaw || $this->arrivalDateRaw === '') {
            return null;
        }

        try {
            return Carbon::parse($this->arrivalDateRaw);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Fecha inválida: ' . $this->arrivalDateRaw);
        }
    }
}
