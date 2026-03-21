<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ObtenerDatosGraficasDashboardDTO;
use App\Application\Services\Asesores\DashboardService;

/**
 * ObtenerDatosGraficasDashboardUseCase
 * 
 * Use Case para obtener datos de graficas del dashboard
 * Encapsula la lógica de calculo y obtención de datos para graficas
 */
class ObtenerDatosGraficasDashboardUseCase
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    public function ejecutar(ObtenerDatosGraficasDashboardDTO $dto): array
    {
        return $this->dashboardService->obtenerDatosGraficas($dto->dias);
    }
}

