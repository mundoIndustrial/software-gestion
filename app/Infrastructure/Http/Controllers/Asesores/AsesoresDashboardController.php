<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Pedidos\DTOs\ObtenerDatosGraficasDashboardDTO;
use App\Application\Pedidos\DTOs\ObtenerEstadisticasDashboardDTO;
use App\Application\Pedidos\UseCases\ObtenerDatosGraficasDashboardUseCase;
use App\Application\Pedidos\UseCases\ObtenerEstadisticasDashboardUseCase;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class AsesoresDashboardController extends Controller
{
    public function __construct(
        private readonly ObtenerEstadisticasDashboardUseCase $obtenerEstadisticasDashboardUseCase,
        private readonly ObtenerDatosGraficasDashboardUseCase $obtenerDatosGraficasDashboardUseCase
    ) {
    }

    public function dashboard()
    {
        $dto = ObtenerEstadisticasDashboardDTO::crear();
        $stats = $this->obtenerEstadisticasDashboardUseCase->ejecutar($dto);

        return view('asesores.dashboard', compact('stats'));
    }

    public function getDashboardData(Request $request)
    {
        $dias = (int) $request->get('tipo', 30);
        $dto = ObtenerDatosGraficasDashboardDTO::fromRequest($dias);
        $datos = $this->obtenerDatosGraficasDashboardUseCase->ejecutar($dto);

        return response()->json($datos);
    }
}

