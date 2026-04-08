<?php

namespace App\Application\SupervisorPedidos\DTOs;

class GetPendingEmbroideryStampingReceiptsRequest
{
    private array $receiptTypes;

    public function __construct(array $receiptTypes = ['BORDADO', 'ESTAMPADO', 'SUBLIMADO', 'DTF'])
    {
        $this->receiptTypes = $receiptTypes;
    }

    public function getReceiptTypes(): array
    {
        return $this->receiptTypes;
    }
}
