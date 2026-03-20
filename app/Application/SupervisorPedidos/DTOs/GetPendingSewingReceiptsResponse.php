<?php

namespace App\Application\SupervisorPedidos\DTOs;

class GetPendingSewingReceiptsResponse
{
    private array $receipts;

    public function __construct(array $receipts = [])
    {
        $this->receipts = $receipts;
    }

    public function getReceipts(): array
    {
        return $this->receipts;
    }

    public function toArray(): array
    {
        return [
            'procesosConCantidad' => $this->receipts
        ];
    }
}
