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
use Carbon\Carbon;

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
        $datos['recibos'] = collect($datos['recibos']);
        return view('registros.recibos-costura', $datos);
    }

    /**
     * Mostrar recibos de reflectivo aprobados
     */
    public function recibosReflectivo(Request $request)
    {
        $datos = $this->getReflectiveReceiptsUseCase->execute($request);
        $datos['recibos'] = collect($datos['recibos']);
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
     */
    public function saveDiaEntrega(Request $request, $id)
    {
        try {
            $request->validate([
                'dia_de_entrega' => 'nullable|integer|min:1|max:35'
            ]);

            $diaDeEntrega = $request->input('dia_de_entrega');

            return response()->json(
                $this->saveDiaEntregaUseCase->execute($id, $diaDeEntrega)
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

}

