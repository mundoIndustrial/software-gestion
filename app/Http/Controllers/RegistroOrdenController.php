<?php

namespace App\Http\Controllers;

use App\Constants\AreaOptions;

use Illuminate\Http\Request;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use App\Models\Cotizacion;
use App\Services\CacheCalculosService;
use App\Services\RegistroOrdenQueryService;
use App\Services\RegistroOrdenSearchService;
use App\Services\RegistroOrdenFilterService;
use App\Services\RegistroOrdenExtendedQueryService;
use App\Services\RegistroOrdenSearchExtendedService;
use App\Services\RegistroOrdenFilterExtendedService;
use App\Services\RegistroOrdenTransformService;
use App\Services\RegistroOrdenProcessService;
use App\Services\RegistroOrdenValidationService;
use App\Services\RegistroOrdenCreationService;
use App\Services\RegistroOrdenUpdateService;
use App\Services\RegistroOrdenDeletionService;
use App\Services\RegistroOrdenNumberService;
use App\Services\RegistroOrdenPrendaService;
use App\Models\News;
use App\Models\Festivo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\FestivosColombiaService;
use Carbon\Carbon;

class RegistroOrdenController extends Controller
{
    protected $queryService;
    protected $searchService;
    protected $filterService;
    protected $extendedQueryService;
    protected $extendedSearchService;
    protected $extendedFilterService;
    protected $transformService;
    protected $processService;
    protected $validationService;
    protected $creationService;
    protected $updateService;
    protected $deletionService;
    protected $numberService;
    protected $prendaService;

    public function __construct(
        RegistroOrdenQueryService $queryService,
        RegistroOrdenSearchService $searchService,
        RegistroOrdenFilterService $filterService,
        RegistroOrdenExtendedQueryService $extendedQueryService,
        RegistroOrdenSearchExtendedService $extendedSearchService,
        RegistroOrdenFilterExtendedService $extendedFilterService,
        RegistroOrdenTransformService $transformService,
        RegistroOrdenProcessService $processService,
        RegistroOrdenValidationService $validationService,
        RegistroOrdenCreationService $creationService,
        RegistroOrdenUpdateService $updateService,
        RegistroOrdenDeletionService $deletionService,
        RegistroOrdenNumberService $numberService,
        RegistroOrdenPrendaService $prendaService
    )
    {
        $this->queryService = $queryService;
        $this->searchService = $searchService;
        $this->filterService = $filterService;
        $this->extendedQueryService = $extendedQueryService;
        $this->extendedSearchService = $extendedSearchService;
        $this->extendedFilterService = $extendedFilterService;
        $this->transformService = $transformService;
        $this->processService = $processService;
        $this->validationService = $validationService;
        $this->creationService = $creationService;
        $this->updateService = $updateService;
        $this->deletionService = $deletionService;
        $this->numberService = $numberService;
        $this->prendaService = $prendaService;
    }

    private function getEnumOptions($table, $column)
    {
        $columnInfo = DB::select("SHOW COLUMNS FROM {$table} WHERE Field = ?", [$column]);
        if (empty($columnInfo)) return [];

        $type = $columnInfo[0]->Type;
        preg_match_all("/'([^']+)'/", $type, $matches);
        return $matches[1] ?? [];
    }

