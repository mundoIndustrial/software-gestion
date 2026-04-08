<?php

namespace App\Application\SupervisorPedidos\DTOs;

class GetPendingOrdersCountResponse
{
    private int $totalPendientes;
    private int $pendientesLogo;

    public function __construct(int $totalPendientes, int $pendientesLogo)
    {
        $this->totalPendientes = $totalPendientes;
        $this->pendientesLogo = $pendientesLogo;
    }

    public function getTotalPendientes(): int { return $this->totalPendientes; }
    public function getPendientesLogo(): int { return $this->pendientesLogo; }

    public function toArray(): array
    {
        return [
            'success' => true,
            'count' => $this->totalPendientes,
            'pendientesLogo' => $this->pendientesLogo,
            'message' => $this->totalPendientes > 0 
                ? "Hay {$this->totalPendientes} orden(es) pendiente(s)" 
                : 'No hay órdenes pendientes'
        ];
    }
}
