<?php

namespace App\Infrastructure\Http\Controllers\Operario;

use App\Application\Operario\Services\OperarioDashboardVistaCosturaService;
use App\Application\Operario\UseCases\GetOperarioDashboardUseCase;
use App\Application\Operario\UseCases\ObtenerDistribucionControlCalidadUseCase;
use App\Application\Operario\UseCases\ObtenerRecibosControlCalidadUseCase;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OperarioDashboardController extends Controller
{
    public function __construct(
        private GetOperarioDashboardUseCase $getOperarioDashboardUseCase,
        private ObtenerRecibosControlCalidadUseCase $obtenerRecibosControlCalidadUseCase,
        private ObtenerDistribucionControlCalidadUseCase $obtenerDistribucionControlCalidadUseCase,
        private OperarioDashboardVistaCosturaService $operarioDashboardVistaCosturaService,
    ) {}

    /**
     * Dashboard del operario.
     */
    public function dashboard(Request $request)
    {
        $dashboardData = $this->getOperarioDashboardUseCase->execute($request);
        $prendasConRecibosControlCalidad = collect();
        $resultadosBusquedaFueraDeArea = collect();

        $esVistaCostura = $request->user()?->hasRole('vista-costura') ?? false;
        $filtroEncargado = strtolower(trim((string) $request->query('encargado', '')));
        $filtroRecibo = strtolower(trim((string) $request->query('filtro', 'costura')));
        $busquedaDashboard = strtolower(trim((string) $request->query('q', '')));
        $mensajeBusquedaDashboard = null;

        if ($esVistaCostura && $filtroEncargado === 'control-calidad') {
            $tipoReciboControlCalidad = $filtroRecibo === 'reflectivo' ? 'REFLECTIVO' : 'COSTURA';
            $resultadoCC = $this->obtenerRecibosControlCalidadUseCase->execute($tipoReciboControlCalidad);
            $prendasConRecibosControlCalidad = $this->operarioDashboardVistaCosturaService->formatearRecibosControlCalidadParaDashboard(
                (array) ($resultadoCC['payload']['data'] ?? []),
                $tipoReciboControlCalidad
            );

            if ($busquedaDashboard !== '') {
                $prendasConRecibosControlCalidad = $this->operarioDashboardVistaCosturaService->filtrarPrendasControlCalidadPorBusqueda(
                    $prendasConRecibosControlCalidad,
                    $busquedaDashboard
                );
            }
        }

        if ($busquedaDashboard !== '' && $dashboardData->prendasConRecibos->isEmpty()) {
            $resultadosBusquedaFueraDeArea = $this->operarioDashboardVistaCosturaService->buscarResultadosBusquedaVistaCosturaFueraDeArea(
                $busquedaDashboard,
                $filtroRecibo
            );

            if ($resultadosBusquedaFueraDeArea->isEmpty()) {
                $mensajeBusquedaDashboard = $this->operarioDashboardVistaCosturaService->resolverMensajeBusquedaVistaCostura($busquedaDashboard, $filtroRecibo);
            }
        }

        $conteosControlCalidad = $this->operarioDashboardVistaCosturaService->obtenerConteosControlCalidad();
        $conteoControlCalidadCostura = (int) ($conteosControlCalidad['costura'] ?? 0);
        $conteoControlCalidadReflectivo = (int) ($conteosControlCalidad['reflectivo'] ?? 0);
        $conteoControlCalidadBodega = (int) ($conteosControlCalidad['bodega'] ?? 0);

        return view('operario.dashboard', [
            'operario' => $dashboardData->operario,
            'prendasConRecibos' => $dashboardData->prendasConRecibos,
            'usuario' => $dashboardData->usuario,
            'tab' => $dashboardData->tab,
            'recibosCompletados' => $dashboardData->recibosCompletados,
            'recibosCompletadosCount' => $dashboardData->recibosCompletadosCount,
            'recibosBodegaCompletados' => $dashboardData->recibosBodegaCompletados,
            'recibosBodegaCompletadosCount' => $dashboardData->recibosBodegaCompletadosCount,
            'pendientesPedidosCount' => $dashboardData->pendientesPedidosCount,
            'recibosBodegaPendientesCount' => $dashboardData->recibosBodegaPendientesCount,
            'vistaCosturaSinEncargadoCount' => $dashboardData->vistaCosturaSinEncargadoCount,
            'vistaCosturaBodegaSinEncargadoCount' => $dashboardData->vistaCosturaBodegaSinEncargadoCount,
            'vistaCosturaBodegaControlCalidadCount' => $dashboardData->vistaCosturaBodegaControlCalidadCount,
            'prendasConRecibosControlCalidad' => $prendasConRecibosControlCalidad,
            'resultadosBusquedaFueraDeArea' => $resultadosBusquedaFueraDeArea,
            'mensajeBusquedaDashboard' => $mensajeBusquedaDashboard,
            'conteoControlCalidadCostura' => $conteoControlCalidadCostura,
            'conteoControlCalidadReflectivo' => $conteoControlCalidadReflectivo,
            'conteoControlCalidadBodega' => $conteoControlCalidadBodega,
        ]);
    }

    /**
     * GET /operario/api/recibos/control-calidad/{tipoRecibo}
     */
    public function obtenerRecibosControlCalidad(Request $request, $tipoRecibo): JsonResponse
    {
        $resultado = $this->obtenerRecibosControlCalidadUseCase->execute($tipoRecibo);
        return response()->json($resultado['payload'], $resultado['status']);
    }

    /**
     * GET /operario/api/recibos/{idRecibo}/distribucion-control-calidad
     */
    public function obtenerDistribucionControlCalidad(Request $request, $idRecibo): JsonResponse
    {
        $resultado = $this->obtenerDistribucionControlCalidadUseCase->execute((int) $idRecibo);
        return response()->json($resultado['payload'], $resultado['status']);
    }
}

