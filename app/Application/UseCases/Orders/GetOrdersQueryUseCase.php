<?php

namespace App\Application\UseCases\Orders;

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
        // Handle request for unique values for filters
        if ($request->has('get_unique_values') && $request->has('column')) {
            try {
                $values = $this->extendedQueryService->getUniqueValues($request->input('column'));
                return [
                    'type' => 'json',
                    'status' => 200,
                    'data' => ['unique_values' => $values],
                ];
            } catch (\InvalidArgumentException $e) {
                return [
                    'type' => 'json',
                    'status' => 400,
                    'data' => ['error' => 'Invalid column'],
                ];
            } catch (\Exception $e) {
                return [
                    'type' => 'json',
                    'status' => 500,
                    'data' => ['error' => 'Error fetching values: ' . $e->getMessage()],
                ];
            }
        }

        $query = $this->extendedQueryService->buildBaseQuery();
        $query = $this->extendedQueryService->applyRoleFilters($query, auth()->user(), $request);
        $query = $this->extendedSearchService->applySearchFilter($query, $request->input('search'));

        // Extraer y aplicar filtros dinámicos
        $filterData = $this->extendedFilterService->extractFiltersFromRequest($request);
        $query = $this->extendedFilterService->applyFiltersToQuery($query, $filterData['filters']);
        $filterTotalDias = $filterData['totalDiasFilter'];

        $currentYear = now()->year;
        $nextYear = now()->addYear()->year;
        $festivos = array_merge(
            FestivosColombiaService::obtenerFestivos($currentYear),
            FestivosColombiaService::obtenerFestivos($nextYear)
        );

        // Si hay filtro de total_de_dias_, necesitamos obtener todos los registros para calcular y filtrar
        if ($filterTotalDias !== null) {
            $todasOrdenes = $query->get();

            // Convertir a array para el cálculo
            $ordenesArray = $todasOrdenes->map(function ($orden) {
                return (object) $orden->getAttributes();
            })->toArray();

            $totalDiasCalculados = CacheCalculosService::getTotalDiasBatch($ordenesArray, $festivos);

            // Filtrar por total_de_dias_
            $ordenesFiltradas = $todasOrdenes->filter(function ($orden) use ($totalDiasCalculados, $filterTotalDias) {
                $totalDias = $totalDiasCalculados[$orden->numero_pedido] ?? 0;
                $match = in_array((int) $totalDias, $filterTotalDias, true);
                return $match;
            });

            // Paginar manualmente los resultados filtrados
            $currentPage = (int) $request->get('page', 1);
            $perPage = 25;
            $ordenes = new LengthAwarePaginator(
                $ordenesFiltradas->forPage($currentPage, $perPage)->values(),
                $ordenesFiltradas->count(),
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            );

            // Recalcular solo para las órdenes de la página actual (con caché inteligente)
            $totalDiasCalculados = CacheCalculosService::getTotalDiasBatch($ordenes->items(), $festivos);
        } else {
            // OPTIMIZACIÓN: Paginación a 25 items
            $ordenes = $query->paginate(25);

            // OPTIMIZACIÓN CRÍTICA: SOLO calcular para la página actual (25 items) con caché
            // No calcular para TODAS las 2257 órdenes - usa CacheCalculosService con TTL de 1 hora
            $totalDiasCalculados = CacheCalculosService::getTotalDiasBatch($ordenes->items(), $festivos);
        }

        // Obtener areasMap solo para los items de esta página (OPTIMIZACIÓN)
        $numeroPedidosPagina = array_map(function ($orden) {
            return $orden->numero_pedido;
        }, $ordenes->items());
        $areasMap = $this->processService->getLastProcessByOrderNumbers($numeroPedidosPagina);

        // Obtener encargados de "Creación Orden" para cada pedido
        $encargadosCreacionOrdenMap = $this->processService->getCreacionOrdenEncargados($numeroPedidosPagina);

        // Opciones de áreas disponibles (áreas de procesos)
        $areaOptions = AreaOptions::getArray();

        // FALLBACK: Si totalDiasCalculados está vacío o falta alguna orden, recalcular
        if (empty($totalDiasCalculados)) {
            $totalDiasCalculados = CacheCalculosService::getTotalDiasBatch($ordenes->items(), $festivos);
        } else {
            // Verificar que todas las órdenes tengan un valor
            foreach ($ordenes->items() as $orden) {
                if (!isset($totalDiasCalculados[$orden->numero_pedido])) {
                    $totalDiasCalculados[$orden->numero_pedido] =
                        CacheCalculosService::getTotalDias($orden->numero_pedido, $orden->estado);
                }
            }
        }

        if ($request->wantsJson()) {
            // Filtrar campos sensibles según el rol del usuario
            $ordenesFiltered = array_map(function ($orden) use ($areasMap, $encargadosCreacionOrdenMap) {
                return $this->transformService->transformarOrden($orden, $areasMap, $encargadosCreacionOrdenMap);
            }, $ordenes->items());

            // Retornar string vacío para que paginationManager.js genere el HTML con los estilos correctos
            $paginationHtml = '';

            // Determinar contexto y rol para renderizado de botones
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
                    'pagination_html' => $paginationHtml,
                ],
            ];
        }

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
                'modalContext'
            ),
        ];
    }
}