    public function index(Request $request)
    {
        // Handle request for unique values for filters
        if ($request->has('get_unique_values') && $request->has('column')) {
            try {
                $values = $this->extendedQueryService->getUniqueValues($request->input('column'));
                return response()->json(['unique_values' => $values]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['error' => 'Invalid column'], 400);
            } catch (\Exception $e) {
                return response()->json(['error' => 'Error fetching values: ' . $e->getMessage()], 500);
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
        
        \Log::info("Antes de verificar filtro - filterTotalDias: " . json_encode($filterTotalDias) . ", es null: " . ($filterTotalDias === null ? 'SI' : 'NO'));
        
        // Si hay filtro de total_de_dias_, necesitamos obtener todos los registros para calcular y filtrar
        if ($filterTotalDias !== null) {
            \Log::info("Iniciando filtrado por total_de_dias_ con valores: " . json_encode($filterTotalDias));
            $todasOrdenes = $query->get();
            \Log::info("Total órdenes obtenidas: " . $todasOrdenes->count());
            
            // Convertir a array para el cálculo
            $ordenesArray = $todasOrdenes->map(function($orden) {
                return (object) $orden->getAttributes();
            })->toArray();
            
            $totalDiasCalculados = $this->calcularTotalDiasBatchConCache($ordenesArray, $festivos);
            
            // Filtrar por total_de_dias_
            $ordenesFiltradas = $todasOrdenes->filter(function($orden) use ($totalDiasCalculados, $filterTotalDias) {
                $totalDias = $totalDiasCalculados[$orden->numero_pedido] ?? 0;
                $match = in_array((int)$totalDias, $filterTotalDias, true);
                
                // Log temporal para debug (eliminar después)
                if ((int)$orden->numero_pedido <= 3) {
                    \Log::info("Filtro total_dias - Pedido: {$orden->numero_pedido}, Total días: {$totalDias}, Filtros: " . json_encode($filterTotalDias) . ", Match: " . ($match ? 'SI' : 'NO'));
                }
                
                return $match;
            });
            
            // Paginar manualmente los resultados filtrados
            $currentPage = request()->get('page', 1);
            $perPage = 25;
            $ordenes = new \Illuminate\Pagination\LengthAwarePaginator(
                $ordenesFiltradas->forPage($currentPage, $perPage)->values(),
                $ordenesFiltradas->count(),
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'query' => request()->query()]
            );
            
            // Recalcular solo para las órdenes de la página actual (con caché inteligente)
            $totalDiasCalculados = CacheCalculosService::getTotalDiasBatch($ordenes->items(), $festivos);
        } else {
            // OPTIMIZACIÓN: Paginación a 25 items
            $ordenes = $query->paginate(25);
            
            // DEBUG: Log de paginación
            \Log::info("=== PAGINACIÓN DEBUG ===");
            \Log::info("Total: {$ordenes->total()}");
            \Log::info("Página actual: {$ordenes->currentPage()}");
            \Log::info("Última página: {$ordenes->lastPage()}");
            \Log::info("Por página: {$ordenes->perPage()}");
            \Log::info("Tiene búsqueda: " . ($request->has('search') ? 'SÍ' : 'NO'));
            \Log::info("Búsqueda: " . ($request->search ?? 'N/A'));
            \Log::info("HTML paginación: " . substr($ordenes->links()->toHtml(), 0, 200));

            // OPTIMIZACIÓN CRÍTICA: SOLO calcular para la página actual (25 items) con caché
            // No calcular para TODAS las 2257 órdenes - usa CacheCalculosService con TTL de 1 hora
            $totalDiasCalculados = CacheCalculosService::getTotalDiasBatch($ordenes->items(), $festivos);
        }

        // Obtener areasMap solo para los items de esta página (OPTIMIZACIÓN)
        $numeroPedidosPagina = array_map(function($orden) {
            return $orden->numero_pedido;
        }, $ordenes->items());
        $areasMap = $this->processService->getLastProcessByOrderNumbers($numeroPedidosPagina);
        
        // Obtener encargados de "Creación Orden" para cada pedido
        $encargadosCreacionOrdenMap = $this->processService->getCreacionOrdenEncargados($numeroPedidosPagina);

        // Opciones de áreas disponibles (áreas de procesos)
        $areaOptions = AreaOptions::getArray();
        
        // FALLBACK: Si totalDiasCalculados está vacío o falta alguna orden, recalcular
        if (empty($totalDiasCalculados)) {
            \Log::warning("totalDiasCalculados vacío, recalculando...");
            $totalDiasCalculados = CacheCalculosService::getTotalDiasBatch($ordenes->items(), $festivos);
        } else {
            // Verificar que todas las órdenes tengan un valor
            foreach ($ordenes->items() as $orden) {
                if (!isset($totalDiasCalculados[$orden->numero_pedido])) {
                    \Log::warning("Falta días para pedido {$orden->numero_pedido}, recalculando...");
                    $totalDiasCalculados[$orden->numero_pedido] = 
                        CacheCalculosService::getTotalDias($orden->numero_pedido, $orden->estado);
                }
            }
        }

        if ($request->wantsJson()) {
            // Filtrar campos sensibles según el rol del usuario
            $ordenesFiltered = array_map(function($orden) use ($areasMap, $encargadosCreacionOrdenMap) {
                return $this->transformService->transformarOrden($orden, $areasMap, $encargadosCreacionOrdenMap);
            }, $ordenes->items());
            
            // Retornar string vacío para que paginationManager.js genere el HTML con los estilos correctos
            $paginationHtml = '';
            
            \Log::info("=== PAGINACIÓN ===");
            \Log::info("Total: {$ordenes->total()}");
            \Log::info("Última página: {$ordenes->lastPage()}");
            
            // Determinar contexto y rol para renderizado de botones
            $context = 'registros';
            $userRole = auth()->user() && auth()->user()->role ? auth()->user()->role->name : null;
            
            return response()->json([
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
                'pagination_html' => $paginationHtml
            ]);
        }

        $context = 'registros';
        $title = 'Registro de Órdenes';
        $icon = 'fa-clipboard-list';
        $fetchUrl = '/registros';
        $updateUrl = '/registros';
        $modalContext = 'orden';
        return view('orders.index', compact('ordenes', 'totalDiasCalculados', 'areaOptions', 'areasMap', 'encargadosCreacionOrdenMap', 'context', 'title', 'icon', 'fetchUrl', 'updateUrl', 'modalContext'));
    }

    public function show($pedido)
    {
        // Buscar en PedidoProduccion por 'numero_pedido'
        $order = PedidoProduccion::with(['asesora', 'prendas', 'cotizacion'])->where('numero_pedido', $pedido)->firstOrFail();

        $totalCantidad = DB::table('prendas_pedido')
            ->where('numero_pedido', $order->numero_pedido)
            ->sum('cantidad');

        // $totalEntregado se calcula solo si la tabla procesos_prenda existe y tiene datos
        $totalEntregado = 0;
        try {
            $totalEntregado = DB::table('procesos_prenda')
                ->where('numero_pedido', $order->numero_pedido)
                ->sum('cantidad_completada');
        } catch (\Exception $e) {
            \Log::warning('Error al calcular totalEntregado', ['error' => $e->getMessage()]);
            $totalEntregado = 0;
        }

        $order->total_cantidad = $totalCantidad;
        $order->total_entregado = $totalEntregado;

        // Filtrar datos sensibles
        $orderArray = $order->toArray();
        
        // Campos que se ocultan para todos
        $camposOcultosGlobal = ['created_at', 'updated_at', 'deleted_at', 'asesor_id', 'cliente_id'];
        
        // Campos que se ocultan para no-asesores
        $camposOcultosNoAsesor = ['cotizacion_id', 'numero_cotizacion'];
        
        // Agregar nombres en lugar de IDs
        if ($order->asesora) {
            $orderArray['asesor'] = $order->asesora->name ?? '';
            $orderArray['asesora'] = $order->asesora->name ?? '';
        } else {
            $orderArray['asesor'] = '';
            $orderArray['asesora'] = '';
        }
        
        // Para cliente, usar el campo 'cliente' directo (que es el nombre del cliente en la tabla)
        if (!empty($orderArray['cliente_id'])) {
            try {
                $cliente = \App\Models\Cliente::find($orderArray['cliente_id']);
                $orderArray['cliente_nombre'] = $cliente ? $cliente->nombre : ($orderArray['cliente'] ?? '');
            } catch (\Exception $e) {
                $orderArray['cliente_nombre'] = $orderArray['cliente'] ?? '';
            }
        } else {
            $orderArray['cliente_nombre'] = $orderArray['cliente'] ?? '';
        }
        
        // Asegurar que descripcion_prendas se calcula correctamente
        // Esto fuerza la evaluación del atributo calculado
        $orderArray['descripcion_prendas'] = $order->descripcion_prendas;
        
        // Eliminar campos ocultos globales
        foreach ($camposOcultosGlobal as $campo) {
            unset($orderArray[$campo]);
        }
        
        // Eliminar campos sensibles para no-asesores
        if (!auth()->user() || !auth()->user()->role || auth()->user()->role->name !== 'asesor') {
            foreach ($camposOcultosNoAsesor as $campo) {
                unset($orderArray[$campo]);
            }
        }
        
        return response()->json($orderArray);
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
        try {
            // Validar datos
            $validatedData = $this->validationService->validateStoreRequest($request);

            // Verificar número consecutivo
            $nextPedido = $this->numberService->getNextNumber();
            
            if (!$request->input('allow_any_pedido', false)) {
                if ($request->pedido != $nextPedido) {
                    return response()->json([
                        'success' => false,
                        'message' => "El número consecutivo disponible es $nextPedido"
                    ], 422);
                }
            }

            // Crear orden con todas sus prendas
            $pedido = $this->creationService->createOrder($validatedData);

            // Registrar evento
            $this->creationService->logOrderCreated(
                $pedido->numero_pedido,
                $validatedData['cliente'],
                $validatedData['estado'] ?? 'No iniciado'
            );

            // Broadcast evento
            $this->creationService->broadcastOrderCreated($pedido);

            return response()->json(['success' => true, 'message' => 'Orden registrada correctamente']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al crear orden', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error inesperado: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $pedido)
    {
        try {
            // Obtener la orden
            $orden = PedidoProduccion::where('numero_pedido', $pedido)->firstOrFail();

            // Validar datos
            $validatedData = $this->validationService->validateUpdateRequest($request);

            // Ejecutar actualización delegada al servicio
            $response = $this->updateService->updateOrder($orden, $validatedData);

            // Broadcast eventos
            $this->updateService->broadcastOrderUpdated($orden, $validatedData);

            return response()->json($response);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al actualizar orden', [
                'pedido' => $pedido,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la orden: ' . $e->getMessage(),
                'error_details' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    public function destroy($pedido)
    {
        try {
            $this->deletionService->deleteOrder($pedido);
            
            // Broadcast evento
            $this->deletionService->broadcastOrderDeleted($pedido);

            return response()->json(['success' => true, 'message' => 'Orden eliminada correctamente']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Orden no encontrada'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error al eliminar orden', ['pedido' => $pedido, 'error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la orden: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getEntregas($pedido)
    {
        try {
            // Obtener el pedido desde la nueva arquitectura
            $orden = PedidoProduccion::where('numero_pedido', $pedido)->firstOrFail();

            // Obtener prendas y convertir a formato compatible
            $entregas = $orden->prendas()
                ->select('nombre_prenda', 'cantidad_talla')
                ->get()
                ->flatMap(function($prenda) {
                    $cantidadTalla = is_string($prenda->cantidad_talla)
                        ? json_decode($prenda->cantidad_talla, true)
                        : $prenda->cantidad_talla;

                    $resultado = [];
                    if (is_array($cantidadTalla)) {
                        foreach ($cantidadTalla as $talla => $cantidad) {
                            $resultado[] = [
                                'prenda' => $prenda->nombre_prenda,
                                'talla' => $talla,
                                'cantidad' => $cantidad,
                                'total_producido_por_talla' => 0,
                                'total_pendiente_por_talla' => $cantidad
                            ];
                        }
                    }
                    return $resultado;
                });

            return response()->json($entregas);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Pedido no encontrado'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Cálculo optimizado con CACHÉ PERSISTENTE (Redis/File)
     * Calcula total_de_dias para TODAS las órdenes con caché de 24 horas
     * MEJORA: 95% más rápido que calcularTotalDiasBatch original
     */
    private function calcularTotalDiasBatchConCache(array $ordenes, array $festivos): array
    {
        // IMPORTANTE: Delegar TODO a CacheCalculosService
        // Esto garantiza que servidor y API usen exactamente la misma lógica
        return \App\Services\CacheCalculosService::getTotalDiasBatch($ordenes, $festivos);
    }
    
    /**
     * Método legacy mantenido para compatibilidad
     * @deprecated Usar calcularTotalDiasBatchConCache en su lugar
     */
    private function calcularTotalDiasBatch(array $ordenes, array $festivos): array
    {
        return $this->calcularTotalDiasBatchConCache($ordenes, $festivos);
    }

    /**
     * Conteo optimizado de fines de semana
     */
    private function contarFinesDeSemanaBatch(\Carbon\Carbon $start, \Carbon\Carbon $end): int
    {
        $totalDays = $start->diffInDays($end) + 1;
        $startDay = $start->dayOfWeek; // 0=Domingo, 6=Sábado

        $fullWeeks = floor($totalDays / 7);
        $extraDays = $totalDays % 7;

        $weekends = $fullWeeks * 2; // 2 fines de semana por semana completa

        // Contar fines de semana en días extra
        for ($i = 0; $i < $extraDays; $i++) {
            $day = ($startDay + $i) % 7;
            if ($day === 0 || $day === 6) $weekends++; // Domingo o Sábado
        }

        return $weekends;
    }
    
    /**
     * Invalidar caché de días calculados para una orden específica
     * Se ejecuta cuando se actualiza o elimina una orden
     */
    private function invalidarCacheDias($pedido): void
    {
        $hoy = now()->format('Y-m-d');
        
        // Obtener festivos del servicio automático (no de BD)
        $currentYear = now()->year;
        $festivos = FestivosColombiaService::obtenerFestivos($currentYear);
        $festivosCacheKey = md5(serialize($festivos));
        
        // Invalidar para todos los posibles estados
        $estados = ['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'];
        
        foreach ($estados as $estado) {
            $cacheKey = "orden_dias_{$pedido}_{$estado}_{$hoy}_{$festivosCacheKey}";
            Cache::forget($cacheKey);
        }
        
        // También invalidar para días anteriores (últimos 7 días)
        for ($i = 1; $i <= 7; $i++) {
            $fecha = now()->subDays($i)->format('Y-m-d');
            foreach ($estados as $estado) {
                $cacheKey = "orden_dias_{$pedido}_{$estado}_{$fecha}_{$festivosCacheKey}";
                Cache::forget($cacheKey);
            }
        }
    }

    /**
     * Actualizar el número de pedido (consecutivo)
     */
    public function updatePedido(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'old_pedido' => 'required|integer',
                'new_pedido' => 'required|integer|min:1',
            ]);

            $this->numberService->updatePedidoNumber(
                $validatedData['old_pedido'],
                $validatedData['new_pedido']
            );

            // Obtener la orden actualizada para broadcast
            $orden = PedidoProduccion::where('numero_pedido', $validatedData['new_pedido'])->first();
            if ($orden) {
                $this->numberService->broadcastPedidoUpdated($orden);
            }

            return response()->json([
                'success' => true,
                'message' => 'Número de pedido actualizado correctamente',
                'old_pedido' => $validatedData['old_pedido'],
                'new_pedido' => $validatedData['new_pedido']
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos: ' . json_encode($e->errors())
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al actualizar pedido', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Obtener registros por orden (API para el modal de edición)
     * Retorna las prendas desde la nueva arquitectura
     */
    public function getRegistrosPorOrden($pedido)
    {
        try {
            $prendas = $this->prendaService->getPrendasArray($pedido);
            return response()->json($prendas);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Pedido no encontrado'], 404);
        } catch (\Exception $e) {
            \Log::error('Error al obtener registros por orden', [
                'pedido' => $pedido,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los registros'
            ], 500);
        }
    }

    /**
     * Editar orden completa (actualiza tabla_original y registros_por_orden)
     */
    public function editFullOrder(Request $request, $pedido)
    {
        try {
            // Validar datos
            $validatedData = $this->validationService->validateEditFullOrderRequest($request);

            // Obtener la orden
            $orden = PedidoProduccion::where('numero_pedido', $pedido)->firstOrFail();

            // Actualizar orden y prendas
            DB::beginTransaction();

            $orden->update([
                'estado' => $validatedData['estado'] ?? 'No iniciado',
                'cliente' => $validatedData['cliente'],
                'fecha_de_creacion_de_orden' => $validatedData['fecha_creacion'],
                'forma_de_pago' => $validatedData['forma_pago'] ?? null,
            ]);

            // Reemplazar prendas
            $totalPrendas = $this->prendaService->replacePrendas($pedido, $validatedData['prendas']);

            // Invalidar caché
            $this->invalidarCacheDias($pedido);

            // Log evento
            News::create([
                'event_type' => 'order_updated',
                'description' => "Orden editada: Pedido {$pedido} para cliente {$validatedData['cliente']}",
                'user_id' => auth()->id(),
                'pedido' => $pedido,
                'metadata' => ['cliente' => $validatedData['cliente'], 'total_prendas' => count($validatedData['prendas'])]
            ]);

            DB::commit();

            // Recargar relaciones
            $orden->load('prendas');

            // Broadcast evento
            broadcast(new \App\Events\OrdenUpdated($orden, 'updated'));

            return response()->json([
                'success' => true,
                'message' => 'Orden actualizada correctamente',
                'pedido' => $pedido,
                'orden' => $orden
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            \Log::error('Error de validación al editar orden', ['pedido' => $pedido, 'errors' => $e->errors()]);

            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Orden no encontrada'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al editar orden completa', ['pedido' => $pedido, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => '🚨 Error interno del servidor: No se pudo actualizar la orden. Por favor, intente nuevamente o contacte al administrador si el problema persiste.'
            ], 500);
        }
    }

    /**
     * Actualizar descripción y regenerar registros_por_orden basado en el contenido
     */
    public function updateDescripcionPrendas(Request $request)
    {
        try {
            // Validar datos
            $validatedData = $this->validationService->validateUpdateDescripcionRequest($request);

            $pedido = $validatedData['pedido'];
            $nuevaDescripcion = $validatedData['descripcion'];

            DB::beginTransaction();

            // Obtener la orden
            $orden = PedidoProduccion::where('numero_pedido', $pedido)->firstOrFail();

            // Parsear descripción
            $prendas = $this->prendaService->parseDescripcionToPrendas($nuevaDescripcion);
            $procesarRegistros = $this->prendaService->isValidParsedPrendas($prendas);

            // Si hay prendas válidas, reemplazarlas
            if ($procesarRegistros) {
                $this->prendaService->replacePrendas($pedido, $prendas);
            }

            // Invalidar caché
            $this->invalidarCacheDias($pedido);

            // Log evento
            News::create([
                'event_type' => 'description_updated',
                'description' => "Descripción y prendas actualizadas para pedido {$pedido}",
                'user_id' => auth()->id(),
                'pedido' => $pedido,
                'metadata' => ['prendas_count' => count($prendas)]
            ]);

            DB::commit();

            // Recargar relaciones
            $orden->load('prendas');

            // Broadcast evento
            broadcast(new \App\Events\OrdenUpdated($orden, 'updated'));

            // Obtener mensaje de resultado
            $mensaje = $this->prendaService->getParsedPrendasMessage($prendas);

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'prendas_procesadas' => count($prendas),
                'registros_regenerados' => $procesarRegistros
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => '❌ Error de validación: Los datos proporcionados no son válidos. Verifique el formato e intente nuevamente.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al actualizar descripción y prendas', [
                'pedido' => $request->pedido ?? 'N/A',
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => '🚨 Error interno del servidor: No se pudo actualizar la descripción y prendas. Por favor, intente nuevamente o contacte al administrador si el problema persiste.'
            ], 500);
        }
    }

    /**
     * Parsear descripción para extraer información de prendas y tallas
     */
    /**
     * DEPRECATED: Método movido a RegistroOrdenPrendaService::parseDescripcionToPrendas()
     * Se mantiene como referencia pero ya no se utiliza
     */
    // parseDescripcionToPrendas() - Ver RegistroOrdenPrendaService

    /**
     * Obtener imágenes de una orden
     */
    public function getImages($pedido)
    {
        try {
            // Buscar en PedidoProduccion
            $orden = PedidoProduccion::where('numero_pedido', $pedido)->first();
            
            if (!$orden) {
                return response()->json(['images' => []], 404);
            }

            // Por ahora retornar array vacío
            // En el futuro, aquí se cargarían imágenes de la BD o almacenamiento
            $images = [];

            return response()->json(['images' => $images]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener imágenes:', ['error' => $e->getMessage()]);
            return response()->json(['images' => []], 500);
        }
    }

    /**
     * Obtener el último proceso (área) para cada orden desde procesos_prenda y procesos_historial
     * Obtiene el proceso más reciente (por updated_at) de cada pedido
     */
    /**
     * DEPRECATED: Los siguientes métodos se han movido a RegistroOrdenProcessService
     * Se mantienen aquí como referencia pero ya no son utilizados
     * 
     * - getLastProcessByOrder() -> RegistroOrdenProcessService::getLastProcessByOrderNumbers()
     * - getCreacionOrdenEncargados() -> RegistroOrdenProcessService::getCreacionOrdenEncargados()
     * - getLastProcessByOrderByNumbers() -> RegistroOrdenProcessService::getLastProcessByOrderNumbers()
     */

    /**
     * API endpoint para calcular días en tiempo real
     * Usado en modal de tracking y tabla de órdenes
     */
    public function calcularDiasAPI(Request $request, $numeroPedido)
    {
        try {
            // Validar entrada
            if (!$numeroPedido) {
                return response()->json(['error' => 'Número de pedido requerido'], 400);
            }

            // Obtener festivos
            $festivos = Festivo::pluck('fecha')->toArray();
            
            // Obtener la orden
            $orden = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
            if (!$orden) {
                return response()->json(['error' => 'Orden no encontrada'], 404);
            }

            // Calcular días usando el método existente
            $resultado = $this->calcularTotalDiasBatchConCache([$orden], $festivos);
            $diasCalculados = $resultado[$numeroPedido] ?? 0;

            return response()->json([
                'success' => true,
                'numero_pedido' => $numeroPedido,
                'total_dias' => intval($diasCalculados),
                'timestamp' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en calcularDiasAPI: ' . $e->getMessage());
            return response()->json(['error' => 'Error al calcular días'], 500);
        }
    }

    /**
     * API endpoint para calcular días de múltiples órdenes
     * Usado para actualizar tabla completa
     */
    public function calcularDiasBatchAPI(Request $request)
    {
        try {
            // Validar entrada
            $numeroPedidos = $request->input('numero_pedidos', []);
            if (empty($numeroPedidos)) {
                return response()->json(['error' => 'Lista de pedidos requerida'], 400);
            }

            // Obtener festivos
            $festivos = Festivo::pluck('fecha')->toArray();
            
            // Obtener todas las órdenes
            $ordenes = PedidoProduccion::whereIn('numero_pedido', $numeroPedidos)->get();
            if ($ordenes->isEmpty()) {
                return response()->json(['error' => 'No se encontraron órdenes'], 404);
            }

            // Calcular días para todas
            $resultados = $this->calcularTotalDiasBatchConCache($ordenes->toArray(), $festivos);

            // Formatear respuesta
            $dias = [];
            foreach ($numeroPedidos as $pedido) {
                $dias[$pedido] = intval($resultados[$pedido] ?? 0);
            }

            return response()->json([
                'success' => true,
                'dias' => $dias,
                'total' => count($dias),
                'timestamp' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en calcularDiasBatchAPI: ' . $e->getMessage());
            return response()->json(['error' => 'Error al calcular días'], 500);
        }
    }

    /**
     * Obtener imágenes de una orden
     * GET /registros/{pedido}/images
     * 
     * Busca en PedidoProduccion y sus relaciones
     */
    public function getOrderImages($pedido)
    {
        try {
            $images = [];
            
            // Obtener desde PedidoProduccion
            $pedidoProduccion = PedidoProduccion::where('numero_pedido', $pedido)->first();
            
            if ($pedidoProduccion) {
                // Si tiene cotización asociada, obtener imágenes de la cotización
                if ($pedidoProduccion->cotizacion_id) {
                    $cotizacion = Cotizacion::find($pedidoProduccion->cotizacion_id);
                    if ($cotizacion && $cotizacion->imagenes) {
                        $images = is_array($cotizacion->imagenes) ? $cotizacion->imagenes : [];
                    }
                }
            }
            
            // Remover duplicados y resetear índices
            $images = array_values(array_unique(array_filter($images)));

            return response()->json([
                'success' => true,
                'images' => $images,
                'total' => count($images),
                'pedido' => $pedido
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al obtener imágenes de orden: ' . $e->getMessage(), [
                'pedido' => $pedido,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener imágenes'
            ], 500);
        }
    }

    /**
     * Obtener la descripción completa de prendas para una orden
     * Esta descripción se construye combinando información de prendas_pedido
     */
    public function getDescripcionPrendas($pedido)
    {
        try {
            // Buscar la orden por número de pedido o por ID
            $orden = PedidoProduccion::where('numero_pedido', $pedido)
                ->orWhere('id', $pedido)
                ->first();

            if (!$orden) {
                return response()->json([
                    'success' => false,
                    'message' => 'Orden no encontrada'
                ], 404);
            }

            // Obtener la descripción de prendas del modelo
            // El campo descripcion_prendas contiene la descripción armada
            $descripcionPrendas = $orden->descripcion_prendas ?? '';

            return response()->json([
                'success' => true,
                'descripcion_prendas' => $descripcionPrendas,
                'numero_pedido' => $orden->numero_pedido,
                'orden_id' => $orden->id
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al obtener descripción de prendas: ' . $e->getMessage(), [
                'pedido' => $pedido,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener descripción de prendas'
            ], 500);
        }
    }

    /**
     * API: Obtener procesos de una orden (para bodega tracking)
     * Busca en procesos_prenda usando el número de pedido
     */
    public function getProcesosTablaOriginal($numeroPedido)
    {
        try {
            // Buscar la orden en pedidos_produccion
            $orden = PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();

            // Obtener festivos
            $festivos = Festivo::pluck('fecha')->toArray();

            // Obtener los procesos ordenados por fecha_inicio desde procesos_prenda
            // Excluir soft-deleted
            $procesos = DB::table('procesos_prenda')
                ->where('numero_pedido', $numeroPedido)
                ->whereNull('deleted_at')  // Excluir soft-deleted
                ->orderBy('fecha_inicio', 'asc')
                ->select('id', 'proceso', 'fecha_inicio', 'encargado', 'estado_proceso')
                ->get()
                ->groupBy('proceso')
                ->map(function($grupo) {
                    return $grupo->first();
                })
                ->values();

            // Calcular días hábiles totales
            $totalDiasHabiles = 0;
            if ($procesos->count() > 0) {
                $fechaInicio = Carbon::parse($procesos->first()->fecha_inicio);
                
                $procesoDespachos = $procesos->firstWhere('proceso', 'Despachos') 
                    ?? $procesos->firstWhere('proceso', 'Entrega')
                    ?? $procesos->firstWhere('proceso', 'Despacho');
                
                if ($procesoDespachos) {
                    $fechaFin = Carbon::parse($procesoDespachos->fecha_inicio);
                } elseif ($procesos->count() > 1) {
                    $fechaFin = Carbon::parse($procesos->last()->fecha_inicio);
                } else {
                    $fechaFin = Carbon::now();
                }
                
                $totalDiasHabiles = $this->calcularDiasHabilesBatch($fechaInicio, $fechaFin, $festivos);
            }

            return response()->json([
                'numero_pedido' => $numeroPedido,
                'cliente' => $orden->cliente ?? '',
                'fecha_inicio' => $orden->fecha_de_creacion_de_orden ?? null,
                'fecha_estimada_de_entrega' => $orden->fecha_estimada_entrega ?? null,
                'procesos' => $procesos,
                'total_dias_habiles' => $totalDiasHabiles,
                'festivos' => $festivos
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener procesos de orden: ' . $e->getMessage());
            return response()->json([
                'error' => 'No se encontró la orden o no tiene permiso para verla'
            ], 404);
        }
    }
}
