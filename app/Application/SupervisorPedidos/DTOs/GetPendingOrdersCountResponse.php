<?php

namespace App\Application\SupervisorPedidos\DTOs;

class GetPendingOrdersCountResponse
{
    private int $totalPendientes;
    private int $pendientesLogo;
    private int $pendientesCarteraNoAprobado;

    public function __construct(int $totalPendientes, int $pendientesLogo, int $pendientesCarteraNoAprobado = 0)
    {
        $this->totalPendientes = $totalPendientes;
        $this->pendientesLogo = $pendientesLogo;
        $this->pendientesCarteraNoAprobado = $pendientesCarteraNoAprobado;
    }

    public function getTotalPendientes(): int { return $this->totalPendientes; }
    public function getPendientesLogo(): int { return $this->pendientesLogo; }
    public function getPendientesCarteraNoAprobado(): int { return $this->pendientesCarteraNoAprobado; }

    public function toArray(): array
    {
        return [
            'success' => true,
            'count' => $this->totalPendientes,
            'pendientesLogo' => $this->pendientesLogo,
            'pendientesCarteraNoAprobado' => $this->pendientesCarteraNoAprobado,
            'message' => $this->totalPendientes > 0
                ? "Hay {$this->totalPendientes} orden(es) pendiente(s)"
                : 'No hay ordenes pendientes'
        ];
    }
}
