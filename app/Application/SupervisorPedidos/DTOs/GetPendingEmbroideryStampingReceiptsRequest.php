<?php

namespace App\Application\SupervisorPedidos\DTOs;

class GetPendingEmbroideryStampingReceiptsRequest
{
    private array $receiptTypes;
    private ?string $busqueda;

    public function __construct(
        array $receiptTypes = ['BORDADO', 'ESTAMPADO', 'SUBLIMADO', 'DTF'],
        ?string $busqueda = null
    )
    {
        $this->receiptTypes = $receiptTypes;
        $busqueda = trim((string) ($busqueda ?? ''));
        $this->busqueda = $busqueda !== '' ? $busqueda : null;
    }

    public function getReceiptTypes(): array
    {
        return $this->receiptTypes;
    }

    public function getBusqueda(): ?string
    {
        return $this->busqueda;
    }
}
