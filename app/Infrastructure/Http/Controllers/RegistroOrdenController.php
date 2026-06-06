<?php
namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\RegistroOrdenValidationService;
use App\Services\RegistroOrdenCreationService;
use App\Services\RegistroOrdenUpdateService;
use App\Services\RegistroOrdenDeletionService;
use App\Services\RegistroOrdenNumberService;
use App\Services\RegistroOrdenPrendaService;
use App\Services\RegistroOrdenCacheService;
use App\Services\RegistroOrdenEntregasService;
use App\Services\RegistroOrdenProcessesService;
use App\Services\ReciboCosturaQueryService;
use App\Application\Pedidos\UseCases\Orders\CreateOrderUseCase;
use App\Application\Pedidos\UseCases\Orders\UpdateOrderUseCase;
use App\Application\Pedidos\UseCases\Orders\DeleteOrderUseCase;
use App\Application\Pedidos\UseCases\Orders\GetOrderUseCase;
use App\Application\Pedidos\UseCases\Orders\EditFullOrderUseCase;
use App\Application\Pedidos\UseCases\Orders\AddNovedadUseCase;
use App\Application\Pedidos\UseCases\Orders\SaveDiaEntregaUseCase;
use App\Application\UseCases\Receipts\GetSewingReceiptsUseCase;
use App\Application\UseCases\Receipts\GetBodegaReceiptsUseCase;
use App\Application\Pedidos\UseCases\Orders\FilterOrdersUseCase;
use App\Application\Pedidos\UseCases\Orders\SearchOrdersUseCase;
use App\Application\UseCases\Receipts\GetReflectiveReceiptsUseCase;
use App\Application\Pedidos\UseCases\Orders\UpdateNovedadesUseCase;
use App\Application\Pedidos\UseCases\Orders\GetFilterOptionsUseCase;
use App\Application\Pedidos\UseCases\Orders\GetColumnFilterOptionsUseCase;
use App\Application\Pedidos\UseCases\Orders\UpdatePedidoNumberUseCase;
use App\Application\Pedidos\UseCases\Orders\UpdateDescripcionPrendasUseCase;
use App\Application\Pedidos\UseCases\Orders\UpdateDescripcionPrendasRequest as UpdateDescripcionPrendasRequestDTO;
use App\Application\Pedidos\UseCases\Orders\GetAreaRecienteUseCase;
use App\Application\UseCases\Receipts\GetReceiptJsonUseCase;
use App\Application\UseCases\Receipts\ContarRecibosEjecutandoUseCase;
use App\Application\UseCases\Receipts\MarcarReciboVistoUseCase;
use App\Application\SupervisorPedidos\UseCases\GetPendingEmbroideryStampingReceiptsUseCase;
use App\Application\SupervisorPedidos\DTOs\GetPendingEmbroideryStampingReceiptsRequest;
use App\Services\FestivosColombiaService;
use App\Services\DiasHabilesService;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class RegistroOrdenController extends Controller
{
    use RegistroOrdenExceptionHandler;

    protected $validationService;
    protected $creationService;
    protected $updateService;
    protected $deletionService;
    protected $numberService;
    protected $prendaService;
    protected $cacheService;
    protected $entregasService;
    protected $processesService;
    protected $reciboCosturaQueryService;
    protected $createOrderUseCase;
    protected $updateOrderUseCase;
    protected $deleteOrderUseCase;
    protected $getOrderUseCase;
    protected $editFullOrderUseCase;
    protected $addNovedadUseCase;
    protected $saveDiaEntregaUseCase;
    protected $getSewingReceiptsUseCase;
    protected $getBodegaReceiptsUseCase;
    protected $filterOrdersUseCase;
    protected $searchOrdersUseCase;
    protected $getReflectiveReceiptsUseCase;
    protected $updateNovedadesUseCase;
    protected $getFilterOptionsUseCase;
    protected $getColumnFilterOptionsUseCase;
    protected $updatePedidoNumberUseCase;
    protected $updateDescripcionPrendasUseCase;
    protected $getReceiptJsonUseCase;
    protected $getAreaRecienteUseCase;
    protected $contarRecibosEjecutandoUseCase;
    protected $marcarReciboVistoUseCase;
    protected $getPendingEmbroideryStampingReceiptsUseCase;
    private DiasHabilesService $diasHabilesService;

    public function __construct(
        RegistroOrdenValidationService $validationService,
        RegistroOrdenCreationService $creationService,
        RegistroOrdenUpdateService $updateService,
        RegistroOrdenDeletionService $deletionService,
        RegistroOrdenNumberService $numberService,
        RegistroOrdenPrendaService $prendaService,
        RegistroOrdenCacheService $cacheService,
        RegistroOrdenEntregasService $entregasService,
        RegistroOrdenProcessesService $processesService,
        ReciboCosturaQueryService $reciboCosturaQueryService,
        CreateOrderUseCase $createOrderUseCase,
        UpdateOrderUseCase $updateOrderUseCase,
        DeleteOrderUseCase $deleteOrderUseCase,
        GetOrderUseCase $getOrderUseCase,
        EditFullOrderUseCase $editFullOrderUseCase,
        AddNovedadUseCase $addNovedadUseCase,
        SaveDiaEntregaUseCase $saveDiaEntregaUseCase,
        GetSewingReceiptsUseCase $getSewingReceiptsUseCase,
        GetBodegaReceiptsUseCase $getBodegaReceiptsUseCase,
        FilterOrdersUseCase $filterOrdersUseCase,
        SearchOrdersUseCase $searchOrdersUseCase,
        GetReflectiveReceiptsUseCase $getReflectiveReceiptsUseCase,
        UpdateNovedadesUseCase $updateNovedadesUseCase,
        GetFilterOptionsUseCase $getFilterOptionsUseCase,
        GetColumnFilterOptionsUseCase $getColumnFilterOptionsUseCase,
        UpdatePedidoNumberUseCase $updatePedidoNumberUseCase,
        UpdateDescripcionPrendasUseCase $updateDescripcionPrendasUseCase,
        GetReceiptJsonUseCase $getReceiptJsonUseCase,
        GetAreaRecienteUseCase $getAreaRecienteUseCase,
        ContarRecibosEjecutandoUseCase $contarRecibosEjecutandoUseCase,
        MarcarReciboVistoUseCase $marcarReciboVistoUseCase,
        GetPendingEmbroideryStampingReceiptsUseCase $getPendingEmbroideryStampingReceiptsUseCase,
        DiasHabilesService $diasHabilesService
    )
    {
        $this->validationService = $validationService;
        $this->creationService = $creationService;
        $this->updateService = $updateService;
        $this->deletionService = $deletionService;
        $this->numberService = $numberService;
        $this->prendaService = $prendaService;
        $this->cacheService = $cacheService;
        $this->entregasService = $entregasService;
        $this->processesService = $processesService;
        $this->reciboCosturaQueryService = $reciboCosturaQueryService;
        $this->createOrderUseCase = $createOrderUseCase;
        $this->updateOrderUseCase = $updateOrderUseCase;
        $this->deleteOrderUseCase = $deleteOrderUseCase;
        $this->getOrderUseCase = $getOrderUseCase;
        $this->editFullOrderUseCase = $editFullOrderUseCase;
        $this->addNovedadUseCase = $addNovedadUseCase;
        $this->saveDiaEntregaUseCase = $saveDiaEntregaUseCase;
        $this->getSewingReceiptsUseCase = $getSewingReceiptsUseCase;
        $this->getBodegaReceiptsUseCase = $getBodegaReceiptsUseCase;
        $this->filterOrdersUseCase = $filterOrdersUseCase;
        $this->searchOrdersUseCase = $searchOrdersUseCase;
        $this->getReflectiveReceiptsUseCase = $getReflectiveReceiptsUseCase;
        $this->updateNovedadesUseCase = $updateNovedadesUseCase;
        $this->getFilterOptionsUseCase = $getFilterOptionsUseCase;
        $this->getColumnFilterOptionsUseCase = $getColumnFilterOptionsUseCase;
        $this->updatePedidoNumberUseCase = $updatePedidoNumberUseCase;
        $this->updateDescripcionPrendasUseCase = $updateDescripcionPrendasUseCase;
        $this->getReceiptJsonUseCase = $getReceiptJsonUseCase;
        $this->getAreaRecienteUseCase = $getAreaRecienteUseCase;
        $this->contarRecibosEjecutandoUseCase = $contarRecibosEjecutandoUseCase;
        $this->marcarReciboVistoUseCase = $marcarReciboVistoUseCase;
        $this->getPendingEmbroideryStampingReceiptsUseCase = $getPendingEmbroideryStampingReceiptsUseCase;
        $this->diasHabilesService = $diasHabilesService;
    }

    public function getNextPedido()
    {
        $pedidoInfo = $this->numberService->getNextPedidoInfo();
        return response()->json($pedidoInfo);
    }

    public function validatePedido(Request $request)
    {
        $request->validate(['pedido' => 'required|integer']);
        
        $pedido = $request->input('pedido');
        $nextInfo = $this->numberService->getNextPedidoInfo();
        $isValid = $this->numberService->isNextExpected($pedido);

        return response()->json([
            'valid' => $isValid,
            'next_pedido' => $nextInfo['next_pedido'],
        ]);
    }

    public function store(Request $request)
    {
        return $this->createOrderUseCase->execute($request);
    }

    public function update(Request $request, $pedido)
    {
        return $this->updateOrderUseCase->execute($request, $pedido);
    }

   

  
    public function destroy($pedido)
    {
        return $this->deleteOrderUseCase->execute($pedido);
    }

    public function getEntregas($pedido)
    {
        return $this->tryExec(function() use ($pedido) {
            $entregas = $this->entregasService->getEntregas($pedido);
            return response()->json($entregas);
        });
    }

    /**
     * Invalidar cache de Dias calculados para una orden especifica
     * Se ejecuta cuando se actualiza o elimina una orden
     * 
     * Delegado a: RegistroOrdenCacheService::invalidateDaysCache()
     */
    private function invalidarCacheDias($pedido): void
    {
        $this->cacheService->invalidateDaysCache($pedido);
    }

    public function updatePedido(Request $request)
    {
        return response()->json($this->updatePedidoNumberUseCase->execute($request));
    }

    /**
     * Obtener registros por orden (API para el modal de edicion)
     * Retorna las prendas desde la nueva arquitectura
     */
    public function getRegistrosPorOrden($pedido)
    {
        return $this->tryExec(function() use ($pedido) {
            $prendas = $this->prendaService->getPrendasArray($pedido);
            return response()->json($prendas);
        });
    }

    /**
     * Editar orden completa
     */
    public function editFullOrder(Request $request, $pedido)
    {
        return $this->editFullOrderUseCase->execute($request, $pedido);
    }

    /**
     * Actualizar descripcion y regenerar registros_por_orden basado en el contenido
     */
    public function updateDescripcionPrendas(Request $request)
    {
        return $this->tryExec(function() use ($request) {
            $validated = $this->validationService->validateUpdateDescripcionRequest($request);

            $dto = new UpdateDescripcionPrendasRequestDTO(
                pedido: (string) $validated['pedido'],
                descripcion: $validated['descripcion'],
                userId: auth()->id(),
            );

            return response()->json($this->updateDescripcionPrendasUseCase->execute($dto));
        });
    }

    /**
     * Obtener detalles de una orden especifica para el modal
     * GET /orders/{numero_pedido}
     */
    public function show($numeroPedido)
    {
        return $this->getOrderUseCase->execute($numeroPedido);
    }
    

    /**
     * Obtener todas las opciones disponibles para filtros
     * GET /registros/filter-options
     */
    public function getFilterOptions()
    {
        return response()->json($this->getFilterOptionsUseCase->execute());
    }

    /**
     * Obtener opciones de una columna especifica con paginacion y busqueda
     * GET /registros/filter-column-options/{column}
     */
    public function getColumnFilterOptions($column, Request $request)
    {
        return response()->json($this->getColumnFilterOptionsUseCase->execute($column, $request));
    }

    /**
     * Filtrar ordenes por criterios especificos
     * POST /registros/filter-orders
     */
    public function filterOrders(Request $request)
    {
        return response()->json($this->filterOrdersUseCase->execute($request));
    }
    

    /**
     * busqueda simple en tiempo real
     * POST /registros/search
     */
    public function searchOrders(Request $request)
    {
        return response()->json($this->searchOrdersUseCase->execute($request));
    }

    /**
     * Agrega una nueva novedad al final del campo (con usuario, fecha y hora)
     * Endpoint: POST /api/ordenes/{numero_pedido}/novedades/add
     */
    public function addNovedad(Request $request, $numeroPedido)
    {
        return $this->addNovedadUseCase->execute((int) $numeroPedido, $request->input('novedad', ''));
    }

    public function updateNovedades(Request $request, $numeroPedido)
    {
        return $this->updateNovedadesUseCase->execute($request, $numeroPedido);
    }

    /**
     * Mostrar recibos de costura por numero de recibo
     */
    public function recibosCostura(Request $request)
    {
        $datos = $this->getSewingReceiptsUseCase->execute($request);
        // No convertir a collect si ya es un paginator
        if (!($datos['recibos'] instanceof \Illuminate\Pagination\LengthAwarePaginator)) {
            $datos['recibos'] = collect($datos['recibos']);
        }
        return view('registros.recibos-costura', $datos);
    }

    /**
     * Mostrar recibos de bodega (vista independiente con mismo diseño de costura)
     */
    public function recibosBodega(Request $request)
    {
        $datos = $this->getBodegaReceiptsUseCase->execute($request);
        if (!($datos['recibos'] instanceof \Illuminate\Pagination\LengthAwarePaginator)) {
            $datos['recibos'] = collect($datos['recibos']);
        }
        return view('registros.recibos-bodega', $datos);
    }

    /**
     * Mostrar recibos de reflectivo aprobados
     */
    public function recibosReflectivo(Request $request)
    {
        $datos = $this->getReflectiveReceiptsUseCase->execute($request);
        // No convertir a collect si ya es un paginator
        if (!($datos['recibos'] instanceof \Illuminate\Pagination\LengthAwarePaginator)) {
            $datos['recibos'] = collect($datos['recibos']);
        }
        return view('registros.recibos-reflectivo', $datos);
    }

    /**
     * Vista nueva fuera de supervisor para pendientes de bordado/estampado.
     */
    public function recibosBordadoEstampado(Request $request)
    {
        $tiposPermitidos = ['BORDADO', 'ESTAMPADO', 'DTF', 'SUBLIMADO'];
        $tipoFiltro = strtoupper((string) $request->query('tipo', 'BORDADO'));
        if (!in_array($tipoFiltro, $tiposPermitidos, true)) {
            $tipoFiltro = 'BORDADO';
        }

        $areaFiltro = trim((string) $request->query('area', ''));

        $requestDTO = new GetPendingEmbroideryStampingReceiptsRequest(
            busqueda: $request->input('busqueda')
        );
        $response = $this->getPendingEmbroideryStampingReceiptsUseCase->execute($requestDTO);

        $allProcesses = collect($response->getProcesses())
            ->sortByDesc(function ($proceso) {
                $item = (array) $proceso;
                $numeroRecibo = (int) ($item['numero_recibo'] ?? 0);
                $fechaCreacion = strtotime((string) ($item['fecha_creacion'] ?? '')) ?: 0;

                // Prioriza recibos más recientes y desempata por consecutivo de recibo.
                return sprintf('%010d-%010d', $numeroRecibo, $fechaCreacion);
            })
            ->values();

        $conteoBordado = $allProcesses->filter(function ($proceso) {
            $item = (array) $proceso;
            return strtoupper((string) ($item['tipo_recibo'] ?? '')) === 'BORDADO';
        })->count();
        $conteoEstampado = $allProcesses->filter(function ($proceso) {
            $item = (array) $proceso;
            return strtoupper((string) ($item['tipo_recibo'] ?? '')) === 'ESTAMPADO';
        })->count();
        $conteoDtf = $allProcesses->filter(function ($proceso) {
            $item = (array) $proceso;
            return strtoupper((string) ($item['tipo_recibo'] ?? '')) === 'DTF';
        })->count();
        $conteoSublimado = $allProcesses->filter(function ($proceso) {
            $item = (array) $proceso;
            return strtoupper((string) ($item['tipo_recibo'] ?? '')) === 'SUBLIMADO';
        })->count();

        // GUARDAR $allProcessesByType ANTES de filtrar por área (para calcular conteos después)
        $allProcessesByType = $allProcesses->filter(function ($proceso) use ($tipoFiltro) {
            $item = (array) $proceso;
            return strtoupper((string) ($item['tipo_recibo'] ?? '')) === $tipoFiltro;
        })
        ->sortByDesc(function ($proceso) {
            $item = (array) $proceso;
            $numeroRecibo = (int) ($item['numero_recibo'] ?? 0);
            $fechaCreacion = strtotime((string) ($item['fecha_creacion'] ?? '')) ?: 0;

            // Garantiza orden descendente dentro del tipo filtrado.
            return sprintf('%010d-%010d', $numeroRecibo, $fechaCreacion);
        })
        ->values();

        $allProcesses = $allProcessesByType;

        // Aplicar filtro de área si se proporciona
        if (!empty($areaFiltro)) {
            $prendaIdsTemporal = $allProcesses
                ->map(fn ($proceso) => (int) ((array) $proceso)['prenda_id'])
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            $areasPorPrendaTemporal = [];
            if (!empty($prendaIdsTemporal)) {
                $rowsArea = \DB::table('prenda_areas_logo_pedido')
                    ->select(['prenda_pedido_id', 'area'])
                    ->whereIn('prenda_pedido_id', $prendaIdsTemporal)
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->get();

                foreach ($rowsArea as $row) {
                    $prendaId = (int) ($row->prenda_pedido_id ?? 0);
                    if ($prendaId <= 0 || isset($areasPorPrendaTemporal[$prendaId])) {
                        continue;
                    }
                    $areasPorPrendaTemporal[$prendaId] = (string) ($row->area ?? '');
                }
            }

            // Comparar áreas directamente sin normalizar (como en pedidos-logo)
            $allProcesses = $allProcesses->filter(function ($proceso) use ($areaFiltro, $areasPorPrendaTemporal) {
                $prendaId = (int) ((array) $proceso)['prenda_id'];
                $areaRaw = (string) ($areasPorPrendaTemporal[$prendaId] ?? 'PENDIENTE');
                return $areaRaw === $areaFiltro;
            })->values();
        }

        $perPage = (int) $request->query('per_page', 25);
        $perPage = max(10, min($perPage, 100));
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $offset = max(0, ($currentPage - 1) * $perPage);

        $procesosConCantidad = new LengthAwarePaginator(
            $allProcesses->slice($offset, $perPage)->values(),
            $allProcesses->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $prendaIdsPagina = $procesosConCantidad->getCollection()
            ->map(fn ($proceso) => (int) ((array) $proceso)['prenda_id'])
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $tiposPagina = $procesosConCantidad->getCollection()
            ->map(fn ($proceso) => strtoupper(trim((string) (((array) $proceso)['tipo_recibo'] ?? ''))))
            ->filter(fn ($tipo) => $tipo !== '')
            ->unique()
            ->values()
            ->all();
        $numerosPagina = $procesosConCantidad->getCollection()
            ->map(fn ($proceso) => (int) (((array) $proceso)['numero_recibo'] ?? 0))
            ->filter(fn ($numero) => $numero > 0)
            ->unique()
            ->values()
            ->all();

        $reciboIdPorKey = [];
        if (!empty($prendaIdsPagina) && !empty($tiposPagina) && !empty($numerosPagina)) {
            $rowsConsecutivos = \DB::table('consecutivos_recibos_pedidos')
                ->select(['id', 'prenda_id', 'tipo_recibo', 'consecutivo_actual'])
                ->whereIn('prenda_id', $prendaIdsPagina)
                ->whereIn('tipo_recibo', $tiposPagina)
                ->whereIn('consecutivo_actual', $numerosPagina)
                ->orderByDesc('id')
                ->get();

            foreach ($rowsConsecutivos as $row) {
                $prendaIdKey = (int) ($row->prenda_id ?? 0);
                $tipoKey = strtoupper(trim((string) ($row->tipo_recibo ?? '')));
                $numeroKey = (int) ($row->consecutivo_actual ?? 0);
                if ($prendaIdKey <= 0 || $tipoKey === '' || $numeroKey <= 0) {
                    continue;
                }

                $key = $prendaIdKey . '|' . $tipoKey . '|' . $numeroKey;
                if (!isset($reciboIdPorKey[$key])) {
                    $reciboIdPorKey[$key] = (int) ($row->id ?? 0);
                }
            }
        }

        $user = auth()->user();
        $puedeGestionarCheckLogo = (bool) ($user && $user->hasRole('visualizador_recibos_logo'));
        $checksActivos = [];
        if ($puedeGestionarCheckLogo && !empty($reciboIdPorKey)) {
            $reciboIds = collect($reciboIdPorKey)->filter(fn ($id) => (int) $id > 0)->values()->all();
            if (!empty($reciboIds)) {
                $checksActivos = \DB::table('recibos_logo_checks')
                    ->where('user_id', (int) $user->id)
                    ->whereIn('consecutivo_recibo_id', $reciboIds)
                    ->where('checked', true)
                    ->pluck('checked', 'consecutivo_recibo_id')
                    ->toArray();
            }
        }

        $prendaIds = $procesosConCantidad->getCollection()
            ->map(fn ($proceso) => (int) (is_array($proceso) ? ($proceso['prenda_id'] ?? 0) : ($proceso->prenda_id ?? 0)))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $areasPorPrenda = [];
        if (!empty($prendaIds)) {
            $rowsArea = \DB::table('prenda_areas_logo_pedido')
                ->select(['prenda_pedido_id', 'area'])
                ->whereIn('prenda_pedido_id', $prendaIds)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get();

            foreach ($rowsArea as $row) {
                $prendaId = (int) ($row->prenda_pedido_id ?? 0);
                if ($prendaId <= 0 || isset($areasPorPrenda[$prendaId])) {
                    continue;
                }
                $areasPorPrenda[$prendaId] = (string) ($row->area ?? '');
            }
        }

        $tiposRecibo = $procesosConCantidad->getCollection()
            ->map(fn ($proceso) => strtoupper(trim((string) (is_array($proceso) ? ($proceso['tipo_recibo'] ?? '') : ($proceso->tipo_recibo ?? '')))))
            ->filter(fn ($tipo) => $tipo !== '')
            ->unique()
            ->values()
            ->all();

        $numerosRecibo = $procesosConCantidad->getCollection()
            ->map(fn ($proceso) => (int) (is_array($proceso) ? ($proceso['numero_recibo'] ?? 0) : ($proceso->numero_recibo ?? 0)))
            ->filter(fn ($numero) => $numero > 0)
            ->unique()
            ->values()
            ->all();

        $createdPorReciboKey = [];
        $reciboIdPorKey = [];
        if (!empty($prendaIds) && !empty($tiposRecibo) && !empty($numerosRecibo)) {
            $rowsConsecutivos = \DB::table('consecutivos_recibos_pedidos')
                ->select(['id', 'prenda_id', 'tipo_recibo', 'consecutivo_actual', 'created_at'])
                ->whereIn('prenda_id', $prendaIds)
                ->whereIn('tipo_recibo', $tiposRecibo)
                ->whereIn('consecutivo_actual', $numerosRecibo)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get();

            foreach ($rowsConsecutivos as $row) {
                $prendaId = (int) ($row->prenda_id ?? 0);
                $tipo = strtoupper(trim((string) ($row->tipo_recibo ?? '')));
                $numero = (int) ($row->consecutivo_actual ?? 0);
                if ($prendaId <= 0 || $tipo === '' || $numero <= 0) {
                    continue;
                }

                $key = $prendaId . '|' . $tipo . '|' . $numero;
                if (!isset($createdPorReciboKey[$key])) {
                    $createdPorReciboKey[$key] = $row->created_at;
                }
                if (!isset($reciboIdPorKey[$key])) {
                    $reciboIdPorKey[$key] = (int) ($row->id ?? 0);
                }
            }
        }

        $reciboIds = collect($reciboIdPorKey)->filter(fn ($id) => (int) $id > 0)->values()->all();
        $checksActivos = [];
        $userId = Auth::id();
        if (!empty($reciboIds) && $userId) {
            $checksActivos = \DB::table('recibos_logo_checks')
                ->where('user_id', (int) $userId)
                ->whereIn('consecutivo_recibo_id', $reciboIds)
                ->where('checked', true)
                ->pluck('checked', 'consecutivo_recibo_id')
                ->toArray();
        }

        $recibosNormalizados = $procesosConCantidad->getCollection()->map(function ($proceso, int $index) use ($areasPorPrenda, $createdPorReciboKey, $reciboIdPorKey, $checksActivos) {
            $procesoArray = (array) $proceso;
            $numeroRecibo = (string) ($procesoArray['numero_recibo'] ?? '');
            $tipoRecibo = (string) ($procesoArray['tipo_recibo'] ?? '');
            $nombrePrenda = (string) ($procesoArray['nombre_prenda'] ?? '');
            $cliente = (string) ($procesoArray['cliente'] ?? '');
            $asesor = (string) ($procesoArray['asesor'] ?? '');
            $cantidad = (int) ($procesoArray['cantidad_total_prendas'] ?? 0);
            $fechaCreacion = $procesoArray['fecha_creacion'] ?? null;
            $prendaId = (int) ($procesoArray['prenda_id'] ?? 0);
            $tipoReciboUpper = strtoupper(trim($tipoRecibo !== '' ? $tipoRecibo : 'BORDADO'));
            $numeroReciboInt = (int) $numeroRecibo;
            $reciboKey = $prendaId . '|' . $tipoReciboUpper . '|' . $numeroReciboInt;
            $pedidoParcialId = (int) ($procesoArray['pedido_parcial_id'] ?? 0);
            $esParcial = !empty($procesoArray['es_parcial']) || $pedidoParcialId > 0;
            $consecutivoReciboId = (int) ($reciboIdPorKey[$reciboKey] ?? 0);
            $fechaCreacionDesdeConsecutivo = $createdPorReciboKey[$reciboKey] ?? null;
            if ($fechaCreacionDesdeConsecutivo) {
                $fechaCreacion = $fechaCreacionDesdeConsecutivo;
            }
            $areaRaw = strtoupper((string) ($areasPorPrenda[$prendaId] ?? 'PENDIENTE'));
            $areaNormalizada = match ($areaRaw) {
                'ESTAMPANDO', 'ESTAMPADO' => 'Estampado',
                'BORDANDO', 'BORDADO' => 'Bordado',
                'CORTE_Y_APLIQUE' => 'Corte',
                'PENDIENTE_CONFIRMAR', 'PENDIENTE_DISENO', 'PENDIENTE' => 'Pendiente',
                default => ucfirst(strtolower(str_replace('_', ' ', $areaRaw))),
            };

            return [
                'id' => (int) ($procesoArray['id'] ?? ($index + 1)),
                'pedido_produccion_id' => (int) ($procesoArray['pedido_id'] ?? 0),
                'prenda_id' => $prendaId,
                'consecutivo_actual' => $numeroRecibo,
                'tipo_recibo' => $tipoRecibo !== '' ? $tipoRecibo : 'BORDADO',
                'es_parcial' => $esParcial,
                'pedido_parcial_id' => $pedidoParcialId > 0 ? $pedidoParcialId : null,
                'estado' => 'En Ejecución',
                'area' => $areaNormalizada,
                'dias_calculados' => 0,
                'cantidad_total' => $cantidad,
                'descripcion_detallada' => trim($nombrePrenda) !== '' ? ('PRENDA: ' . $nombrePrenda) : 'PRENDA: SIN DESCRIPCIÓN',
                'novedades' => '',
                'encargado_orden' => $asesor !== '' ? $asesor : '-',
                'fecha_creacion' => $fechaCreacion,
                'created_at' => $fechaCreacion,
                'fecha_estimada' => null,
                'consecutivo_recibo_id' => $consecutivoReciboId,
                'check_logo_recibo' => (bool) ($checksActivos[$consecutivoReciboId] ?? false),
                'pedido_info' => [
                    'cliente' => $cliente !== '' ? $cliente : 'N/A',
                    'fecha_creacion_orden' => $fechaCreacion,
                ],
            ];
        });

        $recibos = new LengthAwarePaginator(
            $recibosNormalizados,
            $procesosConCantidad->total(),
            $procesosConCantidad->perPage(),
            $procesosConCantidad->currentPage(),
            [
                'path' => $procesosConCantidad->path(),
                'query' => $request->query(),
            ]
        );

        $totalCantidadGlobal = (int) $allProcesses->sum(function ($proceso) {
            return (int) (is_array($proceso) ? ($proceso['cantidad_total_prendas'] ?? 0) : ($proceso->cantidad_total_prendas ?? 0));
        });

        // Calcular conteos por área para TODOS los recibos del tipo filtrado (ANTES del filtro de área)
        // IMPORTANTE: Devolver áreas SIN normalizar, directamente del enum (como en pedidos-logo)
        $conteosPorArea = [];

        // Obtener prendas de todos los recibos del tipo filtrado (usar $allProcessesByType, NO el filtrado por área)
        $prendaIdsAllRecibos = $allProcessesByType
            ->map(fn ($proceso) => (int) ((array) $proceso)['prenda_id'])
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (!empty($prendaIdsAllRecibos)) {
            // Obtener la última área de cada prenda
            $rowsAreaAll = \DB::table('prenda_areas_logo_pedido')
                ->select(['prenda_pedido_id', 'area'])
                ->whereIn('prenda_pedido_id', $prendaIdsAllRecibos)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get();

            // Crear mapa de área por prenda (tomando la más reciente)
            $areasPorPrendaAll = [];
            foreach ($rowsAreaAll as $row) {
                $prendaId = (int) ($row->prenda_pedido_id ?? 0);
                if ($prendaId <= 0 || isset($areasPorPrendaAll[$prendaId])) {
                    continue;
                }
                $areasPorPrendaAll[$prendaId] = (string) ($row->area ?? 'PENDIENTE');
            }

            // Contar recibos por área RAW (sin normalizar) - igual que en pedidos-logo
            foreach ($allProcessesByType as $proceso) {
                $prendaId = (int) ((array) $proceso)['prenda_id'];
                $areaRaw = (string) ($areasPorPrendaAll[$prendaId] ?? 'PENDIENTE');
                
                if (!isset($conteosPorArea[$areaRaw])) {
                    $conteosPorArea[$areaRaw] = 0;
                }
                $conteosPorArea[$areaRaw]++;
            }
        }

        return view('registros.recibos-bordado-estampado', compact(
            'recibos',
            'totalCantidadGlobal',
            'tipoFiltro',
            'conteoBordado',
            'conteoEstampado',
            'conteoDtf',
            'conteoSublimado',
            'conteosPorArea'
        ));
    }

    /**
     * Obtener áreas disponibles por tipo de recibo (para filtros dinámicos AJAX)
     * GET /api/recibos-bordado-estampado/areas-disponibles?tipo=BORDADO
     * Retorna áreas sin normalizar, directamente del enum de la BD
     */
    public function obtenerAreasDisponiblesPorTipo(Request $request)
    {
        try {
            $tipoFiltro = strtoupper((string) $request->query('tipo', 'BORDADO'));
            $tiposPermitidos = ['BORDADO', 'ESTAMPADO', 'DTF', 'SUBLIMADO'];
            
            if (!in_array($tipoFiltro, $tiposPermitidos, true)) {
                $tipoFiltro = 'BORDADO';
            }

            // Obtener todos los procesos del tipo solicitado
            $requestDTO = new GetPendingEmbroideryStampingReceiptsRequest(
                busqueda: ''
            );
            $response = $this->getPendingEmbroideryStampingReceiptsUseCase->execute($requestDTO);

            $allProcesses = collect($response->getProcesses())
                ->filter(function ($proceso) use ($tipoFiltro) {
                    $item = (array) $proceso;
                    return strtoupper((string) ($item['tipo_recibo'] ?? '')) === $tipoFiltro;
                })
                ->values();

            // Obtener prendas únicas
            $prendaIds = $allProcesses
                ->map(fn ($proceso) => (int) ((array) $proceso)['prenda_id'])
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            // Conteos por área (sin normalizar - directas del enum)
            $conteosPorArea = [];

            if (!empty($prendaIds)) {
                // Obtener áreas más recientes por prenda (sin normalizar)
                $rowsArea = \DB::table('prenda_areas_logo_pedido')
                    ->select(['prenda_pedido_id', 'area'])
                    ->whereIn('prenda_pedido_id', $prendaIds)
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->get();

                // Crear mapa de área por prenda (más reciente)
                $areasPorPrenda = [];
                foreach ($rowsArea as $row) {
                    $prendaId = (int) ($row->prenda_pedido_id ?? 0);
                    if ($prendaId <= 0 || isset($areasPorPrenda[$prendaId])) {
                        continue;
                    }
                    $areasPorPrenda[$prendaId] = (string) ($row->area ?? 'PENDIENTE');
                }

                // Contar por área RAW (sin normalizar)
                foreach ($allProcesses as $proceso) {
                    $prendaId = (int) ((array) $proceso)['prenda_id'];
                    $areaRaw = (string) ($areasPorPrenda[$prendaId] ?? 'PENDIENTE');
                    
                    if (!isset($conteosPorArea[$areaRaw])) {
                        $conteosPorArea[$areaRaw] = 0;
                    }
                    $conteosPorArea[$areaRaw]++;
                }
            }

            // Retornar solo áreas con contador > 0
            $areasDisponibles = array_filter($conteosPorArea, fn ($count) => $count > 0);

            return response()->json([
                'success' => true,
                'tipo' => $tipoFiltro,
                'areas' => $areasDisponibles,
            ]);
        } catch (\Exception $e) {
            \Log::error('[obtenerAreasDisponiblesPorTipo] Error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener áreas disponibles',
                'areas' => [],
            ], 500);
        }
    }

    /**
     * Búsqueda AJAX de recibos de costura
     */
    public function searchRecibosCostura(Request $request)
    {
        $searchTerm = $request->input('search', '');
        $page = $request->input('page', 1);
        $perPage = 25;

        // Crear un request con el término de búsqueda
        $searchRequest = new Request(['search' => $searchTerm, 'page' => $page]);

        $datos = $this->getSewingReceiptsUseCase->execute($searchRequest);

        $recibos = $datos['recibos'];
        
        // Debug: Ver qué tipo de objeto es
        \Log::info('Search Debug - Tipo de recibos: ' . get_class($recibos));
        \Log::info('Search Debug - Es Collection: ' . ($recibos instanceof \Illuminate\Support\Collection ? 'true' : 'false'));
        \Log::info('Search Debug - Es LengthAwarePaginator: ' . ($recibos instanceof \Illuminate\Pagination\LengthAwarePaginator ? 'true' : 'false'));
        
        // Si es una Collection (sin paginación)
        if ($recibos instanceof \Illuminate\Support\Collection) {
            $recibosArray = $recibos->toArray();
            return response()->json([
                'success' => true,
                'recibos' => $recibosArray,
                'total' => count($recibosArray),
                'total_cantidad' => $datos['total_cantidad'],
                'current_page' => 1,
                'last_page' => 1,
                'from' => count($recibosArray) > 0 ? 1 : 0,
                'to' => count($recibosArray)
            ]);
        }
        
        // Si es un paginator, extraer los datos y la información de paginación
        if ($recibos instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            // Calcular from y to manualmente para evitar el error
            $currentPage = $recibos->currentPage();
            $perPage = $recibos->perPage();
            $total = $recibos->total();
            $from = $total > 0 ? (($currentPage - 1) * $perPage) + 1 : 0;
            $to = $from + $recibos->count() - 1;
            
            return response()->json([
                'success' => true,
                'recibos' => $recibos->items(),
                'total' => $total,
                'total_cantidad' => $datos['total_cantidad'],
                'current_page' => $currentPage,
                'last_page' => $recibos->lastPage(),
                'from' => $from,
                'to' => $to
            ]);
        }

        // Si es un array
        return response()->json([
            'success' => true,
            'recibos' => $recibos,
            'total' => count($recibos),
            'total_cantidad' => $datos['total_cantidad'],
            'current_page' => 1,
            'last_page' => 1,
            'from' => count($recibos) > 0 ? 1 : 0,
            'to' => count($recibos)
        ]);
    }

    /**
     * Búsqueda AJAX de recibos de reflectivo
     */
    public function searchRecibosReflectivo(Request $request)
    {
        $searchTerm = $request->input('search', '');
        $page = $request->input('page', 1);
        $perPage = 25;

        // Crear un request con el término de búsqueda
        $searchRequest = new Request(['search' => $searchTerm, 'page' => $page]);

        $datos = $this->getReflectiveReceiptsUseCase->execute($searchRequest);

        $recibos = $datos['recibos'];
        
        // Debug: Ver qué tipo de objeto es
        \Log::info('Search Debug Reflectivo - Tipo de recibos: ' . get_class($recibos));
        
        // Si es una Collection (sin paginación)
        if ($recibos instanceof \Illuminate\Support\Collection) {
            $recibosArray = $recibos->toArray();
            return response()->json([
                'success' => true,
                'recibos' => $recibosArray,
                'total' => count($recibosArray),
                'total_cantidad' => $datos['total_cantidad'],
                'current_page' => 1,
                'last_page' => 1,
                'from' => count($recibosArray) > 0 ? 1 : 0,
                'to' => count($recibosArray)
            ]);
        }
        
        // Si es un paginator, extraer los datos y la información de paginación
        if ($recibos instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            // Calcular from y to manualmente para evitar el error
            $currentPage = $recibos->currentPage();
            $perPage = $recibos->perPage();
            $total = $recibos->total();
            $from = $total > 0 ? (($currentPage - 1) * $perPage) + 1 : 0;
            $to = $from + $recibos->count() - 1;
            
            return response()->json([
                'success' => true,
                'recibos' => $recibos->items(),
                'total' => $total,
                'total_cantidad' => $datos['total_cantidad'],
                'current_page' => $currentPage,
                'last_page' => $recibos->lastPage(),
                'from' => $from,
                'to' => $to
            ]);
        }

        // Si es un array
        return response()->json([
            'success' => true,
            'recibos' => $recibos,
            'total' => count($recibos),
            'total_cantidad' => $datos['total_cantidad'],
            'current_page' => 1,
            'last_page' => 1,
            'from' => count($recibos) > 0 ? 1 : 0,
            'to' => count($recibos)
        ]);
    }

    /**
     * Búsqueda AJAX de recibos de bordado/estampado
     */
    public function searchRecibosBordadoEstampado(Request $request)
    {
        $searchTerm = $request->input('search', '');
        $page = $request->input('page', 1);
        $perPage = 25;

        $requestDTO = new GetPendingEmbroideryStampingReceiptsRequest(
            busqueda: $searchTerm
        );
        $response = $this->getPendingEmbroideryStampingReceiptsUseCase->execute($requestDTO);

        $allProcesses = collect($response->getProcesses())
            ->sortByDesc(function ($proceso) {
                $item = (array) $proceso;
                $numeroRecibo = (int) ($item['numero_recibo'] ?? 0);
                $fechaCreacion = strtotime((string) ($item['fecha_creacion'] ?? '')) ?: 0;

                // Mantener el mismo criterio descendente de la vista principal.
                return sprintf('%010d-%010d', $numeroRecibo, $fechaCreacion);
            })
            ->values();

        // Obtener áreas por prenda
        $prendaIds = $allProcesses
            ->map(fn ($proceso) => (int) (is_array($proceso) ? ($proceso['prenda_id'] ?? 0) : ($proceso->prenda_id ?? 0)))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $areasPorPrenda = [];
        if (!empty($prendaIds)) {
            $rowsArea = \DB::table('prenda_areas_logo_pedido')
                ->select(['prenda_pedido_id', 'area'])
                ->whereIn('prenda_pedido_id', $prendaIds)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get();

            foreach ($rowsArea as $row) {
                $prendaId = (int) ($row->prenda_pedido_id ?? 0);
                if ($prendaId <= 0 || isset($areasPorPrenda[$prendaId])) {
                    continue;
                }
                $areasPorPrenda[$prendaId] = (string) ($row->area ?? '');
            }
        }

        // Paginar los resultados
        $currentPage = $page;
        $offset = max(0, ($currentPage - 1) * $perPage);

        $procesosConCantidad = new LengthAwarePaginator(
            $allProcesses->slice($offset, $perPage)->values(),
            $allProcesses->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $prendaIdsPagina = $procesosConCantidad->getCollection()
            ->map(fn ($proceso) => (int) ((array) $proceso)['prenda_id'])
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $tiposPagina = $procesosConCantidad->getCollection()
            ->map(fn ($proceso) => strtoupper(trim((string) (((array) $proceso)['tipo_recibo'] ?? ''))))
            ->filter(fn ($tipo) => $tipo !== '')
            ->unique()
            ->values()
            ->all();
        $numerosPagina = $procesosConCantidad->getCollection()
            ->map(fn ($proceso) => (int) (((array) $proceso)['numero_recibo'] ?? 0))
            ->filter(fn ($numero) => $numero > 0)
            ->unique()
            ->values()
            ->all();

        $reciboIdPorKey = [];
        if (!empty($prendaIdsPagina) && !empty($tiposPagina) && !empty($numerosPagina)) {
            $rowsConsecutivos = \DB::table('consecutivos_recibos_pedidos')
                ->select(['id', 'prenda_id', 'tipo_recibo', 'consecutivo_actual'])
                ->whereIn('prenda_id', $prendaIdsPagina)
                ->whereIn('tipo_recibo', $tiposPagina)
                ->whereIn('consecutivo_actual', $numerosPagina)
                ->orderByDesc('id')
                ->get();

            foreach ($rowsConsecutivos as $row) {
                $prendaIdKey = (int) ($row->prenda_id ?? 0);
                $tipoKey = strtoupper(trim((string) ($row->tipo_recibo ?? '')));
                $numeroKey = (int) ($row->consecutivo_actual ?? 0);
                if ($prendaIdKey <= 0 || $tipoKey === '' || $numeroKey <= 0) {
                    continue;
                }
                $key = $prendaIdKey . '|' . $tipoKey . '|' . $numeroKey;
                if (!isset($reciboIdPorKey[$key])) {
                    $reciboIdPorKey[$key] = (int) ($row->id ?? 0);
                }
            }
        }

        $user = auth()->user();
        $puedeGestionarCheckLogo = (bool) ($user && $user->hasRole('visualizador_recibos_logo'));
        $checksActivos = [];
        if ($puedeGestionarCheckLogo && !empty($reciboIdPorKey)) {
            $reciboIds = collect($reciboIdPorKey)->filter(fn ($id) => (int) $id > 0)->values()->all();
            if (!empty($reciboIds)) {
                $checksActivos = \DB::table('recibos_logo_checks')
                    ->where('user_id', (int) $user->id)
                    ->whereIn('consecutivo_recibo_id', $reciboIds)
                    ->where('checked', true)
                    ->pluck('checked', 'consecutivo_recibo_id')
                    ->toArray();
            }
        }

        // Normalizar datos
        $recibosNormalizados = $procesosConCantidad->getCollection()->map(function ($proceso, int $index) use ($areasPorPrenda, $reciboIdPorKey, $checksActivos, $puedeGestionarCheckLogo) {
            $procesoArray = (array) $proceso;
            $numeroRecibo = (string) ($procesoArray['numero_recibo'] ?? '');
            $tipoRecibo = (string) ($procesoArray['tipo_recibo'] ?? '');
            $nombrePrenda = (string) ($procesoArray['nombre_prenda'] ?? '');
            $cliente = (string) ($procesoArray['cliente'] ?? '');
            $cantidad = (int) ($procesoArray['cantidad_total_prendas'] ?? 0);
            $fechaCreacion = $procesoArray['fecha_creacion'] ?? null;
            $prendaId = (int) ($procesoArray['prenda_id'] ?? 0);
            $pedidoParcialId = (int) ($procesoArray['pedido_parcial_id'] ?? 0);
            $esParcial = !empty($procesoArray['es_parcial']) || $pedidoParcialId > 0;
            $tipoReciboUpper = strtoupper(trim($tipoRecibo !== '' ? $tipoRecibo : 'BORDADO'));
            $numeroReciboInt = (int) $numeroRecibo;
            $reciboKey = $prendaId . '|' . $tipoReciboUpper . '|' . $numeroReciboInt;
            $consecutivoReciboId = (int) ($reciboIdPorKey[$reciboKey] ?? 0);
            $areaRaw = strtoupper((string) ($areasPorPrenda[$prendaId] ?? 'PENDIENTE'));
            $areaNormalizada = match ($areaRaw) {
                'ESTAMPANDO', 'ESTAMPADO' => 'Estampado',
                'BORDANDO', 'BORDADO' => 'Bordado',
                'CORTE_Y_APLIQUE' => 'Corte',
                'PENDIENTE_CONFIRMAR', 'PENDIENTE_DISENO', 'PENDIENTE' => 'Pendiente',
                default => ucfirst(strtolower(str_replace('_', ' ', $areaRaw))),
            };

            return [
                'id' => (int) ($procesoArray['id'] ?? ($index + 1)),
                'pedido_produccion_id' => (int) ($procesoArray['pedido_id'] ?? 0),
                'prenda_id' => $prendaId,
                'consecutivo_actual' => $numeroRecibo,
                'tipo_recibo' => $tipoRecibo !== '' ? $tipoRecibo : 'BORDADO',
                'es_parcial' => $esParcial,
                'pedido_parcial_id' => $pedidoParcialId > 0 ? $pedidoParcialId : null,
                'area' => $areaNormalizada,
                'cantidad_total' => $cantidad,
                'descripcion_detallada' => trim($nombrePrenda) !== '' ? ('PRENDA: ' . $nombrePrenda) : 'PRENDA: SIN DESCRIPCIÓN',
                'created_at' => $fechaCreacion,
                'consecutivo_recibo_id' => $consecutivoReciboId,
                'check_logo_recibo' => (bool) ($checksActivos[$consecutivoReciboId] ?? false),
                'puede_gestionar_check_logo' => $puedeGestionarCheckLogo,
                'pedido_info' => [
                    'cliente' => $cliente !== '' ? $cliente : 'N/A',
                    'fecha_creacion_orden' => $fechaCreacion,
                ],
            ];
        })->toArray();

        $currentPage = $procesosConCantidad->currentPage();
        $total = $procesosConCantidad->total();
        $from = $total > 0 ? (($currentPage - 1) * $perPage) + 1 : 0;
        $to = $from + count($recibosNormalizados) - 1;

        return response()->json([
            'success' => true,
            'recibos' => $recibosNormalizados,
            'total' => $total,
            'total_cantidad' => $total,
            'current_page' => $currentPage,
            'last_page' => $procesosConCantidad->lastPage(),
            'from' => $from,
            'to' => $to
        ]);
    }

    /**
     * Obtener datos de un recibo de reflectivo especifico como JSON
     */
    public function getReciboReflectivoJson($reciboId)
    {
        try {
            $data = $this->getReceiptJsonUseCase->execute((int) $reciboId, 'REFLECTIVO');

            if (!$data) {
                return response()->json(['success' => false, 'message' => 'Recibo no encontrado'], 404);
            }

            return response()->json(['success' => true, 'recibo' => $data]);
        } catch (\Exception $e) {
            \Log::error('Error en getReciboReflectivoJson: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }
    
    /**
     * Obtener datos de un recibo especifico como JSON (para tiempo real)
     */
    public function getReciboJson($reciboId)
    {
        try {
            $data = $this->getReceiptJsonUseCase->execute((int) $reciboId, 'COSTURA');

            if (!$data) {
                return response()->json(['success' => false, 'message' => 'Recibo no encontrado'], 404);
            }

            return response()->json(['success' => true, 'recibo' => $data]);
        } catch (\Exception $e) {
            \Log::error('Error en getReciboJson: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }

    /**
     * Obtener el area mas reciente de un pedido (API)
     */
    public function getAreaReciente($id)
    {
        try {
            \Log::info('[getAreaReciente] Obteniendo area mas reciente para pedido', ['pedido_id' => $id]);

            $data = $this->getAreaRecienteUseCase->execute((int) $id);

            if (!$data) {
                return response()->json(['success' => false, 'error' => 'Pedido no encontrado'], 404);
            }

            return response()->json(['success' => true] + $data);
        } catch (\Exception $e) {
            \Log::error('[getAreaReciente] Error: ' . $e->getMessage(), ['pedido_id' => $id]);
            return response()->json(['success' => false, 'error' => 'Error al obtener area reciente: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Contar recibos de COSTURA en Ejecucion (area Corte) para la campana
     * GET /api/recibos-costura/ejecutando-corte
     */
    public function contarRecibosEjecutandoCostura()
    {
        try {
            return response()->json($this->contarRecibosEjecutandoUseCase->execute((int) auth()->id()));
        } catch (\Exception $e) {
            \Log::error('Error en contarRecibosEjecutandoCostura: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al contar recibos de costura', 'total' => 0, 'recibos' => []], 500);
        }
    }

    /**
     * Marcar un recibo de COSTURA como visto por el usuario actual
     * POST /api/recibos-costura/{id}/marcar-visto-corte
     */
    public function marcarReciboVistoCostura($reciboId)
    {
        try {
            $result = $this->marcarReciboVistoUseCase->execute((int) $reciboId, (int) auth()->id(), 'COSTURA');

            if (!$result) {
                return response()->json(['success' => false, 'message' => 'Recibo no encontrado'], 404);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            \Log::error('Error al marcar recibo como visto: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al marcar el recibo como visto'], 500);
        }
    }

    /**
     * Guardar el check (chulo) de un recibo para el usuario actual.
     */
    public function toggleCheckReciboLogo(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->hasRole('visualizador_recibos_logo')) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado para gestionar el check de recibos logo.',
            ], 403);
        }

        $validated = $request->validate([
            'consecutivo_recibo_id' => 'required|integer|exists:consecutivos_recibos_pedidos,id',
            'checked' => 'required|boolean',
        ]);

        $userId = (int) $user->id;
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado.',
            ], 401);
        }

        \DB::table('recibos_logo_checks')->upsert(
            [[
                'consecutivo_recibo_id' => (int) $validated['consecutivo_recibo_id'],
                'user_id' => (int) $userId,
                'checked' => (bool) $validated['checked'],
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            ['consecutivo_recibo_id', 'user_id'],
            ['checked', 'updated_at']
        );

        return response()->json([
            'success' => true,
            'checked' => (bool) $validated['checked'],
        ]);
    }

    /**
     * Guardar día de entrega y calcular fecha estimada
     * POST /registros/{id}/dia-entrega
     * 
     * Parámetros esperados en Request:
     * - dia_de_entrega: int (entre 1 y 35)
     * - prenda_id: int (ID de la prenda cuyo recibo actualizar)
     */
    public function saveDiaEntrega(Request $request, $id)
    {
        try {
            $request->validate([
                'dia_de_entrega' => 'nullable|integer|min:1|max:35',
                'prenda_id' => 'nullable|integer'
            ]);

            $diaDeEntrega = $request->input('dia_de_entrega');
            $prendaId = $request->input('prenda_id');

            return response()->json(
                $this->saveDiaEntregaUseCase->execute($id, $diaDeEntrega, true, $prendaId)
            );
        } catch (\InvalidArgumentException $e) {
            \Log::error('Error validación en SaveDiaEntrega: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error en SaveDiaEntrega: ' . $e->getMessage(), [
                'id' => $id,
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar día de entrega: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener recibos de costura en formato JSON con filtros
     * GET /api/recibos-costura
     * 
     * Query params:
     * - estado: string|array (estado del recibo)
     * - area: string|array (área del proceso)
     * - numero_recibo: string|array (número del recibo)
     * - cliente: string|array (nombre del cliente)
     * - dia_entrega: string|array (día de entrega)
     * - fecha_creacion_desde: date (fecha inicial)
     * - fecha_creacion_hasta: date (fecha final)
     * - page: int (página, default: 1)
     * - per_page: int (items por página, default: 25)
     */
    public function getRecibosCosutraJSON(Request $request)
    {
        try {
            // Construir query base
            $query = $this->reciboCosturaQueryService->getBaseQuery();

            // Extraer y sanitizar filtros
            $filters = [
                'estado' => $request->input('estado'),
                'area' => $request->input('area'),
                'numero_recibo' => $request->input('numero_recibo'),
                'cliente' => $request->input('cliente'),
                'dia_entrega' => $request->input('dia_entrega'),
                'fecha_creacion_desde' => $request->input('fecha_creacion_desde'),
                'fecha_creacion_hasta' => $request->input('fecha_creacion_hasta'),
            ];

            // Remover filtros vacíos
            $filters = array_filter($filters, function ($value) {
                return !is_null($value) && $value !== '';
            });

            // Aplicar filtros
            $query = $this->reciboCosturaQueryService->applyFilters($query, $filters);

            // Paginar
            $perPage = min($request->input('per_page', 25), 100);
            $recibos = $this->reciboCosturaQueryService->getPaginatedRecibos($query, $perPage);

            return response()->json([
                'success' => true,
                'data' => $recibos->items(),
                'pagination' => [
                    'current_page' => $recibos->currentPage(),
                    'last_page' => $recibos->lastPage(),
                    'per_page' => $recibos->perPage(),
                    'total' => $recibos->total(),
                    'from' => $recibos->firstItem(),
                    'to' => $recibos->lastItem(),
                ],
                'filters_applied' => $filters,
                'filters_available' => $this->reciboCosturaQueryService->getFilterOptions(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en getRecibosCosutraJSON: ' . $e->getMessage(), [
                'filters' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener recibos de costura',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtener opciones disponibles para los filtros de recibos
     * GET /api/recibos-costura/filter-options
     * 
     * Retorna los valores válidos para cada filtro disponible
     */
    public function getRecibosCosutraFilterOptions(Request $request)
    {
        try {
            $filterOptions = $this->reciboCosturaQueryService->getFilterOptions();

            return response()->json([
                'success' => true,
                'filter_options' => $filterOptions
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en getRecibosCosutraFilterOptions: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener opciones de filtro',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtener distribucion de parciales de un recibo para la vista recibos-costura.
     * GET /api/recibos-costura/{idRecibo}/distribucion
     */
    public function obtenerDistribucionRecibo(Request $request, $idRecibo)
    {
        try {
            \Log::info('[RegistroOrdenController][DistribucionRecibo] Iniciando busqueda', [
                'recibo_id' => $idRecibo,
                'usuario_id' => auth()->id(),
            ]);

            $recibo = \App\Models\ConsecutivoReciboPedido::find($idRecibo);
            if (!$recibo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo no encontrado',
                ], 404);
            }

            $parciales = \App\Models\ReciboPorPartes::query()
                ->where('pedido_produccion_id', $recibo->pedido_produccion_id)
                ->where('prenda_pedido_id', $recibo->prenda_id)
                ->where('tipo_recibo', $recibo->tipo_recibo)
                ->where('consecutivo_original', $recibo->consecutivo_actual)
                ->with('tallas')
                ->get();

            $pedidoProduccion = \App\Models\PedidoProduccion::find($recibo->pedido_produccion_id);
            $numeroPedido = $pedidoProduccion ? $pedidoProduccion->numero_pedido : null;

            if ($parciales->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'recibo' => [
                        'id' => $recibo->id,
                        'consecutivo' => $recibo->consecutivo_actual,
                        'tipo_recibo' => $recibo->tipo_recibo,
                        'area_actual' => $recibo->area,
                        'numero_pedido' => $numeroPedido,
                    ],
                    'parciales' => [],
                    'mensaje' => 'No hay parciales creados para este recibo',
                    'total_parciales' => 0,
                ]);
            }

            $parcialesInfo = $parciales->map(function ($parcial) use ($numeroPedido) {
                $proceso = null;

                if ($numeroPedido) {
                    $proceso = \App\Models\ProcesoPrenda::query()
                        ->where('numero_pedido', $numeroPedido)
                        ->where('prenda_pedido_id', $parcial->prenda_pedido_id)
                        ->where('numero_recibo_parcial', $parcial->consecutivo_parcial)
                        ->latest('created_at')
                        ->first();
                }

                $estaCompletado = \DB::table('prenda_recibo_completado')
                    ->where('id_parcial', $parcial->id)
                    ->where('area', 'Costura')
                    ->exists();

                return [
                    'id' => $parcial->id,
                    'area' => $proceso->proceso ?? $parcial->area ?? 'SIN ASIGNAR',
                    'encargado' => $proceso->encargado ?? $parcial->encargado ?? 'SIN ASIGNAR',
                    'tipo_recibo' => $parcial->tipo_recibo,
                    'consecutivo_parcial' => (float) $parcial->consecutivo_parcial,
                    'consecutivo_original' => (float) $parcial->consecutivo_original,
                    'proceso_estado' => $estaCompletado
                        ? 'COMPLETADO'
                        : (($proceso->estado_proceso ?? 'En Progreso') ?: 'En Progreso'),
                    'fecha_asignacion' => $proceso->fecha_de_asignacion_encargado ?? null,
                    'observaciones' => $proceso->observaciones ?? '',
                    'pedido_produccion_id' => $parcial->pedido_produccion_id,
                    'prenda_pedido_id' => $parcial->prenda_pedido_id,
                    'numero_pedido' => $numeroPedido,
                    'tallas' => $parcial->tallas->map(function ($talla) {
                        return [
                            'id' => $talla->id,
                            'talla' => $talla->talla,
                            'cantidad' => $talla->cantidad,
                            'color_nombre' => $talla->color_nombre,
                        ];
                    })->toArray(),
                ];
            })->sortBy('area')->values();

            return response()->json([
                'success' => true,
                'recibo' => [
                    'id' => $recibo->id,
                    'consecutivo' => $recibo->consecutivo_actual,
                    'tipo_recibo' => $recibo->tipo_recibo,
                    'area_actual' => $recibo->area,
                    'numero_pedido' => $numeroPedido,
                ],
                'parciales' => $parcialesInfo,
                'total_parciales' => $parcialesInfo->count(),
            ]);
        } catch (\Exception $e) {
            \Log::error('[RegistroOrdenController][DistribucionRecibo] Error', [
                'recibo_id' => $idRecibo,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener distribucion: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener timeline de seguimiento de un parcial para el modal de distribucion.
     * GET /api/recibos-costura/parciales/{parcialId}/seguimiento
     */
    public function obtenerSeguimientoParcialRecibo(Request $request, $parcialId)
    {
        try {
            \Log::info('[RegistroOrdenController][SeguimientoParcial] Iniciando busqueda', [
                'parcial_id' => $parcialId,
                'usuario_id' => auth()->id(),
            ]);

            $parcial = \App\Models\ReciboPorPartes::query()
                ->with('tallas')
                ->find($parcialId);

            if (!$parcial) {
                // Fallback para nuevos parciales persistidos en pedidos_parciales/pedidos_parciales_tallas.
                $parcialNuevo = \DB::table('pedidos_parciales')
                    ->where('id', $parcialId)
                    ->whereNull('deleted_at')
                    ->first([
                        'id',
                        'pedido_produccion_id',
                        'prenda_pedido_id',
                        'tipo_recibo',
                        'consecutivo_actual',
                        'consecutivo_inicial',
                    ]);

                if (!$parcialNuevo) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Parcial no encontrado',
                    ], 404);
                }

                $pedidoProduccion = \App\Models\PedidoProduccion::find((int) $parcialNuevo->pedido_produccion_id);
                $numeroPedido = $pedidoProduccion ? $pedidoProduccion->numero_pedido : null;

                $tallasNuevo = \DB::table('pedidos_parciales_tallas')
                    ->where('pedido_parcial_id', (int) $parcialNuevo->id)
                    ->get(['id', 'talla', 'cantidad', 'color_nombre', 'genero']);

                return response()->json([
                    'success' => true,
                    'parcial' => [
                        'id' => (int) $parcialNuevo->id,
                        'consecutivo_parcial' => (float) ($parcialNuevo->consecutivo_actual ?? 0),
                        'consecutivo_original' => (float) ($parcialNuevo->consecutivo_inicial ?? 0),
                        'numero_pedido' => $numeroPedido,
                        'pedido_produccion_id' => (int) $parcialNuevo->pedido_produccion_id,
                        'prenda_pedido_id' => (int) $parcialNuevo->prenda_pedido_id,
                        'encargado_actual' => 'Sin asignar',
                        'area_actual' => 'Sin area',
                        'tallas' => $tallasNuevo->map(function ($talla) {
                            return [
                                'id' => (int) $talla->id,
                                'talla' => (string) $talla->talla,
                                'cantidad' => (int) ($talla->cantidad ?? 0),
                                'color_nombre' => $talla->color_nombre,
                                'genero' => strtoupper((string) ($talla->genero ?? 'CABALLERO')),
                            ];
                        })->toArray(),
                    ],
                    'timeline' => [],
                    'total_eventos' => 0,
                ]);
            }

            $pedidoProduccion = \App\Models\PedidoProduccion::find($parcial->pedido_produccion_id);
            $numeroPedido = $pedidoProduccion ? $pedidoProduccion->numero_pedido : null;

            $procesos = collect();

            if ($numeroPedido) {
                $procesos = \App\Models\ProcesoPrenda::query()
                    ->where('numero_pedido', $numeroPedido)
                    ->where('prenda_pedido_id', $parcial->prenda_pedido_id)
                    ->where('numero_recibo_parcial', $parcial->consecutivo_parcial)
                    ->orderByRaw('COALESCE(fecha_inicio, created_at) asc')
                    ->orderBy('id')
                    ->get();
            }

            $completadosRows = \DB::table('prenda_recibo_completado')
                ->where('id_parcial', $parcial->id)
                ->get(['area', 'nombre_operario', 'fecha_completado']);

            $completadosMap = [];
            foreach ($completadosRows as $row) {
                $key = $this->normalizarAreaKey($row->area);
                $completadosMap[$key] = $row;
            }

            // Precargar festivos solo para los años que realmente aparecen en el timeline (evita llamadas repetidas).
            $yearsSet = [];
            foreach ($procesos as $p) {
                foreach (['fecha_inicio', 'created_at', 'fecha_fin'] as $field) {
                    try {
                        if (!empty($p->{$field})) {
                            $yearsSet[Carbon::parse($p->{$field})->year] = true;
                        }
                    } catch (\Exception $e) {
                        // Ignorar fechas invalidas
                    }
                }
            }
            foreach ($completadosRows as $row) {
                try {
                    if (!empty($row->fecha_completado)) {
                        $yearsSet[Carbon::parse($row->fecha_completado)->year] = true;
                    }
                } catch (\Exception $e) {
                    // Ignorar fechas invalidas
                }
            }

            if (empty($yearsSet)) {
                $yearsSet[now()->year] = true;
                $yearsSet[now()->addYear()->year] = true;
            }

            $festivos = [];
            foreach (array_keys($yearsSet) as $year) {
                $festivos = array_merge($festivos, FestivosColombiaService::obtenerFestivos((int) $year));
            }

            $festivosSet = [];
            foreach ($festivos as $f) {
                try {
                    $festivosSet[Carbon::parse($f)->format('Y-m-d')] = true;
                } catch (\Exception $e) {
                    // Ignorar fechas invalidas
                }
            }

            $calcularDiasHabiles = function (?Carbon $inicio, ?Carbon $fin) use ($festivosSet): ?int {
                if (!$inicio || !$fin) {
                    return null;
                }

                if ($fin->lessThan($inicio)) {
                    return 0;
                }

                $current = $inicio->copy()->addDay(); // no cuenta el dia de inicio
                $totalDays = 0;
                $maxIterations = 3660; // 10 años de margen
                $iterations = 0;

                while ($current <= $fin && $iterations < $maxIterations) {
                    $dateString = $current->format('Y-m-d');
                    $isWeekend = $current->dayOfWeek === 0 || $current->dayOfWeek === 6;
                    $isFestivo = isset($festivosSet[$dateString]);

                    if (!$isWeekend && !$isFestivo) {
                        $totalDays++;
                    }

                    $current->addDay();
                    $iterations++;
                }

                return max(0, $totalDays);
            };

            $timeline = $procesos->map(function ($proceso, $index) {
                $fechaInicio = $proceso->fecha_inicio ? Carbon::parse($proceso->fecha_inicio) : null;
                $fechaFin = $proceso->fecha_fin ? Carbon::parse($proceso->fecha_fin) : null;

                return [
                    'id' => $proceso->id,
                    'orden' => $index + 1,
                    'area' => $proceso->proceso ?: 'Sin area',
                    'encargado' => $proceso->encargado ?: 'Sin asignar',
                    'estado' => $proceso->estado_proceso ?: 'En progreso',
                    'fecha_inicio' => $fechaInicio?->format('d/m/Y h:i A'),
                    'fecha_fin' => $fechaFin?->format('d/m/Y h:i A'),
                    'observaciones' => $proceso->observaciones ?: null,
                ];
            })->values();

            // Marcar completados por area (prenda_recibo_completado) y calcular duracion en dias habiles.
            $currentProcesoId = $procesos->last()?->id;

            $timeline = $timeline->map(function ($step) use ($procesos, $completadosMap, $calcularDiasHabiles, $currentProcesoId) {
                $areaKey = $this->normalizarAreaKey($step['area'] ?? '');
                $completionRow = $completadosMap[$areaKey] ?? null;

                if (!$completionRow) {
                    $step['completado'] = false;
                    $step['dias_habiles'] = null;

                    // Solo el area actual (ultimo proceso) se calcula hasta hoy; si el proceso ya tiene fecha_fin, se usa esa.
                    $procesoOriginal = $procesos->firstWhere('id', $step['id'] ?? null);
                    $inicio = null;
                    $fin = null;

                    try {
                        if (!empty($procesoOriginal?->fecha_inicio)) {
                            $inicio = Carbon::parse($procesoOriginal->fecha_inicio);
                        } elseif (!empty($procesoOriginal?->created_at)) {
                            $inicio = Carbon::parse($procesoOriginal->created_at);
                        }
                    } catch (\Exception $e) {
                        $inicio = null;
                    }

                    try {
                        if (!empty($procesoOriginal?->fecha_fin)) {
                            $fin = Carbon::parse($procesoOriginal->fecha_fin);
                        } elseif (!empty($currentProcesoId) && (int) ($step['id'] ?? 0) === (int) $currentProcesoId) {
                            $fin = Carbon::now();
                        }
                    } catch (\Exception $e) {
                        $fin = null;
                    }

                    if ($inicio && $fin) {
                        $step['dias_habiles'] = $calcularDiasHabiles($inicio, $fin);
                    }

                    return $step;
                }

                $fechaCompletado = null;
                try {
                    $fechaCompletado = Carbon::parse($completionRow->fecha_completado);
                } catch (\Exception $e) {
                    $fechaCompletado = null;
                }

                $step['completado'] = true;
                $step['estado'] = 'COMPLETADO';
                $step['fecha_fin'] = $fechaCompletado?->format('d/m/Y h:i A');
                $step['completado_por'] = $completionRow->nombre_operario ?? null;

                // Buscar el proceso original para obtener un inicio confiable.
                $inicio = null;
                $procesoOriginal = $procesos->firstWhere('id', $step['id'] ?? null);
                try {
                    if (!empty($procesoOriginal?->fecha_inicio)) {
                        $inicio = Carbon::parse($procesoOriginal->fecha_inicio);
                    } elseif (!empty($procesoOriginal?->created_at)) {
                        $inicio = Carbon::parse($procesoOriginal->created_at);
                    }
                } catch (\Exception $e) {
                    $inicio = null;
                }

                $step['dias_habiles'] = $calcularDiasHabiles($inicio, $fechaCompletado);
                return $step;
            })->values();

            return response()->json([
                'success' => true,
                'parcial' => [
                    'id' => $parcial->id,
                    'consecutivo_parcial' => (float) $parcial->consecutivo_parcial,
                    'consecutivo_original' => (float) $parcial->consecutivo_original,
                    'numero_pedido' => $numeroPedido,
                    'pedido_produccion_id' => $parcial->pedido_produccion_id,
                    'prenda_pedido_id' => $parcial->prenda_pedido_id,
                    'encargado_actual' => $procesos->last()?->encargado ?? $parcial->encargado ?? 'Sin asignar',
                    'area_actual' => $procesos->last()?->proceso ?? $parcial->area ?? 'Sin area',
                    'tallas' => $parcial->tallas->map(function ($talla) {
                        return [
                            'id' => $talla->id,
                            'talla' => $talla->talla,
                            'cantidad' => $talla->cantidad,
                            'color_nombre' => $talla->color_nombre,
                        ];
                    })->toArray(),
                ],
                'timeline' => $timeline,
                'total_eventos' => $timeline->count(),
            ]);
        } catch (\Exception $e) {
            \Log::error('[RegistroOrdenController][SeguimientoParcial] Error', [
                'parcial_id' => $parcialId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener seguimiento del parcial: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function reporteRecibosLogo(Request $request)
    {
        $diasAntiguedad = (int) $request->input('dias_antiguedad', 0);
        $areasLogo = ['BORDADO', 'BORDANDO', 'ESTAMPANDO', 'DISENO'];
        $tiposRecibo = ['BORDADO', 'ESTAMPADO', 'DTF', 'SUBLIMADO'];
        $desde = now()->subDays($diasAntiguedad);

        // Tomar solo el último estado de área por proceso (técnica) para evitar mezclar
        // BORDADO/ESTAMPADO/DTF/SUBLIMADO dentro de la misma prenda.
        $ultimasAreasLogo = DB::table('prenda_areas_logo_pedido as p1')
            ->select('p1.proceso_prenda_detalle_id', DB::raw('MAX(p1.updated_at) as max_updated_at'))
            ->groupBy('p1.proceso_prenda_detalle_id');

        $tipoReciboCase = "CASE ppd.tipo_proceso_id "
            . "WHEN 2 THEN 'BORDADO' "
            . "WHEN 3 THEN 'ESTAMPADO' "
            . "WHEN 4 THEN 'DTF' "
            . "WHEN 5 THEN 'SUBLIMADO' "
            . "ELSE NULL END";

        $recibos = DB::table('prendas_pedido as pp')
            ->join('pedidos_procesos_prenda_detalles as ppd', 'ppd.prenda_pedido_id', '=', 'pp.id')
            ->joinSub($ultimasAreasLogo, 'ultima_area', function ($join) {
                $join->on('ultima_area.proceso_prenda_detalle_id', '=', 'ppd.id');
            })
            ->join('prenda_areas_logo_pedido as palo', function ($join) {
                $join->on('palo.proceso_prenda_detalle_id', '=', 'ppd.id')
                    ->on('palo.updated_at', '=', 'ultima_area.max_updated_at');
            })
            ->join('pedidos_produccion as pedprod', 'pp.pedido_produccion_id', '=', 'pedprod.id')
            ->leftJoin('users as asesor_user', 'pedprod.asesor_id', '=', 'asesor_user.id')
            ->join('consecutivos_recibos_pedidos as crp', function ($join) use ($tipoReciboCase) {
                $join->on('pp.id', '=', 'crp.prenda_id')
                    ->on('pp.pedido_produccion_id', '=', 'crp.pedido_produccion_id')
                    ->where(function ($q) {
                        $q->whereColumn('palo.consecutivo_recibo_id', 'crp.id')
                            ->orWhereNull('palo.consecutivo_recibo_id');
                    })
                    ->whereRaw("crp.tipo_recibo = ({$tipoReciboCase})");
            })
            ->select([
                'crp.consecutivo_actual as numero_recibo',
                'palo.area',
                'crp.created_at as fecha_creacion',
                'pedprod.cliente',
                'pp.id as prenda_id',
                'pp.nombre_prenda',
                'asesor_user.name as asesor',
                'crp.tipo_recibo',
            ])
            ->where('palo.area', '<>', 'ANULADO')
            ->where('palo.area', '<>', 'ENTREGADO')
            ->whereIn('palo.area', $areasLogo)
            ->whereIn('crp.tipo_recibo', $tiposRecibo)
            ->whereRaw("UPPER(COALESCE(crp.estado, '')) NOT LIKE '%ANULAD%'")
            ->whereRaw("UPPER(COALESCE(crp.area, '')) NOT LIKE '%ANULAD%'")
            ->when($diasAntiguedad > 0, function ($q) use ($desde) {
                return $q->where('crp.created_at', '>=', $desde);
            })
            ->orderBy('crp.created_at', 'asc')
            ->get();

        $receipts = collect($recibos)
            ->map(function ($item) {
                $fechaCreacion = Carbon::parse($item->fecha_creacion);
                $diasHabiles = $this->diasHabilesService->calcularDiasHabiles($fechaCreacion, now());
                $item->dias_transcurridos = $diasHabiles;

                $prendaId = (int) $item->prenda_id;
                $numeroRecibo = (int) $item->numero_recibo;

                // Intentar obtener prendas de pedidos parciales (que coincidan con el número de recibo)
                $prendasPartiales = DB::table('pedidos_parciales as pp')
                    ->join('pedidos_parciales_tallas as ppt', 'pp.id', '=', 'ppt.pedido_parcial_id')
                    ->join('prendas_pedido as prenda', 'pp.prenda_pedido_id', '=', 'prenda.id')
                    ->select([
                        'prenda.nombre_prenda',
                        'ppt.color_nombre',
                        DB::raw('SUM(ppt.cantidad) as cantidad'),
                        'ppt.talla',
                        'ppt.genero',
                    ])
                    ->where('pp.prenda_pedido_id', $prendaId)
                    ->where('pp.consecutivo_actual', $numeroRecibo)
                    ->where('pp.activo', true)
                    ->whereNull('pp.deleted_at')
                    ->groupBy('prenda.nombre_prenda', 'ppt.color_nombre', 'ppt.talla', 'ppt.genero')
                    ->get();

                if ($prendasPartiales->count() > 0) {
                    // Usar cantidades de pedidos_parciales_tallas
                    $item->prendas = collect($prendasPartiales)->map(function ($p) {
                        return (object)[
                            'nombre_prenda' => $p->nombre_prenda,
                            'color_nombre' => !empty($p->color_nombre) ? $p->color_nombre : null,
                            'cantidad_color' => !empty($p->color_nombre) ? $p->cantidad : null,
                            'cantidad_talla' => empty($p->color_nombre) ? $p->cantidad : null,
                            'tela' => !empty($p->talla) ? $p->talla : null,
                        ];
                    });
                } else {
                    // Usar cantidades de prenda_pedido_tallas (si no hay parcial)
                    $prendasConColores = collect(
                        DB::table('prendas_pedido as pp')
                            ->join('prenda_pedido_tallas as ppt', 'pp.id', '=', 'ppt.prenda_pedido_id')
                            ->join('prenda_pedido_talla_colores as pptc', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
                            ->select([
                                'pp.nombre_prenda',
                                'pptc.color_nombre',
                                'pptc.cantidad as cantidad_color',
                                DB::raw('null as cantidad_talla'),
                                DB::raw('null as tela'),
                            ])
                            ->where('pp.id', $prendaId)
                            ->get()
                    );

                    $prendasSinColores = collect(
                        DB::table('prendas_pedido as pp')
                            ->join('prenda_pedido_tallas as ppt', 'pp.id', '=', 'ppt.prenda_pedido_id')
                            ->leftJoin('prenda_pedido_talla_colores as pptc', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
                            ->select([
                                'pp.nombre_prenda',
                                'ppt.tela',
                                'ppt.cantidad as cantidad_talla',
                                DB::raw('null as color_nombre'),
                                DB::raw('null as cantidad_color'),
                            ])
                            ->where('pp.id', $prendaId)
                            ->whereNull('pptc.id')
                            ->get()
                    );

                    $item->prendas = $prendasConColores->merge($prendasSinColores);
                }

                return $item;
            })
            ->groupBy('dias_transcurridos')
            ->sortByDesc(function ($group, $dias) {
                return (int) $dias;
            });

        $totalRecibos = $recibos->count();
        $filtros = $request->only(['dias_antiguedad']);

        $pdf = Pdf::loadView('registros.reporte-recibos-logo-pdf', [
            'grouped' => $receipts,
            'totalRecibos' => $totalRecibos,
            'filtros' => $filtros,
            'fechaGeneracion' => now(),
            'diasAntiguedad' => $diasAntiguedad,
        ])->setPaper('a4', 'landscape');

        $filename = "reporte_recibos_logo_" . now()->format('Ymd_His') . ".pdf";

        return $pdf->download($filename);
    }

    public function reporteRecibosReflectivo(Request $request)
    {
        $diasAntiguedad = (int) $request->input('dias_antiguedad', 0);
        $desde = now()->subDays($diasAntiguedad);
        $areasReflectivo = ['INSUMOS', 'COSTURA'];

        $recibos = DB::table('consecutivos_recibos_pedidos as crp')
            ->join('prendas_pedido as pp', 'crp.prenda_id', '=', 'pp.id')
            ->join('pedidos_produccion as pedprod', 'pp.pedido_produccion_id', '=', 'pedprod.id')
            ->leftJoin('users as asesor_user', 'pedprod.asesor_id', '=', 'asesor_user.id')
            ->select([
                'crp.consecutivo_actual as numero_recibo',
                'crp.area',
                'crp.created_at as fecha_creacion',
                'pedprod.cliente',
                'pp.id as prenda_id',
                'pp.nombre_prenda',
                'asesor_user.name as asesor',
                'crp.tipo_recibo',
            ])
            ->where('crp.tipo_recibo', 'REFLECTIVO')
            ->where('crp.activo', true)
            ->whereIn('crp.area', $areasReflectivo)
            ->whereRaw("UPPER(COALESCE(crp.estado, '')) NOT LIKE '%ANULAD%'")
            ->whereRaw("UPPER(COALESCE(crp.area, '')) NOT LIKE '%ANULAD%'")
            ->when($diasAntiguedad > 0, function ($q) use ($desde) {
                return $q->where('crp.created_at', '>=', $desde);
            })
            ->orderBy('crp.created_at', 'asc')
            ->get();

        $receipts = collect($recibos)
            ->map(function ($item) {
                $fechaCreacion = Carbon::parse($item->fecha_creacion);
                $diasHabiles = $this->diasHabilesService->calcularDiasHabiles($fechaCreacion, now());
                $item->dias_transcurridos = $diasHabiles;

                $prendaId = (int) $item->prenda_id;
                $numeroRecibo = (int) $item->numero_recibo;

                // Intentar obtener prendas de pedidos parciales (que coincidan con el número de recibo)
                $prendasPartiales = DB::table('pedidos_parciales as pp')
                    ->join('pedidos_parciales_tallas as ppt', 'pp.id', '=', 'ppt.pedido_parcial_id')
                    ->join('prendas_pedido as prenda', 'pp.prenda_pedido_id', '=', 'prenda.id')
                    ->select([
                        'prenda.nombre_prenda',
                        'ppt.color_nombre',
                        DB::raw('SUM(ppt.cantidad) as cantidad'),
                        'ppt.talla',
                        'ppt.genero',
                    ])
                    ->where('pp.prenda_pedido_id', $prendaId)
                    ->where('pp.consecutivo_actual', $numeroRecibo)
                    ->where('pp.activo', true)
                    ->whereNull('pp.deleted_at')
                    ->groupBy('prenda.nombre_prenda', 'ppt.color_nombre', 'ppt.talla', 'ppt.genero')
                    ->get();

                if ($prendasPartiales->count() > 0) {
                    // Usar cantidades de pedidos_parciales_tallas
                    $item->prendas = collect($prendasPartiales)->map(function ($p) {
                        return (object)[
                            'nombre_prenda' => $p->nombre_prenda,
                            'color_nombre' => !empty($p->color_nombre) ? $p->color_nombre : null,
                            'cantidad_color' => !empty($p->color_nombre) ? $p->cantidad : null,
                            'cantidad_talla' => empty($p->color_nombre) ? $p->cantidad : null,
                            'tela' => !empty($p->talla) ? $p->talla : null,
                        ];
                    });
                } else {
                    // Usar cantidades de prenda_pedido_tallas (si no hay parcial)
                    $prendasConColores = collect(
                        DB::table('prendas_pedido as pp')
                            ->join('prenda_pedido_tallas as ppt', 'pp.id', '=', 'ppt.prenda_pedido_id')
                            ->join('prenda_pedido_talla_colores as pptc', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
                            ->select([
                                'pp.nombre_prenda',
                                'pptc.color_nombre',
                                'pptc.cantidad as cantidad_color',
                                DB::raw('null as cantidad_talla'),
                                DB::raw('null as tela'),
                            ])
                            ->where('pp.id', $prendaId)
                            ->get()
                    );

                    $prendasSinColores = collect(
                        DB::table('prendas_pedido as pp')
                            ->join('prenda_pedido_tallas as ppt', 'pp.id', '=', 'ppt.prenda_pedido_id')
                            ->leftJoin('prenda_pedido_talla_colores as pptc', 'ppt.id', '=', 'pptc.prenda_pedido_talla_id')
                            ->select([
                                'pp.nombre_prenda',
                                'ppt.tela',
                                'ppt.cantidad as cantidad_talla',
                                DB::raw('null as color_nombre'),
                                DB::raw('null as cantidad_color'),
                            ])
                            ->where('pp.id', $prendaId)
                            ->whereNull('pptc.id')
                            ->get()
                    );

                    $item->prendas = $prendasConColores->merge($prendasSinColores);
                }

                return $item;
            })
            ->groupBy('dias_transcurridos')
            ->sortByDesc(function ($group, $dias) {
                return (int) $dias;
            });

        $totalRecibos = $recibos->count();
        $filtros = $request->only(['dias_antiguedad']);

        $pdf = Pdf::loadView('registros.reporte-recibos-reflectivo-pdf', [
            'grouped' => $receipts,
            'totalRecibos' => $totalRecibos,
            'filtros' => $filtros,
            'fechaGeneracion' => now(),
            'diasAntiguedad' => $diasAntiguedad,
        ])->setPaper('a4', 'landscape');

        $filename = "reporte_recibos_reflectivo_" . now()->format('Ymd_His') . ".pdf";

        return $pdf->download($filename);
    }

    private function normalizarAreaKey(?string $area): string
    {
        $area = trim((string) $area);
        if ($area === '') {
            return '';
        }

        // Normaliza tildes y espacios para poder comparar "Control de Calidad" vs "Control Calidad", etc.
        $ascii = Str::ascii($area);
        $ascii = mb_strtolower($ascii);
        $ascii = preg_replace('/[^a-z0-9]+/i', '', $ascii) ?? $ascii;

        return $ascii;
    }

}
