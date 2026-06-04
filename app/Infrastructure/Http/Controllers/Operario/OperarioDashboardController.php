<?php

namespace App\Infrastructure\Http\Controllers\Operario;

use App\Application\Operario\Services\OperarioDashboardVistaCosturaService;
use App\Application\Operario\UseCases\GetOperarioDashboardUseCase;
use App\Application\Operario\UseCases\ObtenerDistribucionControlCalidadUseCase;
use App\Application\Operario\UseCases\ObtenerRecibosControlCalidadUseCase;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            $tipoReciboControlCalidad = match ($filtroRecibo) {
                'reflectivo' => 'REFLECTIVO',
                'bodega' => 'CORTE-PARA-BODEGA',
                default => 'COSTURA',
            };
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
        $nombresCosturaReflectivo = $this->operarioDashboardVistaCosturaService->obtenerNombresCosturaReflectivoNormalizados();
        $mapaParcialesBodega = $this->operarioDashboardVistaCosturaService
            ->construirMapaParcialesBodegaDesdePrendas(collect($dashboardData->prendasConRecibos ?? []));
        $contextoVistaDashboard = $this->operarioDashboardVistaCosturaService->prepararContextoVistaDashboard(
            $request,
            collect($dashboardData->prendasConRecibos ?? []),
            $prendasConRecibosControlCalidad,
            $dashboardData->tab ?? null
        );
        $contextoVistaDashboard['prendasRenderizadas'] = $this->operarioDashboardVistaCosturaService
            ->enriquecerPrendasBodegaParaVista(
                collect($contextoVistaDashboard['prendasRenderizadas'] ?? []),
                $mapaParcialesBodega
            );
        $contextoVistaDashboard['prendasRenderizadas'] = $this->operarioDashboardVistaCosturaService
            ->enriquecerPrendasNormalesParaVista(
                collect($contextoVistaDashboard['prendasRenderizadas'] ?? []),
                $request->user(),
                (string) ($contextoVistaDashboard['filtroReciboActual'] ?? 'costura'),
                (string) ($contextoVistaDashboard['busquedaActual'] ?? ''),
                $nombresCosturaReflectivo
            );

        return view('operario.dashboard', array_merge([
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
            'nombresCosturaReflectivo' => $nombresCosturaReflectivo,
            'mapaParcialesBodega' => $mapaParcialesBodega,
        ], $contextoVistaDashboard));
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

    /**
     * GET /operario/api/recibos/{idRecibo}/tallas-control-calidad
     */
    public function obtenerTallasControlCalidad(Request $request, $idRecibo): JsonResponse
    {
        $reciboId = (int) $idRecibo;

        $rows = DB::table('prenda_recibo_completado_tallas as prct')
            ->join('prenda_recibo_completado as prc', 'prc.id', '=', 'prct.prenda_recibo_completado_id')
            ->where('prc.id_recibo', $reciboId)
            ->whereRaw('LOWER(TRIM(COALESCE(prc.area, ""))) IN (?, ?)', ['control calidad', 'control de calidad'])
            ->select([
                'prct.talla',
                'prct.genero',
                'prct.color_nombre',
                'prct.cantidad',
                'prct.created_at',
            ])
            ->orderBy('prct.created_at')
            ->get();

        $tallas = $rows
            ->groupBy(fn ($row) => implode('|', [
                strtoupper(trim((string) ($row->talla ?? ''))),
                strtoupper(trim((string) ($row->genero ?? ''))),
                strtoupper(trim((string) ($row->color_nombre ?? ''))),
            ]))
            ->map(function ($group) {
                $first = $group->first();

                return [
                    'talla' => (string) ($first->talla ?? ''),
                    'genero' => (string) ($first->genero ?? ''),
                    'color_nombre' => (string) ($first->color_nombre ?? ''),
                    'cantidad' => (int) $group->sum(fn ($item) => (int) ($item->cantidad ?? 0)),
                    'historial_envios' => $group->map(function ($item) {
                        return [
                            'cantidad' => (int) ($item->cantidad ?? 0),
                            'fecha_envio' => $item->created_at,
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'tallas' => $tallas,
        ]);
    }
}
