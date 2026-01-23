<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ObtenerDatosGraficasDashboardDTO;
use App\Application\Services\Asesores\DashboardService;

/**
 * ObtenerDatosGraficasDashboardUseCase
 * 
 * Use Case para obtener datos de grÃ¡ficas del dashboard
 * Encapsula la lÃ³gica de cÃ¡lculo y obtenciÃ³n de datos para grÃ¡ficas
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

