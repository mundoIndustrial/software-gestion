<?php

namespace App\Application\RegistrosOrdenes\QueryHandlers;

use App\Domain\RegistrosOrdenes\Contracts\RegistroOrdenRepository;
use App\Application\RegistrosOrdenes\Contracts\FiltrosOrdenService;
use App\Application\RegistrosOrdenes\Contracts\BusquedaOrdenService;
use App\Application\RegistrosOrdenes\Contracts\TransformacionOrdenService;
use App\Services\CacheCalculosService;
use App\Models\Festivo;
use App\Services\RegistroOrdenProcessService;
use Illuminate\Support\Facades\Log;

/**
 * ListarOrdenesQueryHandler
 * 
 * Handler para listar órdenes con búsqueda, filtros y paginación
 * Orquesta: Repository -> Búsqueda -> Filtros -> Cálculos -> Transformación
 */
class ListarOrdenesQueryHandler
{
    public function __construct(
        private RegistroOrdenRepository $repository,
        private FiltrosOrdenService $filtrosService,
        private BusquedaOrdenService $busquedaService,
        private TransformacionOrdenService $transformacionService,
        private RegistroOrdenProcessService $processService,
    ) {}

    /**
     * Ejecutar query de listado
     */
    public function handle($request, $userRole = null)
    {
        try {
            // 1. Construir query base
            $query = $this->repository->buildBaseQuery();

            // 2. Aplicar búsqueda
            if ($request->has('search') && $request->input('search')) {
                $this->busquedaService->aplicar($query, $request->input('search'));
            }

            // 3. Extraer y aplicar filtros
            $filterData = $this->filtrosService->extraerDelRequest($request);
            $this->filtrosService->aplicar($query, $filterData['filters']);
            $filterTotalDias = $filterData['totalDiasFilter'];

            // 4. Obtener festivos para cálculos de días
            $currentYear = now()->year;
            $nextYear = now()->addYear()->year;
            $festivos = array_merge(
                \App\Services\FestivosColombiaService::obtenerFestivos($currentYear),
                \App\Services\FestivosColombiaService::obtenerFestivos($nextYear)
            );

            // 5. Aplicar filtro de total_dias si existe
            $ordenes = null;
            if ($filterTotalDias !== null) {
                $todasOrdenes = $query->get();
                $ordenes = $this->filtrosService->aplicarFiltroTotalDias($todasOrdenes, $filterTotalDias, $festivos);
                
                // Paginar
                $currentPage = $request->get('page', 1);
                $perPage = 25;
                $ordenes = new \Illuminate\Pagination\LengthAwarePaginator(
                    $ordenes->forPage($currentPage, $perPage)->values(),
                    $ordenes->count(),
                    $perPage,
                    $currentPage,
                    ['path' => $request->url(), 'query' => $request->query()]
                );
            } else {
                // Paginación normal
                $ordenes = $query->paginate(25);
            }

            // 6. Calcular total_dias para página actual
            $totalDiasCalculados = CacheCalculosService::getTotalDiasBatch($ordenes->items(), $festivos);

            // 7. Obtener áreas y encargados
            $numeroPedidosPagina = array_map(fn($o) => $o->numero_pedido, $ordenes->items());
            $areasMap = $this->processService->getLastProcessByOrderNumbers($numeroPedidosPagina);
            $encargadosMap = $this->processService->getCreacionOrdenEncargados($numeroPedidosPagina);

            // 8. Transformar órdenes
            $ordenesTransformadas = array_map(
                fn($orden) => $this->transformacionService->transformarParaListado(
                    $orden,
                    $areasMap,
                    $encargadosMap
                ),
                $ordenes->items()
            );

            return [
                'orders' => $ordenesTransformadas,
                'totalDiasCalculados' => $totalDiasCalculados,
                'pagination' => [
                    'total' => $ordenes->total(),
                    'per_page' => $ordenes->perPage(),
                    'current_page' => $ordenes->currentPage(),
                    'last_page' => $ordenes->lastPage(),
                    'from' => $ordenes->firstItem(),
                    'to' => $ordenes->lastItem(),
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Error en ListarOrdenesQueryHandler: ' . $e->getMessage());
            throw $e;
        }
    }
}
