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

    public function __construct(
        RegistroOrdenQueryService $queryService,
        RegistroOrdenSearchService $searchService,
        RegistroOrdenFilterService $filterService,
        RegistroOrdenExtendedQueryService $extendedQueryService,
        RegistroOrdenSearchExtendedService $extendedSearchService,
        RegistroOrdenFilterExtendedService $extendedFilterService,
        RegistroOrdenTransformService $transformService,
        RegistroOrdenProcessService $processService
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
        $lastPedido = PedidoProduccion::max('numero_pedido');
        $nextPedido = $lastPedido ? $lastPedido + 1 : 1;
        return response()->json(['next_pedido' => $nextPedido]);
    }

    public function validatePedido(Request $request)
    {
        $request->validate([
            'pedido' => 'required|integer',
        ]);

        $pedido = $request->input('pedido');
        $lastPedido = PedidoProduccion::max('numero_pedido');
        $nextPedido = $lastPedido ? $lastPedido + 1 : 1;

        $valid = ($pedido == $nextPedido);

        return response()->json([
            'valid' => $valid,
            'next_pedido' => $nextPedido,
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'pedido' => 'required|integer',
                'estado' => 'nullable|in:No iniciado,En Ejecución,Entregado,Anulada',
                'cliente' => 'required|string|max:255',
                'area' => 'nullable|string',
                'fecha_creacion' => 'required|date',
                'encargado' => 'nullable|string|max:255',
                'forma_pago' => 'nullable|string|max:255',
                'prendas' => 'required|array',
                'prendas.*.prenda' => 'required|string|max:255',
                'prendas.*.descripcion' => 'nullable|string|max:1000',
                'prendas.*.tallas' => 'required|array',
                'prendas.*.tallas.*.talla' => 'required|string|max:50',
                'prendas.*.tallas.*.cantidad' => 'required|integer|min:1',
                'allow_any_pedido' => 'nullable|boolean',
            ]);
    
            $lastPedido = PedidoProduccion::max('numero_pedido');
            $nextPedido = $lastPedido ? $lastPedido + 1 : 1;
    
            if (!$request->input('allow_any_pedido', false)) {
                if ($request->pedido != $nextPedido) {
                    return response()->json([
                        'success' => false,
                        'message' => "El número consecutivo disponible es $nextPedido"
                    ], 422);
                }
            }
    
            DB::beginTransaction();
            
            // Crear pedido en PedidoProduccion
            $estado = $request->estado ?? 'No iniciado';
            
            $pedido = PedidoProduccion::create([
                'numero_pedido' => $request->pedido,
                'cliente' => $request->cliente,
                'estado' => $estado,
                'forma_de_pago' => $request->forma_pago,
                'fecha_de_creacion_de_orden' => $request->fecha_creacion,
                'area' => $request->area ?? 'Creación Orden',
                'novedades' => null,
            ]);

            // Crear prendas en PrendaPedido
            foreach ($request->prendas as $index => $prendaData) {
                // Calcular cantidad total de la prenda
                $cantidadPrenda = 0;
                $cantidadesPorTalla = [];
                
                foreach ($prendaData['tallas'] as $talla) {
                    $cantidadPrenda += $talla['cantidad'];
                    $cantidadesPorTalla[$talla['talla']] = $talla['cantidad'];
                }

                // Crear prenda
                PrendaPedido::create([
                    'numero_pedido' => $pedido->numero_pedido,
                    'nombre_prenda' => $prendaData['prenda'],
                    'cantidad' => $cantidadPrenda,
                    'descripcion' => $prendaData['descripcion'] ?? '',
                    'cantidad_talla' => json_encode($cantidadesPorTalla),
                ]);
            }

            DB::commit();

            // Log news
            News::create([
                'event_type' => 'order_created',
                'description' => "Nueva orden registrada: Pedido {$request->pedido} para cliente {$request->cliente}",
                'user_id' => auth()->id(),
                'pedido' => $request->pedido,
                'metadata' => ['cliente' => $request->cliente, 'estado' => $estado]
            ]);

            // Broadcast event for real-time updates
            broadcast(new \App\Events\OrdenUpdated($pedido, 'created'));

            return response()->json(['success' => true, 'message' => 'Orden registrada correctamente']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error inesperado: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $pedido)
    {
        try {
            \Log::info("DEBUG: Datos recibidos en update", [
                'pedido' => $pedido,
                'all_request' => $request->all(),
                'dia_de_entrega' => $request->input('dia_de_entrega')
            ]);

            $orden = PedidoProduccion::where('numero_pedido', $pedido)->firstOrFail();

            $areaOptions = AreaOptions::getArray();
            $estadoOptions = ['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'];

            // Whitelist de columnas permitidas para edición
            $allowedColumns = [
                'estado', 'area', 'dia_de_entrega', '_pedido', 'cliente', 'descripcion', 'cantidad',
                'novedades', 'asesora', 'forma_de_pago', 'fecha_de_creacion_de_orden',
                'encargado_orden', 'dias_orden', 'inventario', 'encargados_inventario',
                'dias_inventario', 'insumos_y_telas', 'encargados_insumos', 'dias_insumos',
                'corte', 'encargados_de_corte', 'dias_corte', 'bordado', 'codigo_de_bordado',
                'dias_bordado', 'estampado', 'encargados_estampado', 'dias_estampado',
                'costura', 'modulo', 'dias_costura', 'reflectivo', 'encargado_reflectivo',
                'total_de_dias_reflectivo', 'lavanderia', 'encargado_lavanderia',
                'dias_lavanderia', 'arreglos', 'encargado_arreglos', 'total_de_dias_arreglos',
                'marras', 'encargados_marras', 'total_de_dias_marras', 'control_de_calidad',
                'encargados_calidad', 'dias_c_c', 'entrega', 'encargados_entrega', 'despacho', 'column_52'
            ];

            // Columnas que son de tipo fecha
            $dateColumns = [
                'fecha_de_creacion_de_orden', 'insumos_y_telas', 'corte', 'costura', 
                'lavanderia', 'arreglos', 'control_de_calidad', 'entrega', 'despacho'
            ];

            // Validar área manualmente en lugar de usar la regla 'in'
            $areaRecibida = $request->input('area');
            $areasValidas = AreaOptions::getArray();
            
            if ($areaRecibida && !in_array($areaRecibida, $areasValidas)) {
                return response()->json([
                    'success' => false,
                    'message' => "El área '{$areaRecibida}' no es válida. Áreas válidas: " . implode(', ', $areasValidas)
                ], 422);
            }

            $validatedData = $request->validate([
                'estado' => 'nullable|in:' . implode(',', $estadoOptions),
                'dia_de_entrega' => 'nullable|integer|in:15,20,25,30',
            ]);
            
            // Agregar el área validada manualmente
            if ($areaRecibida) {
                $validatedData['area'] = $areaRecibida;
            }
            
            // Convertir string vacío a null para dia_de_entrega
            if (isset($validatedData['dia_de_entrega']) && $validatedData['dia_de_entrega'] === '') {
                $validatedData['dia_de_entrega'] = null;
            }

            // Validar columnas adicionales permitidas como strings
            $additionalValidation = [];
            foreach ($allowedColumns as $col) {
                if ($request->has($col) && $col !== 'estado' && $col !== 'area' && $col !== 'dia_de_entrega') {
                    // Campos TEXT que pueden ser más largos
                    if ($col === 'descripcion' || $col === 'novedades') {
                        $additionalValidation[$col] = 'nullable|string|max:65535';
                    } else {
                        $additionalValidation[$col] = 'nullable|string|max:255';
                    }
                }
            }
            $additionalData = $request->validate($additionalValidation);

            $updates = [];
            $updatedFields = [];
            if (array_key_exists('estado', $validatedData)) {
                $updates['estado'] = $validatedData['estado'];
            }
            if (array_key_exists('area', $validatedData)) {
                $nuevaArea = $validatedData['area'];
                
                // Crear o actualizar un proceso en procesos_prenda usando el modelo Eloquent
                // Esto dispara el Observer que actualiza el área en pedidos_produccion
                $procesoExistente = ProcesoPrenda::where('numero_pedido', $pedido)
                    ->where('proceso', $nuevaArea)
                    ->first();
                
                if (!$procesoExistente) {
                    // Crear nuevo proceso usando Eloquent (dispara Observer)
                    ProcesoPrenda::create([
                        'numero_pedido' => $pedido,
                        'proceso' => $nuevaArea,
                        'fecha_inicio' => now()->toDateTimeString(),
                        'encargado' => auth()->user()->name ?? 'Sistema'
                    ]);
                    \Log::info("Proceso CREADO para pedido {$pedido}: {$nuevaArea}");
                } else {
                    // Actualizar solo la fecha_inicio si ya existe
                    $procesoExistente->update([
                        'fecha_inicio' => now()->toDateTimeString(),
                        'encargado' => auth()->user()->name ?? 'Sistema'
                    ]);
                    \Log::info("Proceso ACTUALIZADO para pedido {$pedido}: {$nuevaArea}");
                }
            }
            if (array_key_exists('dia_de_entrega', $validatedData)) {
                $diaEntrega = $validatedData['dia_de_entrega'];
                if ($diaEntrega !== null) {
                    $updates['dia_de_entrega'] = $diaEntrega;
                    
                    // Recalcular fecha_estimada_de_entrega si se actualiza dia_de_entrega
                    $orden->dia_de_entrega = $diaEntrega;
                    $fechaEstimada = $orden->calcularFechaEstimada();
                    if ($fechaEstimada) {
                        $updates['fecha_estimada_de_entrega'] = $fechaEstimada->format('Y-m-d');
                    }
                    
                    \Log::info("Día de entrega actualizado para pedido {$pedido}: {$diaEntrega}");
                }
            }

            // Agregar otras columnas permitidas y convertir fechas si es necesario
            foreach ($additionalData as $key => $value) {
                // Si es una columna de fecha y el valor no está vacío, convertir formato
                if (in_array($key, $dateColumns) && !empty($value)) {
                    try {
                        // Intentar parsear desde formato d/m/Y (11/11/2025)
                        $date = \Carbon\Carbon::createFromFormat('d/m/Y', $value);
                        $updates[$key] = $date->format('Y-m-d');
                    } catch (\Exception $e) {
                        try {
                            // Si falla, intentar parsear como fecha genérica (puede ser Y-m-d ya)
                            $date = \Carbon\Carbon::parse($value);
                            $updates[$key] = $date->format('Y-m-d');
                        } catch (\Exception $e2) {
                            // Si todo falla, guardar el valor tal cual
                            $updates[$key] = $value;
                        }
                    }
                } else {
                    $updates[$key] = $value;
                }
            }

            $oldStatus = $orden->estado;
            $oldArea = $orden->area;

            if (!empty($updates)) {
                $orden->update($updates);
                
                // Invalidar caché de días calculados para esta orden
                $this->invalidarCacheDias($pedido);

                // Log news if status or area changed
                if (isset($updates['estado']) && $updates['estado'] !== $oldStatus) {
                    News::create([
                        'event_type' => 'status_changed',
                        'description' => "Estado cambiado para pedido {$pedido}: {$oldStatus} → {$updates['estado']}",
                        'user_id' => auth()->id(),
                        'pedido' => $pedido,
                        'metadata' => ['old_status' => $oldStatus, 'new_status' => $updates['estado']]
                    ]);
                }

                if (isset($updates['area']) && $updates['area'] !== $oldArea) {
                    News::create([
                        'event_type' => 'area_changed',
                        'description' => "Área cambiada para pedido {$pedido}: {$oldArea} → {$updates['area']}",
                        'user_id' => auth()->id(),
                        'pedido' => $pedido,
                        'metadata' => ['old_area' => $oldArea, 'new_area' => $updates['area']]
                    ]);
                }
            }

            // Broadcast event for real-time updates
            $orden->refresh(); // Reload to get updated data
            
            // Si se actualizó el área, obtener el último proceso de procesos_prenda y asignarlo al modelo
            if (array_key_exists('area', $validatedData)) {
                $ultimoProceso = DB::table('procesos_prenda')
                    ->where('numero_pedido', $pedido)
                    ->orderBy('updated_at', 'desc')
                    ->first();
                
                if ($ultimoProceso) {
                    // Asignar el último proceso como el área actual para el evento del WebSocket
                    $orden->area = $ultimoProceso->proceso;
                }
            }
            
            // Preparar array de campos que cambiaron
            $changedFields = [];
            if (isset($updates['estado'])) {
                $changedFields[] = 'estado';
            }
            if (array_key_exists('area', $validatedData)) {
                $changedFields[] = 'area';
            }
            if (isset($updates['dia_de_entrega'])) {
                $changedFields[] = 'dia_de_entrega';
            }
            
            broadcast(new \App\Events\OrdenUpdated($orden, 'updated', $changedFields));

            // Broadcast evento específico para Control de Calidad (después de refresh)
            if (isset($updates['area']) && $updates['area'] !== $oldArea) {
                if ($updates['area'] === 'Control-Calidad') {
                    // Orden ENTRA a Control de Calidad
                    broadcast(new \App\Events\ControlCalidadUpdated($orden, 'added', 'pedido'));
                } elseif ($oldArea === 'Control-Calidad' && $updates['area'] !== 'Control-Calidad') {
                    // Orden SALE de Control de Calidad
                    broadcast(new \App\Events\ControlCalidadUpdated($orden, 'removed', 'pedido'));
                }
            }

            // Obtener la orden actualizada para retornar todos los campos
            $ordenActualizada = PedidoProduccion::where('numero_pedido', $pedido)->first();
            
            // Preparar datos de la orden para retornar
            $ordenData = $ordenActualizada->toArray();
            
            // Formatear TODAS las columnas de fecha a DD/MM/YYYY para el frontend
            $dateColumns = [
                'fecha_de_creacion_de_orden',
                'fecha_estimada_de_entrega',
                'inventario',
                'insumos_y_telas',
                'corte',
                'bordado',
                'estampado',
                'costura',
                'reflectivo',
                'lavanderia',
                'arreglos',
                'marras',
                'control_de_calidad',
                'entrega',
                'despacho'
            ];
            
            foreach ($dateColumns as $column) {
                // Verificar si la columna existe y tiene valor
                if (isset($ordenData[$column]) && $ordenData[$column] !== null && $ordenData[$column] !== '') {
                    try {
                        $valorOriginal = $ordenData[$column];
                        // Parsear y formatear la fecha
                        $fechaParsed = \Carbon\Carbon::parse($valorOriginal);
                        $ordenData[$column] = $fechaParsed->format('d/m/Y');
                        
                        \Log::info("CONTROLADOR: Fecha formateada", [
                            'columna' => $column,
                            'original' => $valorOriginal,
                            'formateada' => $ordenData[$column]
                        ]);
                    } catch (\Exception $e) {
                        \Log::warning("CONTROLADOR: Error formateando fecha", [
                            'columna' => $column,
                            'valor' => $ordenData[$column] ?? 'null',
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            // Log DESPUÉS del formateo
            \Log::info("\n========== CONTROLADOR: ORDEN ACTUALIZADA (FORMATEADA) ==========", [
                'pedido' => $pedido,
                'fecha_de_creacion_de_orden' => $ordenData['fecha_de_creacion_de_orden'] ?? 'N/A',
                'fecha_estimada_de_entrega' => $ordenData['fecha_estimada_de_entrega'] ?? 'N/A',
                'dia_de_entrega' => $ordenActualizada->dia_de_entrega,
                'updated_fields' => $updatedFields
            ]);
            
            \Log::info("CONTROLADOR: Datos que se retornan al cliente (FORMATEADOS)", [
                'pedido' => $pedido,
                'order_data_fechas' => [
                    'fecha_de_creacion_de_orden' => $ordenData['fecha_de_creacion_de_orden'] ?? 'N/A',
                    'fecha_estimada_de_entrega' => $ordenData['fecha_estimada_de_entrega'] ?? 'N/A'
                ]
            ]);

            return response()->json([
                'success' => true,
                'updated_fields' => $updatedFields,
                'order' => $ordenData,
                'totalDiasCalculados' => CacheCalculosService::getTotalDiasBatch([$ordenActualizada], Festivo::pluck('fecha')->toArray())
            ]);
        } catch (\Exception $e) {
            // Log del error para debugging
            \Log::error('Error al actualizar orden', [
                'pedido' => $pedido,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Capturar cualquier error y devolver JSON con mensaje
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
            DB::beginTransaction();

            // Obtener el pedido desde la nueva arquitectura
            $orden = PedidoProduccion::where('numero_pedido', $pedido)->firstOrFail();

            // Eliminar todas las prendas asociadas (las entregas se eliminan automáticamente por cascada)
            $orden->prendas()->delete();
            
            // Eliminar el pedido
            $orden->delete();

            DB::commit();
            
            // Invalidar caché de días calculados para esta orden
            $this->invalidarCacheDias($pedido);

            // Log news
            News::create([
                'event_type' => 'order_deleted',
                'description' => "Orden eliminada: Pedido {$pedido}",
                'user_id' => auth()->id(),
                'pedido' => $pedido,
                'metadata' => ['action' => 'deleted']
            ]);

            // Broadcast event for real-time updates
            broadcast(new \App\Events\OrdenUpdated(['numero_pedido' => $pedido], 'deleted'));

            return response()->json(['success' => true, 'message' => 'Orden eliminada correctamente']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Orden no encontrada'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
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

            $oldPedido = $validatedData['old_pedido'];
            $newPedido = $validatedData['new_pedido'];

            // Verificar que la orden antigua existe
            $orden = PedidoProduccion::where('numero_pedido', $oldPedido)->first();
            if (!$orden) {
                return response()->json([
                    'success' => false,
                    'message' => 'La orden no existe'
                ], 404);
            }

            // Verificar que el nuevo pedido no existe ya
            $existingOrder = PedidoProduccion::where('numero_pedido', $newPedido)->first();
            if ($existingOrder) {
                return response()->json([
                    'success' => false,
                    'message' => "El número de pedido {$newPedido} ya está en uso"
                ], 422);
            }

            DB::beginTransaction();

            // Actualizar el número de pedido en la nueva arquitectura
            $orden->update(['numero_pedido' => $newPedido]);

            DB::commit();

            // Invalidar caché para ambos pedidos
            $this->invalidarCacheDias($oldPedido);
            $this->invalidarCacheDias($newPedido);

            // Log news
            News::create([
                'event_type' => 'pedido_updated',
                'description' => "Número de pedido actualizado: {$oldPedido} → {$newPedido}",
                'user_id' => auth()->id(),
                'pedido' => $newPedido,
                'metadata' => ['old_pedido' => $oldPedido, 'new_pedido' => $newPedido]
            ]);

            // Broadcast event for real-time updates
            broadcast(new \App\Events\OrdenUpdated($orden->fresh(), 'updated'));

            return response()->json([
                'success' => true,
                'message' => 'Número de pedido actualizado correctamente',
                'old_pedido' => $oldPedido,
                'new_pedido' => $newPedido
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos: ' . json_encode($e->errors())
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al actualizar pedido', [
                'old_pedido' => $request->old_pedido,
                'new_pedido' => $request->new_pedido,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el número de pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener registros por orden (API para el modal de edición)
     * Retorna las prendas desde la nueva arquitectura
     */
    public function getRegistrosPorOrden($pedido)
    {
        try {
            // Obtener el pedido desde la nueva arquitectura
            $orden = PedidoProduccion::where('numero_pedido', $pedido)->firstOrFail();
            
            // Obtener prendas con sus tallas y cantidades
            $prendas = $orden->prendas()
                ->select('id', 'nombre_prenda', 'descripcion', 'cantidad_talla')
                ->get()
                ->map(function($prenda) use ($orden) {
                    // Parsear cantidad_talla desde JSON
                    $cantidadTalla = is_string($prenda->cantidad_talla) 
                        ? json_decode($prenda->cantidad_talla, true) 
                        : $prenda->cantidad_talla;
                    
                    // Convertir a formato compatible con registros_por_orden
                    $registros = [];
                    if (is_array($cantidadTalla)) {
                        foreach ($cantidadTalla as $talla => $cantidad) {
                            $registros[] = [
                                'pedido' => $orden->numero_pedido,
                                'cliente' => $orden->cliente,
                                'prenda' => $prenda->nombre_prenda,
                                'descripcion' => $prenda->descripcion ?? '',
                                'talla' => $talla,
                                'cantidad' => $cantidad,
                                'total_pendiente_por_talla' => $cantidad,
                                'costurero' => null,
                                'total_producido_por_talla' => null,
                                'fecha_completado' => null
                            ];
                        }
                    }
                    return $registros;
                })
                ->flatten(1)
                ->values();

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
            $request->validate([
                'pedido' => 'required|integer',
                'estado' => 'nullable|in:No iniciado,En Ejecución,Entregado,Anulada',
                'cliente' => 'required|string|max:255',
                'fecha_creacion' => 'required|date',
                'encargado' => 'nullable|string|max:255',
                'forma_pago' => 'nullable|string|max:255',
                'prendas' => 'required|array',
                'prendas.*.prenda' => 'required|string|max:255',
                'prendas.*.descripcion' => 'nullable|string|max:1000',
                'prendas.*.tallas' => 'required|array',
                'prendas.*.tallas.*.talla' => 'required|string|max:50',
                'prendas.*.tallas.*.cantidad' => 'required|integer|min:1',
            ]);

            DB::beginTransaction();

            // Obtener la orden de la nueva arquitectura
            $orden = PedidoProduccion::where('numero_pedido', $pedido)->firstOrFail();

            // Actualizar datos de la orden
            $orden->update([
                'estado' => $request->estado ?? 'No iniciado',
                'cliente' => $request->cliente,
                'fecha_de_creacion_de_orden' => $request->fecha_creacion,
                'forma_de_pago' => $request->forma_pago,
            ]);

            // Eliminar todas las prendas existentes
            $orden->prendas()->delete();

            // Crear nuevas prendas
            foreach ($request->prendas as $prendaData) {
                // Calcular cantidad total de la prenda
                $cantidadPrenda = 0;
                $cantidadesPorTalla = [];

                foreach ($prendaData['tallas'] as $talla) {
                    $cantidadPrenda += $talla['cantidad'];
                    $cantidadesPorTalla[$talla['talla']] = $talla['cantidad'];
                }

                // Crear prenda
                PrendaPedido::create([
                    'numero_pedido' => $orden->numero_pedido,
                    'nombre_prenda' => $prendaData['prenda'],
                    'cantidad' => $cantidadPrenda,
                    'descripcion' => $prendaData['descripcion'] ?? '',
                    'cantidad_talla' => json_encode($cantidadesPorTalla),
                ]);
            }

            // Invalidar caché
            $this->invalidarCacheDias($pedido);

            // Log news
            News::create([
                'event_type' => 'order_updated',
                'description' => "Orden editada: Pedido {$pedido} para cliente {$request->cliente}",
                'user_id' => auth()->id(),
                'pedido' => $pedido,
                'metadata' => ['cliente' => $request->cliente, 'total_prendas' => count($request->prendas)]
            ]);

            DB::commit();

            // Recargar para obtener relaciones
            $orden->load('prendas');

            // Broadcast event for real-time updates
            broadcast(new \App\Events\OrdenUpdated($orden, 'updated'));

            return response()->json([
                'success' => true,
                'message' => 'Orden actualizada correctamente',
                'pedido' => $pedido,
                'orden' => $orden
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            \Log::error('Error de validación al editar orden', [
                'pedido' => $pedido,
                'errors' => $e->errors()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Orden no encontrada'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al editar orden completa', [
                'pedido' => $pedido,
                'error' => $e->getMessage()
            ]);

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
            $validatedData = $request->validate([
                'pedido' => 'required|integer',
                'descripcion' => 'required|string'
            ]);

            $pedido = $validatedData['pedido'];
            $nuevaDescripcion = $validatedData['descripcion'];

            DB::beginTransaction();

            // Obtener la orden desde la nueva arquitectura
            $orden = PedidoProduccion::where('numero_pedido', $pedido)->firstOrFail();

            // Parsear la nueva descripción para extraer prendas y tallas
            $prendas = $this->parseDescripcionToPrendas($nuevaDescripcion);
            $mensaje = '';
            $procesarRegistros = false;

            // Verificar si se encontraron prendas válidas con el formato estructurado
            if (!empty($prendas)) {
                $totalTallasEncontradas = 0;
                foreach ($prendas as $prenda) {
                    $totalTallasEncontradas += count($prenda['tallas']);
                }

                if ($totalTallasEncontradas > 0) {
                    $procesarRegistros = true;

                    // Eliminar todas las prendas existentes
                    $orden->prendas()->delete();

                    // Crear nuevas prendas desde la descripción parseada
                    $totalCantidad = 0;
                    foreach ($prendas as $prenda) {
                        $cantidadPrenda = 0;
                        $cantidadesPorTalla = [];

                        foreach ($prenda['tallas'] as $talla) {
                            $cantidadPrenda += $talla['cantidad'];
                            $totalCantidad += $talla['cantidad'];
                            $cantidadesPorTalla[$talla['talla']] = $talla['cantidad'];
                        }

                        // Crear prenda en la nueva arquitectura
                        PrendaPedido::create([
                            'numero_pedido' => $orden->numero_pedido,
                            'nombre_prenda' => $prenda['nombre'],
                            'cantidad' => $cantidadPrenda,
                            'descripcion' => $prenda['descripcion'] ?? '',
                            'cantidad_talla' => json_encode($cantidadesPorTalla),
                        ]);
                    }

                    // Actualizar cantidad total en la orden
                    $orden->update(['cantidad' => $totalCantidad]);

                    $mensaje = "✅ Descripción actualizada y registros regenerados automáticamente. Se procesaron " . count($prendas) . " prenda(s) con " . $totalTallasEncontradas . " talla(s).";
                } else {
                    $mensaje = "⚠️ Descripción actualizada, pero no se encontraron tallas válidas. Los registros existentes se mantuvieron intactos.";
                }
            } else {
                $mensaje = "📝 Descripción actualizada como texto libre. Para regenerar registros automáticamente, use el formato:\n\nPrenda 1: NOMBRE\nDescripción: detalles\nTallas: M:5, L:3";
            }

            // Invalidar caché
            $this->invalidarCacheDias($pedido);

            // Log news
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

            // Broadcast events
            broadcast(new \App\Events\OrdenUpdated($orden, 'updated'));

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
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
    private function parseDescripcionToPrendas($descripcion)
    {
        $prendas = [];
        $lineas = explode("\n", $descripcion);
        $prendaActual = null;

        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if (empty($linea)) continue;

            // Detectar inicio de nueva prenda (formato: "Prenda X: NOMBRE")
            if (preg_match('/^Prenda\s+\d+:\s*(.+)$/i', $linea, $matches)) {
                // Guardar prenda anterior si existe
                if ($prendaActual !== null) {
                    $prendas[] = $prendaActual;
                }
                
                // Iniciar nueva prenda
                $prendaActual = [
                    'nombre' => trim($matches[1]),
                    'descripcion' => '',
                    'tallas' => []
                ];
            }
            // Detectar descripción (formato: "Descripción: TEXTO")
            elseif (preg_match('/^Descripción:\s*(.+)$/i', $linea, $matches)) {
                if ($prendaActual !== null) {
                    $prendaActual['descripcion'] = trim($matches[1]);
                }
            }
            // Detectar tallas (formato: "Tallas: M:5, L:3, XL:2")
            elseif (preg_match('/^Tallas:\s*(.+)$/i', $linea, $matches)) {
                if ($prendaActual !== null) {
                    $tallasStr = trim($matches[1]);
                    $tallasPares = explode(',', $tallasStr);
                    
                    foreach ($tallasPares as $par) {
                        $par = trim($par);
                        if (preg_match('/^([^:]+):(\d+)$/', $par, $tallaMatches)) {
                            $prendaActual['tallas'][] = [
                                'talla' => trim($tallaMatches[1]),
                                'cantidad' => intval($tallaMatches[2])
                            ];
                        }
                    }
                }
            }
        }

        // Agregar la última prenda si existe
        if ($prendaActual !== null) {
            $prendas[] = $prendaActual;
        }

        return $prendas;
    }

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
