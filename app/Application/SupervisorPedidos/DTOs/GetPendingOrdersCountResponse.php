<?php

namespace App\Application\SupervisorPedidos\DTOs;

class GetPendingOrdersCountResponse
{
    private int $totalPendientes;
    private int $pendientesLogo;
    private int $pendientesCarteraNoAprobado;
    private int $devueltoAsesoraCount;

    public function __construct(int $totalPendientes, int $pendientesLogo, int $pendientesCarteraNoAprobado = 0, int $devueltoAsesoraCount = 0)
    {
        $this->totalPendientes = $totalPendientes;
        $this->pendientesLogo = $pendientesLogo;
        $this->pendientesCarteraNoAprobado = $pendientesCarteraNoAprobado;
        $this->devueltoAsesoraCount = $devueltoAsesoraCount;
    }

    public function getTotalPendientes(): int { return $this->totalPendientes; }
    public function getPendientesLogo(): int { return $this->pendientesLogo; }
    public function getPendientesCarteraNoAprobado(): int { return $this->pendientesCarteraNoAprobado; }
    public function getDevueltoAsesoraCount(): int { return $this->devueltoAsesoraCount; }

    public function toArray(): array
    {
        return [
            'success' => true,
            'count' => $this->totalPendientes,
            'pendientesLogo' => $this->pendientesLogo,
            'pendientesCarteraNoAprobado' => $this->pendientesCarteraNoAprobado,
            'devueltoAsesoraCount' => $this->devueltoAsesoraCount,
            'message' => $this->totalPendientes > 0
                ? "Hay {$this->totalPendientes} orden(es) pendiente(s)"
                : 'No hay ordenes pendientes'
        ];
    }
}
