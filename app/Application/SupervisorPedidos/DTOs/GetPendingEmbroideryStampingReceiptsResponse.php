<?php

namespace App\Application\SupervisorPedidos\DTOs;

class GetPendingEmbroideryStampingReceiptsResponse
{
    private array $processes;

    public function __construct(array $processes = [])
    {
        $this->processes = $processes;
    }

    public function getProcesses(): array
    {
        return $this->processes;
    }

    public function toArray(): array
    {
        return [
            'procesosConCantidad' => $this->processes
        ];
    }
}
