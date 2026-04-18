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
use App\Services\FestivosColombiaService;
use Carbon\Carbon;
use Illuminate\Support\Str;

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
        MarcarReciboVistoUseCase $marcarReciboVistoUseCase
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
