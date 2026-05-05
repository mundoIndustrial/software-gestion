<?php

namespace App\Application\Pedidos\UseCases\Orders;

use App\Constants\AreaOptions;
use App\Services\CacheCalculosService;
use App\Services\FestivosColombiaService;
use App\Services\RegistroOrdenExtendedQueryService;
use App\Services\RegistroOrdenFilterExtendedService;
use App\Services\RegistroOrdenProcessService;
use App\Services\RegistroOrdenSearchExtendedService;
use App\Services\RegistroOrdenTransformService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class GetOrdersQueryUseCase
{
    public function __construct(
        private readonly RegistroOrdenExtendedQueryService $extendedQueryService,
        private readonly RegistroOrdenSearchExtendedService $extendedSearchService,
        private readonly RegistroOrdenFilterExtendedService $extendedFilterService,
        private readonly RegistroOrdenTransformService $transformService,
        private readonly RegistroOrdenProcessService $processService,
    ) {}

    /**
     * Mantiene la misma respuesta y comportamiento del controller.
     *
     * @return array{type: 'json'|'view', status?: int, data?: array, view?: string, viewData?: array}
     */
    public function execute(Request $request): array
    {
        $response = null;
        $uniqueValuesResponse = $this->handleUniqueValuesRequest($request);

        if ($uniqueValuesResponse !== null) {
            $response = $uniqueValuesResponse;
        } else {
            $query = $this->extendedQueryService->buildBaseQuery();
            $query = $this->extendedQueryService->applyRoleFilters($query, auth()->user(), $request);
            $query = $this->extendedSearchService->applySearchFilter($query, $request->input('search'));

            $filterData = $this->extendedFilterService->extractFiltersFromRequest($request);
            $query = $this->extendedFilterService->applyFiltersToQuery($query, $filterData['filters']);
            $quickStatusCounts = $this->buildQuickStatusCounts(clone $query);
            $query = $this->applyQuickStatusFilter($query, $request->input('status'));
            $festivos = $this->buildFestivos();

            ['ordenes' => $ordenes, 'totalDiasCalculados' => $totalDiasCalculados] = $this->resolveOrdersAndTotals(
                $query,
                $filterData['totalDiasFilter'],
                $request,
                $festivos
            );

            $numeroPedidosPagina = array_map(fn ($orden) => $orden->numero_pedido, $ordenes->items());
            $pedidoIdsPagina = array_map(fn ($orden) => (int) $orden->id, $ordenes->items());
            $areasMap = $this->processService->getLastProcessByOrderNumbers($numeroPedidosPagina);
            $encargadosCreacionOrdenMap = $this->processService->getCreacionOrdenEncargados($numeroPedidosPagina);
            $fechaMaximaRecibosPorPedido = $this->extendedQueryService->getMaxDeliveryDatesByPedidoIds($pedidoIdsPagina);
            $areaOptions = AreaOptions::getArray();
            $totalDiasCalculados = $this->ensureTotalDiasCalculados($ordenes, $totalDiasCalculados, $festivos);

            $response = $request->wantsJson()
                ? $this->buildJsonResponse($ordenes, $areasMap, $encargadosCreacionOrdenMap, $totalDiasCalculados, $areaOptions, $fechaMaximaRecibosPorPedido)
                : $this->buildViewResponse($ordenes, $areasMap, $encargadosCreacionOrdenMap, $totalDiasCalculados, $areaOptions, $fechaMaximaRecibosPorPedido, $quickStatusCounts);
        }

        return $response;
    }

    /**
     * @return array{type:'json',status:int,data:array}|null
     */
    private function handleUniqueValuesRequest(Request $request): ?array
    {
        $response = null;

        if ($request->has('get_unique_values') && $request->has('column')) {
            try {
                $values = $this->extendedQueryService->getUniqueValues($request->input('column'));
                $response = [
                    'type' => 'json',
                    'status' => 200,
                    'data' => ['unique_values' => $values],
                ];
            } catch (\InvalidArgumentException $e) {
                $response = [
                    'type' => 'json',
                    'status' => 400,
                    'data' => ['error' => 'Invalid column'],
                ];
            } catch (\Exception $e) {
                $response = [
                    'type' => 'json',
                    'status' => 500,
                    'data' => ['error' => 'Error fetching values: ' . $e->getMessage()],
                ];
            }
        }

        return $response;
    }

    /**
     * @return array<int, string>
     */
    private function buildFestivos(): array
    {
        $currentYear = now()->year;
        $nextYear = now()->addYear()->year;

        return array_merge(
            FestivosColombiaService::obtenerFestivos($currentYear),
            FestivosColombiaService::obtenerFestivos($nextYear)
        );
    }

    /**
     * @param mixed $query
     * @param array<int, int>|null $filterTotalDias
     * @return array{ordenes:LengthAwarePaginator,totalDiasCalculados:array}
     */
    private function resolveOrdersAndTotals($query, ?array $filterTotalDias, Request $request, array $festivos): array
    {
        if ($filterTotalDias !== null) {
            return $this->resolveOrdersAndTotalsWithDiasFilter($query, $filterTotalDias, $request, $festivos);
        }

        $ordenes = $query->paginate(25)->withQueryString();
        $totalDiasCalculados = CacheCalculosService::getTotalDiasBatch($ordenes->items(), $festivos);

        return [
            'ordenes' => $ordenes,
            'totalDiasCalculados' => $totalDiasCalculados,
        ];
    }

    /**
     * @param mixed $query
     * @param array<int, int> $filterTotalDias
     * @return array{ordenes:LengthAwarePaginator,totalDiasCalculados:array}
     */
    private function resolveOrdersAndTotalsWithDiasFilter($query, array $filterTotalDias, Request $request, array $festivos): array
    {
        $todasOrdenes = $query->get();
        $ordenesArray = $todasOrdenes->map(fn ($orden) => (object) (array) $orden)->toArray();
        $totalDiasCalculados = CacheCalculosService::getTotalDiasBatch($ordenesArray, $festivos);

        $ordenesFiltradas = $todasOrdenes->filter(function ($orden) use ($totalDiasCalculados, $filterTotalDias) {
            $totalDias = $totalDiasCalculados[$orden->numero_pedido] ?? 0;
            return in_array((int) $totalDias, $filterTotalDias, true);
        });

        $currentPage = (int) $request->get('page', 1);
        $perPage = 25;
        $ordenes = new LengthAwarePaginator(
            $ordenesFiltradas->forPage($currentPage, $perPage)->values(),
            $ordenesFiltradas->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return [
            'ordenes' => $ordenes,
            'totalDiasCalculados' => CacheCalculosService::getTotalDiasBatch($ordenes->items(), $festivos),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function ensureTotalDiasCalculados(LengthAwarePaginator $ordenes, array $totalDiasCalculados, array $festivos): array
    {
        if (empty($totalDiasCalculados)) {
            return CacheCalculosService::getTotalDiasBatch($ordenes->items(), $festivos);
        }

        foreach ($ordenes->items() as $orden) {
            if (!isset($totalDiasCalculados[$orden->numero_pedido])) {
                $totalDiasCalculados[$orden->numero_pedido] =
                    CacheCalculosService::getTotalDias($orden->numero_pedido, $orden->estado);
            }
        }

        return $totalDiasCalculados;
    }

    /**
     * @return array{type:'json',status:int,data:array}
     */
    private function buildJsonResponse(
        LengthAwarePaginator $ordenes,
        array $areasMap,
        array $encargadosCreacionOrdenMap,
        array $totalDiasCalculados,
        array $areaOptions,
        array $fechaMaximaRecibosPorPedido
    ): array {
        $ordenesFiltered = array_map(
            fn ($orden) => $this->transformService->transformarOrden($orden, $areasMap, $encargadosCreacionOrdenMap),
            $ordenes->items()
        );

        $context = 'registros';
        $userRole = auth()->user() && auth()->user()->role ? auth()->user()->role->name : null;

        return [
            'type' => 'json',
            'status' => 200,
            'data' => [
                'orders' => $ordenesFiltered,
                'totalDiasCalculados' => $totalDiasCalculados,
                'areaOptions' => $areaOptions,
                'context' => $context,
                'userRole' => $userRole,
                'pagination' => [
                    'current_page' => $ordenes->currentPage(),
                    'last_page' => $ordenes->lastPage(),
                    'per_page' => $ordenes->perPage(),
                    'total' => $ordenes->total(),
                    'from' => $ordenes->firstItem(),
                    'to' => $ordenes->lastItem(),
                ],
                'pagination_html' => '',
                'fechaMaximaRecibosPorPedido' => array_map(
                    static fn ($fecha) => $fecha?->toDateString(),
                    $fechaMaximaRecibosPorPedido
                ),
            ],
        ];
    }

    /**
     * @return array{type:'view',view:string,viewData:array}
     */
    private function buildViewResponse(
        LengthAwarePaginator $ordenes,
        array $areasMap,
        array $encargadosCreacionOrdenMap,
        array $totalDiasCalculados,
        array $areaOptions,
        array $fechaMaximaRecibosPorPedido,
        array $quickStatusCounts = []
    ): array {
        $context = 'registros';
        $title = 'Registro de Órdenes';
        $icon = 'fa-clipboard-list';
        $fetchUrl = '/registros';
        $updateUrl = '/registros';
        $modalContext = 'orden';

        return [
            'type' => 'view',
            'view' => 'orders.index',
            'viewData' => compact(
                'ordenes',
                'totalDiasCalculados',
                'areaOptions',
                'areasMap',
                'encargadosCreacionOrdenMap',
                'context',
                'title',
                'icon',
                'fetchUrl',
                'updateUrl',
                'modalContext',
                'fechaMaximaRecibosPorPedido',
                'quickStatusCounts'
            ),
        ];
    }

    /**
     * Contadores globales para tabs rápidos (sobre toda la consulta filtrada, no solo la página actual).
     *
     * @param mixed $baseQuery
     * @return array{todos:int,vencidos:int,en_progreso:int,entregados:int}
     */
    private function buildQuickStatusCounts($baseQuery): array
    {
        $totalTodos = (clone $baseQuery)->count();

        $totalEnProgreso = (clone $baseQuery)
            ->whereIn('estado', [
                'En Ejecución', 'PENDIENTE_SUPERVISOR', 'PENDIENTE_INSUMOS',
                'DEVUELTO_A_ASESORA', 'Pendiente', 'No iniciado'
            ])
            ->count();

        $totalEntregados = (clone $baseQuery)
            ->whereRaw('LOWER(estado) LIKE ?', ['%entregado%'])
            ->count();

        $totalVencidos = (clone $baseQuery)
            ->whereNotNull('fecha_estimada_de_entrega')
            ->whereDate('fecha_estimada_de_entrega', '<', now()->toDateString())
            ->whereRaw('LOWER(estado) NOT LIKE ?', ['%entregado%'])
            ->count();

        return [
            'todos' => (int) $totalTodos,
            'vencidos' => (int) $totalVencidos,
            'en_progreso' => (int) $totalEnProgreso,
            'entregados' => (int) $totalEntregados,
        ];
    }

    /**
     * Aplicar filtro rápido por estado (tabs de la vista)
     */
    private function applyQuickStatusFilter($query, ?string $status)
    {
        $status = trim((string) $status);

        if ($status === '' || $status === 'todos') {
            return $query;
        }

        if ($status === 'en-progreso') {
            return $query->whereIn('estado', [
                'En Ejecución', 'PENDIENTE_SUPERVISOR', 'PENDIENTE_INSUMOS',
                'DEVUELTO_A_ASESORA', 'Pendiente', 'No iniciado'
            ]);
        }

        if ($status === 'entregados') {
            return $query->whereRaw('LOWER(estado) LIKE ?', ['%entregado%']);
        }

        if ($status === 'vencidos') {
            return $query->where(function ($q) {
                $q->whereNotNull('fecha_estimada_de_entrega')
                  ->whereDate('fecha_estimada_de_entrega', '<', now()->toDateString())
                  ->whereRaw('LOWER(estado) NOT LIKE ?', ['%entregado%']);
            });
        }

        return $query;
    }
}
