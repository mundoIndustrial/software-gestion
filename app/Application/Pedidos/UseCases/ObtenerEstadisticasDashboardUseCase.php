<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ObtenerEstadisticasDashboardDTO;
use App\Application\Services\Asesores\DashboardService;

/**
 * ObtenerEstadisticasDashboardUseCase
 * 
 * Use Case para obtener estadísticas generales del dashboard
 * Encapsula la lógica de obtener datos del dashboard
 */
class ObtenerEstadisticasDashboardUseCase
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    public function ejecutar(ObtenerEstadisticasDashboardDTO $dto): array
    {
        return $this->dashboardService->obtenerEstadisticas();
    }
}
