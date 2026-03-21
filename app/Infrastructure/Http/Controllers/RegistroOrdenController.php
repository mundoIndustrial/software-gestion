<?php
namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Exceptions\RegistroOrdenPedidoNumberException;
use Illuminate\Http\Request;
use App\Models\PedidoProduccion;
use App\Events\OrdenUpdated;
use App\Services\RegistroOrdenValidationService;
use App\Services\RegistroOrdenCreationService;
use App\Services\RegistroOrdenUpdateService;
use App\Services\RegistroOrdenDeletionService;
use App\Services\RegistroOrdenNumberService;
use App\Services\RegistroOrdenPrendaService;
use App\Services\RegistroOrdenCacheService;
use App\Services\RegistroOrdenEntregasService;
use App\Services\RegistroOrdenProcessesService;
use App\Models\News;
use Illuminate\Support\Facades\DB;
use App\Services\PrendaCotizacionTemplateService;
use App\Services\CacheCalculosService;
use App\Services\FestivosColombiaService;
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

    public function __construct(
        RegistroOrdenValidationService $validationService,
        RegistroOrdenCreationService $creationService,
        RegistroOrdenUpdateService $updateService,
        RegistroOrdenDeletionService $deletionService,
        RegistroOrdenNumberService $numberService,
        RegistroOrdenPrendaService $prendaService,
        RegistroOrdenCacheService $cacheService,
        RegistroOrdenEntregasService $entregasService,
        RegistroOrdenProcessesService $processesService
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
        return $this->tryExec(function() use ($request) {
            // Validar datos
            $validatedData = $this->validationService->validateStoreRequest($request);

            // Verificar numero consecutivo
            $nextPedido = $this->numberService->getNextNumber();
            
            if (!$request->input('allow_any_pedido', false)) {
                if ($request->pedido != $nextPedido) {
                    throw RegistroOrdenPedidoNumberException::unexpectedNumber(
                        $nextPedido,
                        $request->pedido
                    );
                }
            }

            // Crear orden con todas sus prendas
            $pedido = $this->creationService->createOrder($validatedData);

            // Registrar evento
            $this->creationService->logOrderCreated(
                $pedido->numero_pedido,
                $validatedData['cliente'],
                $validatedData['estado'] ?? 'Pendiente'
            );

            // Broadcast evento
            $this->creationService->broadcastOrderCreated($pedido);

            return response()->json([
                'success' => true,
                'message' => 'Orden registrada correctamente',
                'pedido' => $pedido->numero_pedido
            ]);
        });
    }

    public function update(Request $request, $pedido)
    {
        return $this->tryExec(function() use ($request, $pedido) {
            // Obtener la orden
            $orden = PedidoProduccion::where('numero_pedido', $pedido)
                ->firstOrFail();

            // Validar datos
            $validatedData = $this->validationService->validateUpdateRequest($request);

            // Ejecutar actualizacion delegada al servicio
            $response = $this->updateService->updateOrder($orden, $validatedData);

            // ðŸ†• Obtener la orden actualizada con todos los campos calculados
            $ordenActualizada = $orden->fresh();
            
            // ðŸ†• Preparar campos que fueron realmente actualizados (incluyendo los calculados)
            $changedFields = array_keys($validatedData);
            
            // ðŸ†• Si se actualizÃ³ dia_de_entrega, anadir fecha_estimada_de_entrega a los campos cambiados
            if (in_array('dia_de_entrega', $changedFields) && !in_array('fecha_estimada_de_entrega', $changedFields)) {
                $changedFields[] = 'fecha_estimada_de_entrega';
            }
            
            // ðŸ†• Broadcast eventos con la orden actualizada y los campos reales (con manejo de errores)
            try {
                broadcast(new \App\Events\OrdenUpdated($ordenActualizada, 'updated', $changedFields));
                \Log::info(" Broadcast enviado exitosamente para pedido {$ordenActualizada->numero_pedido}", ['campos' => $changedFields]);
            } catch (\Exception $e) {
                \Log::warning(" Fallo en broadcast para pedido {$ordenActualizada->numero_pedido}, pero la actualizacion fue exitosa", [
                    'error' => $e->getMessage(),
                    'codigo' => $e->getCode()
                ]);
                // No re-lanzamos la excepciÃ³n para que la actualizacion sea exitosa incluso sin broadcast
            }

            return response()->json($response);
        });
    }

    public function destroy($pedido)
    {
        return $this->tryExec(function() use ($pedido) {
            $this->deletionService->deleteOrder($pedido);
            
            // Broadcast evento
            $this->deletionService->broadcastOrderDeleted($pedido);

            return response()->json([
                'success' => true,
                'message' => 'Orden eliminada correctamente',
                'pedido' => $pedido
            ]);
        });
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
        return $this->tryExec(function() use ($request) {
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
                'message' => 'numero de pedido actualizado correctamente',
                'old_pedido' => $validatedData['old_pedido'],
                'new_pedido' => $validatedData['new_pedido']
            ]);
        });
    }

    /**
     * Obtener registros por orden (API para el modal de ediciÃ³n)
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
     * Editar orden completa (actualiza tabla_original y registros_por_orden)
     */
    public function editFullOrder(Request $request, $pedido)
    {
        return $this->tryExec(function() use ($request, $pedido) {
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

            // Invalidar cache
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
        });
    }

    /**
     * Actualizar descripciÃ³n y regenerar registros_por_orden basado en el contenido
     */
    public function updateDescripcionPrendas(Request $request)
    {
        return $this->tryExec(function() use ($request) {
            // Validar datos
            $validatedData = $this->validationService->validateUpdateDescripcionRequest($request);

            $pedido = $validatedData['pedido'];
            $nuevaDescripcion = $validatedData['descripcion'];

            DB::beginTransaction();

            // Obtener la orden
            $orden = PedidoProduccion::where('numero_pedido', $pedido)->firstOrFail();

            // Parsear descripciÃ³n
            $prendas = $this->prendaService->parseDescripcionToPrendas($nuevaDescripcion);
            $procesarRegistros = $this->prendaService->isValidParsedPrendas($prendas);

            // Si hay prendas validas, reemplazarlas
            if ($procesarRegistros) {
                $this->prendaService->replacePrendas($pedido, $prendas);
            }

            // Invalidar cache
            $this->invalidarCacheDias($pedido);

            // Log evento
            News::create([
                'event_type' => 'description_updated',
                'description' => "DescripciÃ³n y prendas actualizadas para pedido {$pedido}",
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
        });
    }

    /**
     * Parsear descripciÃ³n para extraer informaciÃ³n de prendas y tallas
     */
    /**
     * DEPRECATED: Metodo movido a RegistroOrdenPrendaService::parseDescripcionToPrendas()
     * Se mantiene como referencia pero ya no se utiliza
     */
    // parseDescripcionToPrendas() - Ver RegistroOrdenPrendaService

    /**
     * Obtener detalles de una orden especifica para el modal
     * GET /orders/{numero_pedido}
     */
    public function show($numeroPedido)
    {
        try {
            // Buscar la orden en PedidoProduccion con relaciones
            $order = PedidoProduccion::with('asesora')->where('numero_pedido', $numeroPedido)->first();
            
            if (!$order) {
                return response()->json(['error' => 'Orden no encontrada'], 404);
            }

            // Obtener nombre de la asesora
            $asesoraName = '';
            if ($order->asesora) {
                $asesoraName = $order->asesora->name ?? '';
            }

            // Obtener datos basicos
            $orderData = [
                'id' => $order->id,
                'numero_pedido' => $order->numero_pedido,
                'cliente' => $order->cliente,
                'fecha_de_creacion_de_orden' => $order->fecha_de_creacion_de_orden,
                'descripcion_prendas' => $order->descripcion_prendas ?? '',
                'estado' => $order->estado,
                'forma_de_pago' => $order->forma_de_pago ?? '-',
                'area' => $order->area,
                'novedades' => $order->novedades,
                'total_cantidad' => 0,
                'total_entregado' => 0,
                'cantidad' => 0,
                'encargado_orden' => '',
                'asesora' => $asesoraName,
            ];

            // Calcular totales si existen prendas
            try {
                $totalCantidad = DB::table('prendas')
                    ->where('numero_pedido', $numeroPedido)
                    ->sum('cantidad');
                $orderData['total_cantidad'] = $totalCantidad ?? 0;
                $orderData['cantidad'] = $totalCantidad ?? 0;
            } catch (\Exception $e) {
                \Log::warning('Error calculando cantidad: ' . $e->getMessage());
            }

            // Calcular entregas
            try {
                $totalEntregado = DB::table('entregas')
                    ->where('numero_pedido', $numeroPedido)
                    ->sum('cantidad_entregada');
                $orderData['total_entregado'] = $totalEntregado ?? 0;
            } catch (\Exception $e) {
                \Log::warning('Error calculando entregas: ' . $e->getMessage());
            }

            // Obtener prendas - usar plantilla si está relacionado a cotizaciÃ³n
            try {
                // Verificar si el pedido está relacionado a una cotizaciÃ³n
                $esCotizacion = DB::table('pedidos_produccion')
                    ->where('numero_pedido', $numeroPedido)
                    ->whereNotNull('cotizacion_id')
                    ->exists();

                if ($esCotizacion) {
                    // Usar plantilla para cotizaciones
                    $templateService = new PrendaCotizacionTemplateService();
                    $orderData['prendas'] = $templateService->generarPlantillaPrendas($numeroPedido);
                    $orderData['es_cotizacion'] = true;
                } else {
                    // Usar formato simple para pedidos sin cotizaciÃ³n
                    $prendas = DB::table('prendas_pedido')
                        ->where('numero_pedido', $numeroPedido)
                        ->orderBy('id', 'asc')
                        ->get(['id', 'nombre_prenda', 'descripcion']);

                    // Formatear prendas con tallas desde tabla relacional
                    $prendasFormato = [];
                    foreach ($prendas as $index => $prenda) {
                        // Obtener tallas desde tabla relacional
                        $tallasDb = DB::table('prenda_pedido_tallas')
                            ->where('prenda_pedido_id', $prenda->id)
                            ->select('genero', 'talla', 'cantidad')
                            ->get();
                        
                        // Construir string de tallas para display
                        $tallasStr = $tallasDb->map(function($t) {
                            return "{$t->talla}:{$t->cantidad}";
                        })->implode(', ');
                        
                        $prendasFormato[] = [
                            'numero' => $index + 1,
                            'nombre' => $prenda->nombre_prenda ?? '-',
                            'descripcion' => $prenda->descripcion ?? '-',
                            'tallas' => $tallasStr
                        ];
                    }
                    $orderData['prendas'] = $prendasFormato;
                    $orderData['es_cotizacion'] = false;
                }
            } catch (\Exception $e) {
                \Log::warning('Error obteniendo prendas: ' . $e->getMessage());
                $orderData['prendas'] = [];
                $orderData['es_cotizacion'] = false;
            }

            return response()->json($orderData);
        } catch (\Exception $e) {
            \Log::error('Error en show de orden: ' . $e->getMessage() . ' - ' . $e->getFile() . ':' . $e->getLine());
            return response()->json(['error' => 'Error al obtener datos'], 500);
        }
    }

    /**
     * Obtener todas las opciones disponibles para filtros
     * GET /registros/filter-options
     */
    public function getFilterOptions()
    {
        try {
            $options = [
                'estados' => PedidoProduccion::ESTADOS,
                'dias_entrega' => PedidoProduccion::DIAS_ENTREGA,
                'areas' => PedidoProduccion::distinct()->pluck('area')->filter()->sort()->values()->toArray(),
                'clientes' => PedidoProduccion::distinct()->pluck('cliente')->filter()->sort()->values()->toArray(),
                'asesores' => PedidoProduccion::with('asesora')->get()->pluck('asesora.name')->filter()->unique()->sort()->values()->toArray(),
                'formas_pago' => PedidoProduccion::distinct()->pluck('forma_de_pago')->filter()->sort()->values()->toArray(),
                'encargados' => PedidoProduccion::distinct()->pluck('encargado_orden')->filter()->sort()->values()->toArray(),
            ];

            return response()->json([
                'success' => true,
                'options' => $options
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener opciones de filtro: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener opciones de filtro'
            ], 500);
        }
    }

    /**
     * Obtener opciones de una columna especifica con paginaciÃ³n y busqueda
     * GET /registros/filter-column-options/{column}
     */
    public function getColumnFilterOptions($column, Request $request)
    {
        try {
            $page = $request->input('page', 1);
            $limit = $request->input('limit', 25);
            $search = $request->input('search', '');
            
            $options = [];
            $total = 0;

            switch ($column) {
                case 'estado':
                    $options = PedidoProduccion::ESTADOS;
                    break;
                case 'area':
                    $query = PedidoProduccion::distinct()->pluck('area')->filter()->sort()->values()->toArray();
                    $options = $query;
                    break;
                case 'dia_entrega':
                    $options = PedidoProduccion::DIAS_ENTREGA;
                    break;
                case 'pedido':
                    // Aplicar busqueda si existe
                    $query = PedidoProduccion::distinct();
                    if (!empty($search)) {
                        $query->where('numero_pedido', 'LIKE', "%{$search}%");
                    }
                    $options = $query->pluck('numero_pedido')->filter()->sort()->values()->toArray();
                    break;
                case 'cliente':
                    // Aplicar busqueda si existe
                    $query = PedidoProduccion::distinct();
                    if (!empty($search)) {
                        $query->where('cliente', 'LIKE', "%{$search}%");
                    }
                    $options = $query->pluck('cliente')->filter()->sort()->values()->toArray();
                    break;
                case 'descripcion':
                    // Para descripciÃ³n, generar descripciones detalladas de prendas (como en recibos)
                    $ordenes = PedidoProduccion::with([
                        'prendas.variantes',
                        'prendas.coloresTelas.tela',
                        'prendas.coloresTelas.color'
                    ])->get();
                    $descripcionesMap = [];
                    
                    \Log::info("[FILTRO-DESCRIPCION] Iniciando generaciÃ³n de descripciones", ['total_ordenes' => $ordenes->count()]);
                    
                    foreach ($ordenes as $orden) {
                        // Obtener prendas del pedido
                        if ($orden->prendas && $orden->prendas->count() > 0) {
                            foreach ($orden->prendas as $index => $prenda) {
                                // Generar descripciÃ³n detallada
                                $descripcionDetallada = $this->generarDescripcionPrenda($prenda, $index + 1);
                                
                                \Log::info("[FILTRO-DESCRIPCION] Prenda procesada", [
                                    'numero_pedido' => $orden->numero_pedido,
                                    'prenda_nombre' => $prenda->nombre_prenda,
                                    'descripcion_generada' => substr($descripcionDetallada, 0, 100) . '...'
                                ]);
                                
                                if ($descripcionDetallada) {
                                    // Si la descripciÃ³n ya existe, agregar el pedido a la lista
                                    if (!isset($descripcionesMap[$descripcionDetallada])) {
                                        $descripcionesMap[$descripcionDetallada] = [];
                                    }
                                    if (!in_array($orden->numero_pedido, $descripcionesMap[$descripcionDetallada])) {
                                        $descripcionesMap[$descripcionDetallada][] = $orden->numero_pedido;
                                    }
                                }
                            }
                        } else {
                            // Fallback a descripciÃ³n simple
                            $descripcion = $orden->getNombresPrendas();
                            if ($descripcion !== '-') {
                                if (!isset($descripcionesMap[$descripcion])) {
                                    $descripcionesMap[$descripcion] = [];
                                }
                                $descripcionesMap[$descripcion][] = $orden->numero_pedido;
                            }
                        }
                    }
                    
                    \Log::info("[FILTRO-DESCRIPCION] Descripciones unicas generadas", ['total_descripciones' => count($descripcionesMap)]);
                    
                    // Convertir a array de opciones
                    $options = array_map(function($desc, $pedidos) {
                        // Limitar descripciÃ³n a 300 caracteres para el display (aumentado de 200)
                        $displayDesc = strlen($desc) > 300 ? substr(strip_tags($desc), 0, 300) . '...' : strip_tags($desc);
                        return [
                            'value' => implode(',', $pedidos), // Guardar todos los pedidos con esa descripciÃ³n
                            'display' => $displayDesc
                        ];
                    }, array_keys($descripcionesMap), array_values($descripcionesMap));
                    
                    \Log::info("[FILTRO-DESCRIPCION] Opciones finales preparadas", [
                        'total_opciones' => count($options),
                        'primera_opcion' => isset($options[0]) ? ['display' => substr($options[0]['display'], 0, 100), 'value' => $options[0]['value']] : 'N/A'
                    ]);
                    break;
                case 'asesor':
                    $options = PedidoProduccion::with('asesora')->get()->pluck('asesora.name')->filter()->unique()->sort()->values()->toArray();
                    break;
                case 'forma_pago':
                    $options = PedidoProduccion::distinct()->pluck('forma_de_pago')->filter()->sort()->values()->toArray();
                    break;
                case 'encargado':
                    // Obtener encargados unicos del primer proceso de cada orden
                    $options = PedidoProduccion::with(['procesos' => function($q) {
                        $q->orderBy('created_at', 'desc');
                    }])->get()
                        ->map(fn($orden) => $orden->procesos->first()?->encargado)
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values()
                        ->toArray();
                    break;
                case 'fecha_creacion':
                    $options = PedidoProduccion::whereNotNull('fecha_de_creacion_de_orden')
                        ->pluck('fecha_de_creacion_de_orden')
                        ->map(fn($f) => \Carbon\Carbon::parse($f)->format('d/m/Y'))
                        ->unique()
                        ->sort()
                        ->values()
                        ->toArray();
                    break;
                case 'fecha_estimada':
                    $options = PedidoProduccion::whereNotNull('fecha_estimada_de_entrega')
                        ->pluck('fecha_estimada_de_entrega')
                        ->map(fn($f) => \Carbon\Carbon::parse($f)->format('d/m/Y'))
                        ->unique()
                        ->sort()
                        ->values()
                        ->toArray();
                    break;
                case 'cantidad':
                    // Opciones de cantidad basadas en las prendas
                    $options = PedidoProduccion::with(['prendas.tallas'])->get()
                        ->map(fn($o) => $o->cantidad_total)
                        ->filter(fn($v) => $v > 0)
                        ->unique()
                        ->sort()
                        ->values()
                        ->toArray();
                    break;
                case 'total_dias':
                    // Para total_dias, calcular todos los valores y obtener los unicos
                    $currentYear = now()->year;
                    $nextYear = now()->addYear()->year;
                    $festivos = array_merge(
                        FestivosColombiaService::obtenerFestivos($currentYear),
                        FestivosColombiaService::obtenerFestivos($nextYear)
                    );
                    
                    $ordenes = PedidoProduccion::all();
                    
                    // Convertir Eloquent Collection a array manteniendo estructura
                    $ordenesArray = [];
                    foreach ($ordenes as $orden) {
                        $ordenesArray[] = $orden->toArray();
                    }
                    
                    \Log::info("Total ordenes para filtro: " . count($ordenesArray));
                    
                    // Usar batch calculation para obtener Dias de forma eficiente
                    $diasCalculados = CacheCalculosService::getTotalDiasBatch($ordenesArray, $festivos);
                    
                    \Log::info("Dias calculados: " . json_encode(array_slice($diasCalculados, 0, 10)));
                    
                    // Obtener valores unicos
                    $diasUnicos = [];
                    foreach ($diasCalculados as $dias) {
                        if ($dias >= 0) {  // Solo incluir valores validos
                            $diasUnicos[$dias] = $dias;
                        }
                    }
                    
                    \Log::info("Dias unicos para filtro: " . json_encode($diasUnicos));
                    
                    // Ordenar por numero
                    ksort($diasUnicos);
                    $options = array_values($diasUnicos);
                    break;
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Columna no valida'
                    ], 400);
            }

            // Filtrar por busqueda si existe
            if (!empty($search)) {
                $options = array_filter($options, function($item) use ($search) {
                    $text = is_array($item) ? $item['display'] : $item;
                    return stripos($text, $search) !== false;
                });
                $options = array_values($options); // Reindexar array
            }

            $total = count($options);
            
            // Aplicar paginaciÃ³n
            $offset = ($page - 1) * $limit;
            $paginatedOptions = array_slice($options, $offset, $limit);

            return response()->json([
                'success' => true,
                'column' => $column,
                'options' => $paginatedOptions,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener opciones de columna: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener opciones de columna'
            ], 500);
        }
    }

    /**
     * Filtrar ordenes por criterios especificos
     * POST /registros/filter-orders
     */
    public function filterOrders(Request $request)
    {
        try {
            $filters = $request->input('filters', []);
            $page = $request->input('page', 1);
            $perPage = 25;  // Sincronizado con la paginaciÃ³n inicial en RegistroOrdenQueryController

            $query = PedidoProduccion::query();

            // Aplicar filtros
            if (!empty($filters)) {
                foreach ($filters as $column => $values) {
                    if (empty($values)) continue;

                    switch ($column) {
                        case 'estado':
                            $query->whereIn('estado', $values);
                            break;
                        case 'area':
                            $query->whereIn('area', $values);
                            break;
                        case 'dia_entrega':
                            // Convertir "X Dias" a numero
                            $dias = array_map(function($v) {
                                return (int) str_replace(' Dias', '', $v);
                            }, $values);
                            $query->whereIn('dia_de_entrega', $dias);
                            break;
                        case 'pedido':
                            $query->whereIn('numero_pedido', $values);
                            break;
                        case 'total_dias':
                            // Filtro especial para total_dias - requiere calculo
                            // Se procesaria despues de obtener todas las ordenes
                            break;
                        case 'descripcion':
                            // Filtrar por descripciones (que pueden contener multiples pedidos)
                            // Los valores vienen como "14342,14328,14329"
                            $allPedidos = [];
                            foreach ($values as $value) {
                                $pedidos = explode(',', $value);
                                $allPedidos = array_merge($allPedidos, $pedidos);
                            }
                            $query->whereIn('numero_pedido', $allPedidos)
                                  ->whereNotNull('numero_pedido')
                                  ->where('numero_pedido', '>', 0);
                            // Excluir solo estados completamente finalizados
                            $query->whereNotIn('estado', ['DEVUELTO_A_ASESORA']);
                            break;
                        case 'cliente':
                            $query->where(function($q) use ($values) {
                                foreach ($values as $value) {
                                    $q->orWhere('cliente', 'LIKE', '%' . $value . '%');
                                }
                            });
                            break;
                        case 'forma_pago':
                            $query->whereIn('forma_de_pago', $values);
                            break;
                        case 'asesor':
                            $query->whereHas('asesora', function($q) use ($values) {
                                $q->whereIn('name', $values);
                            });
                            break;
                        case 'cantidad':
                            // Se filtra post-query
                            break;
                        case 'novedades':
                            $query->where(function($q) use ($values) {
                                foreach ($values as $value) {
                                    $q->orWhere('novedades', 'LIKE', '%' . $value . '%');
                                }
                            });
                            break;
                        case 'fecha_creacion':
                            $query->where(function($q) use ($values) {
                                foreach ($values as $value) {
                                    // Convertir dd/mm/yyyy a fecha
                                    $parts = explode('/', $value);
                                    if (count($parts) === 3) {
                                        $date = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
                                        $q->orWhereDate('fecha_de_creacion_de_orden', $date);
                                    }
                                }
                            });
                            break;
                        case 'fecha_estimada':
                            $query->where(function($q) use ($values) {
                                foreach ($values as $value) {
                                    $parts = explode('/', $value);
                                    if (count($parts) === 3) {
                                        $date = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
                                        $q->orWhereDate('fecha_estimada_de_entrega', $date);
                                    }
                                }
                            });
                            break;
                        case 'encargado':
                            // Filtrar por encargado - verificar que existe un proceso con ese encargado
                            $query->whereHas('procesos', function($q) use ($values) {
                                $q->whereIn('encargado', $values);
                            });
                            break;
                    }
                }
            } else {
                // Si no hay filtros, mostrar todos los pedidos con numero_pedido valido
                // Excluir solo estados completamente finalizados y pedidos sin numero
                $query->whereNotNull('numero_pedido')
                      ->where('numero_pedido', '>', 0)
                      ->whereNotIn('estado', ['DEVUELTO_A_ASESORA']);
            }

            // ðŸ”’ FILTRADO DE SEGURIDAD: Siempre excluir pedidos sin numero de pedido
            // Esto asegura que nunca se devuelvan pedidos con numero_pedido = null
            $query->whereNotNull('numero_pedido')
                  ->where('numero_pedido', '>', 0);

            // Obtener resultados paginados
            $ordenes = $query
                ->with(['prendas.tallas', 'asesora', 'procesos' => function($q) {
                    $q->orderBy('created_at', 'desc');
                }])
                ->orderBy('created_at', 'asc')
                ->get();

            // Filtrar por total_dias si está especificado
            if (!empty($filters['total_dias'])) {
                $currentYear = now()->year;
                $nextYear = now()->addYear()->year;
                $festivos = array_merge(
                    FestivosColombiaService::obtenerFestivos($currentYear),
                    FestivosColombiaService::obtenerFestivos($nextYear)
                );
                
                $diasFiltro = array_map('intval', $filters['total_dias']);
                
                // Convertir a array para batch calculation
                $ordenesArray = $ordenes->map(function($orden) {
                    return $orden->toArray();
                })->toArray();
                
                $totalDiasCalculados = CacheCalculosService::getTotalDiasBatch($ordenesArray, $festivos);
                
                // Filtrar ordenes que coincidan con los Dias seleccionados
                $ordenes = $ordenes->filter(function($orden) use ($totalDiasCalculados, $diasFiltro) {
                    $totalDias = $totalDiasCalculados[$orden->numero_pedido] ?? 0;
                    return in_array((int)$totalDias, $diasFiltro, true);
                })->values();
            }

            // Aplicar paginaciÃ³n manual
            $perPage = 25;
            $currentPage = $page;
            $total = $ordenes->count();
            $lastPage = ceil($total / $perPage);
            
            $ordenesPaginadas = $ordenes->slice(($currentPage - 1) * $perPage, $perPage)->values();

            // Transformar datos para la vista
            $ordenesData = $ordenesPaginadas->map(function($orden) {
                return [
                    'id' => $orden->id,
                    'numero_pedido' => $orden->numero_pedido,
                    'cliente' => $orden->cliente,
                    'estado' => $orden->estado,
                    'area' => $orden->area,
                    'dia_de_entrega' => $orden->dia_de_entrega,
                    'dias_habiles' => $orden->calcularDiasHabiles(),
                    'descripcion' => $orden->getNombresPrendas(),
                    'cantidad' => $orden->cantidad_total,
                    'novedades' => $orden->novedades,
                    'asesor' => $orden->asesora ? $orden->asesora->name : '-',
                    'forma_de_pago' => $orden->forma_de_pago,
                    'fecha_creacion' => $orden->fecha_de_creacion_de_orden ? $orden->fecha_de_creacion_de_orden->format('d/m/Y') : '-',
                    'fecha_estimada' => $orden->fecha_estimada_de_entrega ? $orden->fecha_estimada_de_entrega->format('d/m/Y') : '-',
                    'encargado' => $orden->procesos?->first()?->encargado ?? '-',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $ordenesData,
                'pagination' => [
                    'current_page' => $currentPage,
                    'total' => $total,
                    'per_page' => $perPage,
                    'last_page' => $lastPage,
                    'from' => ($currentPage - 1) * $perPage + 1,
                    'to' => min($currentPage * $perPage, $total),
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al filtrar ordenes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al filtrar ordenes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ðŸ†• busqueda simple en tiempo real
     * POST /registros/search
     */
    public function searchOrders(Request $request)
    {
        try {
            $search = $request->input('search', '');
            $limit = $request->input('limit', 25);
            $page = $request->input('page', 1);
            $isTableSearch = $request->input('isTableSearch', false);

            if (strlen($search) < 1) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'ordenes' => []
                ]);
            }

            // Buscar por numero de pedido o cliente
            $query = PedidoProduccion::where('numero_pedido', 'LIKE', '%' . $search . '%')
                ->orWhere('cliente', 'LIKE', '%' . $search . '%');

            // Si es busqueda de tabla, retornar todos los campos con paginaciÃ³n
            if ($isTableSearch) {
                // Usar paginaciÃ³n
                $ordenesQuery = $query->select(
                    'id',
                    'numero_pedido',
                    'cliente',
                    'estado',
                    'area',
                    'dia_de_entrega',
                    'fecha_de_creacion_de_orden',
                    'fecha_estimada_de_entrega',
                    'novedades',
                    'forma_de_pago',
                    'asesor_id',
                    'created_at',
                    'updated_at'
                )->with(['prendas.tallas', 'asesora']);

                // Obtener total antes de paginar
                $total = $ordenesQuery->count();

                // Paginar
                $ordenes = $ordenesQuery->paginate($limit, ['*'], 'page', $page);

                // Mapear datos para incluir total_dias calculado y encargado
                $ordenesMapeadas = $ordenes->getCollection()->map(function($orden) {
                    // Obtener encargado de la orden (último proceso)
                    $encargado = DB::table('procesos_prenda')
                        ->where('numero_pedido', $orden->numero_pedido)
                        ->orderBy('created_at', 'desc')
                        ->value('encargado');

                    return [
                        'id' => $orden->id,
                        'numero_pedido' => $orden->numero_pedido,
                        'cliente' => $orden->cliente,
                        'estado' => $orden->estado,
                        'area' => $orden->area,
                        'dia_de_entrega' => $orden->dia_de_entrega,
                        'fecha_de_creacion_de_orden' => $orden->fecha_de_creacion_de_orden,
                        'fecha_estimada_de_entrega' => $orden->fecha_estimada_de_entrega,
                        'novedades' => $orden->novedades,
                        'forma_de_pago' => $orden->forma_de_pago,
                        'asesor' => $orden->asesora?->name ?? '-',
                        'created_at' => $orden->created_at,
                        'updated_at' => $orden->updated_at,
                        'prendas' => $orden->prendas,
                        'total_dias_calculado' => $orden->calcularDiasHabiles(),
                        'encargado' => $encargado
                    ];
                });

                // Reemplazar la colecciÃ³n con los datos mapeados
                $ordenes->setCollection($ordenesMapeadas);

                return response()->json([
                    'success' => true,
                    'ordenes' => $ordenes->items(),
                    'data' => $ordenes->items(),
                    'pagination' => [
                        'current_page' => $ordenes->currentPage(),
                        'last_page' => $ordenes->lastPage(),
                        'per_page' => $ordenes->perPage(),
                        'total' => $ordenes->total(),
                        'from' => $ordenes->firstItem(),
                        'to' => $ordenes->lastItem()
                    ]
                ]);
            }

            // Si es busqueda de dropdown, retornar solo lo necesario
            $ordenes = $query->select('id', 'numero_pedido', 'cliente', 'estado', 'area')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $ordenes
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en busqueda de ordenes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error en busqueda: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener imagenes de una orden (DEPRECATED - Usar RegistroOrdenQueryController)
     */

    /**
     * Actualizar novedades de una orden
     * POST /api/ordenes/{numero_pedido}/novedades
     */
    public function updateNovedades(Request $request, $numeroPedido)
    {
        try {
            \Log::info(' updateNovedades iniciado', ['numeroPedido' => $numeroPedido]);
            
            // Validar entrada
            $request->validate([
                'novedades' => 'nullable|string|max:5000'
            ]);

            \Log::info(' ValidaciÃ³n exitosa');

            // Buscar la orden
            $orden = PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();
            
            \Log::info(' Orden encontrada', ['orden_id' => $orden->id]);

            // Actualizar novedades (reemplazo total)
            $orden->update([
                'novedades' => $request->input('novedades', '')
            ]);
            
            \Log::info(' Novedades actualizadas', ['novedades' => $request->input('novedades', '')]);

            // Registrar en auditoria si existe
            if (class_exists('App\Models\AuditLog')) {
                \App\Models\AuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'update_novedades',
                    'auditable_type' => PedidoProduccion::class,
                    'auditable_id' => $orden->id,
                    'changes' => [
                        'novedades' => $request->input('novedades', '')
                    ]
                ]);
            }

            // Broadcast actualizacion en tiempo real
            broadcast(new \App\Events\OrdenUpdated($orden->fresh(), 'updated', ['novedades']));
            \Log::info('ðŸ“¡ Evento de broadcast enviado para novedades');

            return response()->json([
                'success' => true,
                'message' => 'Novedades actualizadas correctamente',
                'data' => [
                    'numero_pedido' => $orden->numero_pedido,
                    'novedades' => $orden->novedades
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error(' Orden no encontrada', ['numeroPedido' => $numeroPedido]);
            return response()->json([
                'success' => false,
                'message' => 'Orden no encontrada'
            ], 404);
        } catch (\Exception $e) {
            \Log::error(' Error al actualizar novedades: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar las novedades: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Agrega una nueva novedad al final del campo (con usuario, fecha y hora)
     * Endpoint: POST /api/ordenes/{numero_pedido}/novedades/add
     */
    public function addNovedad(Request $request, $numeroPedido)
    {
        try {
            \Log::info(' addNovedad iniciado', ['numeroPedido' => $numeroPedido]);
            
            // Validar entrada
            $request->validate([
                'novedad' => 'required|string|max:500'
            ]);

            // Buscar la orden
            $orden = PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();
            
            // Obtener usuario autenticado
            $usuario = auth()->user()->name ?? auth()->user()->email ?? 'Usuario';
            
            // Obtener fecha y hora actual en formato d-m-Y h:i:s A (hora normal con AM/PM)
            $fechaHora = \Carbon\Carbon::now()->format('d-m-Y h:i:s A');
            
            // Crear la novedad con formato [usuario - fecha hora] novedad
            $novedadFormato = "[{$usuario} - {$fechaHora}] " . $request->input('novedad');
            
            // Obtener novedades actuales
            $novedadesActuales = $orden->novedades ?? '';
            
            // Concatenar con salto de linea si hay novedades anteriores
            if (!empty($novedadesActuales)) {
                $novedadesNuevas = $novedadesActuales . "\n\n" . $novedadFormato;
            } else {
                $novedadesNuevas = $novedadFormato;
            }
            
            // Actualizar novedades
            $orden->update([
                'novedades' => $novedadesNuevas
            ]);
            
            \Log::info(' Novedad agregada', [
                'usuario' => $usuario,
                'fecha_hora' => $fechaHora,
                'novedad' => $request->input('novedad')
            ]);

            // Registrar en auditoria si existe
            if (class_exists('App\Models\AuditLog')) {
                \App\Models\AuditLog::create([
                    'user_id' => auth()->id(),
                    'action' => 'add_novedad',
                    'auditable_type' => PedidoProduccion::class,
                    'auditable_id' => $orden->id,
                    'changes' => [
                        'novedad_agregada' => $novedadFormato
                    ]
                ]);
            }

            // Broadcast actualizacion en tiempo real (sin bloquear si falla)
            try {
                broadcast(new \App\Events\OrdenUpdated($orden->fresh(), 'updated', ['novedades']));
                \Log::info('ðŸ“¡ Evento de broadcast enviado para nueva novedad');
            } catch (\Exception $e) {
                \Log::warning(' Error de broadcast (no critico)', [
                    'error' => $e->getMessage(),
                    'pedido' => $numeroPedido
                ]);
                // Continuar de todas formas, no es un error critico
            }

            return response()->json([
                'success' => true,
                'message' => 'Novedad agregada correctamente',
                'data' => [
                    'numero_pedido' => $orden->numero_pedido,
                    'novedades' => $orden->novedades
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error(' Orden no encontrada', ['numeroPedido' => $numeroPedido]);
            return response()->json([
                'success' => false,
                'message' => 'Orden no encontrada'
            ], 404);
        } catch (\Exception $e) {
            \Log::error(' Error al agregar novedad: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar la novedad: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generar descripciÃ³n detallada de una prenda (formato recibo)
     */
    private function generarDescripcionPrenda($prenda, $indexPrenda = 1)
    {
        try {
            $lineas = [];
            $nombrePrenda = $prenda->nombre_prenda ?? $prenda->nombre ?? 'SIN NOMBRE';
            $lineas[] = "PRENDA {$indexPrenda}: {$nombrePrenda}";
            
            // Obtener color y tela de la primera variante (color/tela combinaciÃ³n)
            if ($prenda->coloresTelas && $prenda->coloresTelas->count() > 0) {
                $primerColorTela = $prenda->coloresTelas->first();
                $tela = $primerColorTela && $primerColorTela->tela ? $primerColorTela->tela->nombre ?? $primerColorTela->tela : '-';
                $color = $primerColorTela && $primerColorTela->color ? $primerColorTela->color->nombre ?? $primerColorTela->color : '-';
                $ref = $primerColorTela && $primerColorTela->tela ? $primerColorTela->tela->referencia ?? '' : '';
                
                $lineas[] = "TELA: {$tela} / COLOR: {$color}" . ($ref ? " (REF: {$ref})" : '');
            }
            
            // Manga
            if ($prenda->variantes && $prenda->variantes->count() > 0) {
                $primerVariante = $prenda->variantes->first();
                if ($primerVariante && $primerVariante->manga) {
                    $manga = strtoupper($primerVariante->manga);
                    $lineas[] = "MANGA: {$manga}";
                }
            }
            
            // Observaciones de manga
            if ($prenda->variantes && $prenda->variantes->count() > 0) {
                $primerVariante = $prenda->variantes->first();
                if ($primerVariante && $primerVariante->manga_obs) {
                    $lineas[] = "OBS. MANGA: {$primerVariante->manga_obs}";
                }
            }
            
            // Bolsillos
            if ($prenda->variantes && $prenda->variantes->count() > 0) {
                $primerVariante = $prenda->variantes->first();
                if ($primerVariante && $primerVariante->bolsillos_obs) {
                    $lineas[] = "BOLSILLOS: {$primerVariante->bolsillos_obs}";
                }
            }
            
            // Broche/Boton
            if ($prenda->variantes && $prenda->variantes->count() > 0) {
                $primerVariante = $prenda->variantes->first();
                if ($primerVariante && $primerVariante->broche) {
                    $broche = strtoupper($primerVariante->broche);
                    $lineas[] = "BROCHE: {$broche}";
                    if ($primerVariante->broche_obs) {
                        $lineas[] = "OBS. BROCHE: {$primerVariante->broche_obs}";
                    }
                }
            }
            
            // Tallas (incluyendo tallas por color)
            $tallasSummary = [];
            
            // Primero, verificar si hay tallas por color
            $tallasPorColor = \DB::table('prenda_pedido_talla_colores')
                ->join('prenda_pedido_tallas', 'prenda_pedido_talla_colores.prenda_pedido_talla_id', '=', 'prenda_pedido_tallas.id')
                ->where('prenda_pedido_tallas.prenda_pedido_id', $prenda->id)
                ->select([
                    'prenda_pedido_tallas.talla',
                    'prenda_pedido_talla_colores.color_nombre',
                    'prenda_pedido_talla_colores.cantidad'
                ])
                ->get();
            
            if ($tallasPorColor->count() > 0) {
                // Hay tallas por color, mostrar en formato TALLA:CANTIDAD-COLOR
                foreach ($tallasPorColor as $tallaColor) {
                    if ($tallaColor->cantidad > 0) {
                        $colorNombre = strtoupper($tallaColor->color_nombre);
                        $tallasSummary[] = "{$tallaColor->talla}:{$tallaColor->cantidad}-{$colorNombre}";
                    }
                }
            } else {
                // No hay tallas por color, usar tallas normales
                if ($prenda->tallas && $prenda->tallas->count() > 0) {
                    foreach ($prenda->tallas as $talla) {
                        $tallaNombre = $talla->talla ?? '-';
                        $cantidad = $talla->cantidad ?? 0;
                        if ($cantidad > 0) {
                            $tallasSummary[] = "{$tallaNombre}: {$cantidad}";
                        }
                    }
                }
            }
            
            if (!empty($tallasSummary)) {
                $lineas[] = "TALLAS: " . implode(", ", $tallasSummary);
            }
            
            $descripcionFinal = implode(" | ", $lineas);
            
            \Log::debug("[GENERAR-DESCRIPCION] DescripciÃ³n generada", [
                'prenda_id' => $prenda->id,
                'prenda_nombre' => $nombrePrenda,
                'lineas_cantidad' => count($lineas),
                'descripcion_longitud' => strlen($descripcionFinal),
                'descripcion_preview' => substr($descripcionFinal, 0, 150)
            ]);
            
            return $descripcionFinal;
        } catch (\Exception $e) {
            \Log::error("[GENERAR-DESCRIPCION] Error generando descripciÃ³n", [
                'error' => $e->getMessage(),
                'prenda_id' => $prenda->id ?? 'unknown'
            ]);
            return null;
        }
    }

    /**
     * Mostrar recibos de costura por numero de recibo
     */
    public function recibosCostura(Request $request)
    {
        try {
            // Obtener todos los tipos de filtros desde la solicitud
            $filtros = [];
            $tiposFiltro = [
                'estado', 'dia_entrega', 'total_dias', 'numero_recibo', 
                'cliente', 'descripcion', 'cantidad', 'novedades', 
                'fecha_creacion', 'fecha_estimada', 'encargado'
            ];
            
            foreach ($tiposFiltro as $tipo) {
                $valor = $request->input($tipo, []);
                if (is_string($valor)) {
                    $valor = json_decode($valor, true) ?? [];
                }
                if (!empty($valor)) {
                    $filtros[$tipo] = $valor;
                }
            }
            
            \Log::info('[recibosCostura] Filtros aplicados', ['filtros' => $filtros]);
            
            // Construir consulta base
            $query = DB::table('consecutivos_recibos_pedidos')
                ->where('tipo_recibo', 'COSTURA')
                ->where('activo', 1);
            
            // Aplicar filtros según el tipo
            $this->aplicarFiltros($query, $filtros);
            
            $recibosCostura = $query->orderBy('consecutivo_actual', 'desc')->get();

            // Obtener festivos para calculo de Dias
            $currentYear = now()->year;
            $nextYear = now()->addYear()->year;
            $festivos = array_merge(
                \App\Services\FestivosColombiaService::obtenerFestivos($currentYear),
                \App\Services\FestivosColombiaService::obtenerFestivos($nextYear)
            );

            // Obtener informaciÃ³n adicional de pedidos y prendas
            $recibosConInfo = $recibosCostura->map(function ($recibo) use ($festivos) {
                $pedido = PedidoProduccion::with([
                    'prendas.coloresTelas.tela',
                    'prendas.coloresTelas.color', 
                    'prendas.tallas'
                ])->find($recibo->pedido_produccion_id);

                // Detectar si es anexo (recibo parcial) y resolver created_at real del anexo
                $parcialId = null;
                $notas = isset($recibo->notas) ? (string) $recibo->notas : '';
                if ($notas !== '' && preg_match('/parcial_id:(\d+)/i', $notas, $matches)) {
                    $parcialId = (int) $matches[1];
                }
                $esParcial = $parcialId !== null;

                $createdAt = $recibo->created_at;
                if ($esParcial) {
                    try {
                        $parcial = \DB::table('pedidos_parciales')
                            ->select('created_at')
                            ->where('id', $parcialId)
                            ->whereNull('deleted_at')
                            ->first();
                        if ($parcial && !empty($parcial->created_at)) {
                            $createdAt = $parcial->created_at;
                        }
                    } catch (\Exception $e) {
                        \Log::warning('[recibosCostura] No se pudo obtener created_at de pedidos_parciales', [
                            'recibo_id' => $recibo->id,
                            'pedido_parcial_id' => $parcialId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
                
                // Calcular Dias para este pedido (desde fecha de creaciÃ³n del pedido hasta hoy)
                $diasCalculados = 0;
                $fechaBaseCalculo = null;
                if ($esParcial && $createdAt) {
                    try {
                        $fechaBaseCalculo = \Carbon\Carbon::parse($createdAt);
                    } catch (\Exception $e) {
                        $fechaBaseCalculo = null;
                    }
                }
                if (!$fechaBaseCalculo && $pedido && $pedido->fecha_de_creacion_de_orden) {
                    $fechaBaseCalculo = $pedido->fecha_de_creacion_de_orden;
                }

                if ($fechaBaseCalculo) {
                    try {
                        // Para anexos: calcular desde created_at del anexo. Para recibo base: fecha_de_creacion_de_orden.
                        $fechaInicio = $fechaBaseCalculo;
                        $fechaFin = \Carbon\Carbon::now();
                        
                        // Obtener festivos
                        $festivosArray = \App\Models\Festivo::pluck('fecha')->toArray();
                        $festivosSet = [];
                        foreach ($festivosArray as $f) {
                            try {
                                $festivosSet[\Carbon\Carbon::parse($f)->format('Y-m-d')] = true;
                            } catch (\Exception $e) {}
                        }
                        
                        // Calcular Dias habiles manualmente (misma lÃ³gica que CacheCalculosService)
                        $current = $fechaInicio->copy()->addDay();  // Saltar al prÃ³ximo día
                        $totalDays = 0;
                        $maxIterations = 365;
                        $iterations = 0;
                        
                        while ($current <= $fechaFin && $iterations < $maxIterations) {
                            $dateString = $current->format('Y-m-d');
                            $isWeekend = $current->dayOfWeek === 0 || $current->dayOfWeek === 6;
                            $isFestivo = isset($festivosSet[$dateString]);
                            
                            // Solo contar si es día habil (no es fin de semana ni festivo)
                            if (!$isWeekend && !$isFestivo) {
                                $totalDays++;
                            }
                            
                            $current->addDay();
                            $iterations++;
                        }
                        
                        $diasCalculados = max(0, $totalDays);
                        
                        \Log::info('[recibosCostura] Dias calculados para recibo', [
                            'recibo_id' => $recibo->id,
                            'pedido_id' => $pedido->id,
                            'numero_pedido' => $pedido->numero_pedido,
                            'es_parcial' => $esParcial,
                            'pedido_parcial_id' => $parcialId,
                            'fecha_base_calculo' => $fechaInicio instanceof \Carbon\Carbon ? $fechaInicio->format('Y-m-d H:i:s') : (string) $fechaInicio,
                            'dias_calculados' => $diasCalculados
                        ]);
                        
                    } catch (\Exception $e) {
                        \Log::warning('Error calculando Dias para recibo de costura', [
                            'recibo_id' => $recibo->id,
                            'pedido_id' => $pedido->id,
                            'error' => $e->getMessage()
                        ]);
                        $diasCalculados = 0;
                    }
                }
                
                // Obtener el area directamente del recibo (que es actualizado por el Observer)
                $area = $recibo->area ?? 'Insumos';
                
                // Obtener informaciÃ³n detallada de la prenda especifica del recibo
                $descripcionDetallada = '';
                if ($pedido && $recibo->prenda_id) {
                    // Buscar la prenda especifica del recibo
                    $prendaRecibo = $pedido->prendas->where('id', $recibo->prenda_id)->first();
                    if ($prendaRecibo) {
                        $prendaInfo = "PRENDA: " . ($prendaRecibo->nombre_prenda ?? 'Sin nombre');
                        
                        // Agregar informaciÃ³n de telas y colores
                        if ($prendaRecibo->coloresTelas && $prendaRecibo->coloresTelas->count() > 0) {
                            $telasInfo = [];
                            foreach ($prendaRecibo->coloresTelas as $colorTela) {
                                $telaNombre = $colorTela->tela ? $colorTela->tela->nombre : 'Sin tela';
                                $colorNombre = $colorTela->color ? $colorTela->color->nombre : 'Sin color';
                                $referencia = $colorTela->referencia ?? '';
                                $telasInfo[] = "TELA: {$telaNombre} / COLOR: {$colorNombre}" . ($referencia ? " (REF: {$referencia})" : '');
                            }
                            if (!empty($telasInfo)) {
                                $prendaInfo .= " | " . implode(' | ', $telasInfo);
                            }
                        }
                        
                        // Agregar informaciÃ³n de tallas
                        if ($prendaRecibo->tallas && $prendaRecibo->tallas->count() > 0) {
                            $tallasInfo = [];
                            foreach ($prendaRecibo->tallas as $talla) {
                                $cantidad = $talla->cantidad ?? 0;
                                if ($cantidad > 0) {
                                    $tallasInfo[] = $talla->talla . ": " . $cantidad;
                                }
                            }
                            if (!empty($tallasInfo)) {
                                $prendaInfo .= " | TALLAS: " . implode(', ', $tallasInfo);
                            }
                        }
                        
                        $descripcionDetallada = $prendaInfo;
                    }
                }

                return [
                    'id' => $recibo->id,
                    'consecutivo_actual' => $recibo->consecutivo_actual,
                    'pedido_produccion_id' => $recibo->pedido_produccion_id,
                    'prenda_id' => $recibo->prenda_id,
                    'tipo_recibo' => $recibo->tipo_recibo,
                    'notas' => $recibo->notas,
                    'estado' => $recibo->estado ?? 'PENDIENTE_INSUMOS',
                    'area' => $recibo->area ?? 'Insumos',
                    'created_at' => $createdAt,
                    'updated_at' => $recibo->updated_at,
                    'dias_calculados' => $diasCalculados,
                    'descripcion_detallada' => $descripcionDetallada, // Nuevo campo para filtro
                    'es_parcial' => $esParcial,
                    'pedido_parcial_id' => $parcialId,
                    'pedido_info' => $pedido ? [
                        'numero_pedido' => $pedido->numero_pedido,
                        'cliente' => $pedido->cliente,
                        'estado' => $pedido->estado,
                        'area' => $area,
                        'dia_de_entrega' => $pedido->dia_de_entrega,
                        'fecha_estimada_de_entrega' => $pedido->fecha_estimada_de_entrega ? $pedido->fecha_estimada_de_entrega->format('d/m/Y') : null,
                        'fecha_creacion_orden' => $pedido->fecha_de_creacion_de_orden ? $pedido->fecha_de_creacion_de_orden->format('Y-m-d H:i:s') : null,
                    ] : null,
                ];
            });

            // Calcular cantidad total para cada recibo y suma global
            $totalCantidadGlobal = 0;
            $recibosConCantidad = $recibosConInfo->map(function ($recibo) use (&$totalCantidadGlobal) {
                $cantidadTotal = 0;
                
                // Obtener la prenda especifica del recibo
                if ($recibo['pedido_produccion_id'] && $recibo['prenda_id']) {
                    try {
                        $pedido = PedidoProduccion::find($recibo['pedido_produccion_id']);
                        if ($pedido && $pedido->prendas) {
                            $prendaRecibo = $pedido->prendas->where('id', $recibo['prenda_id'])->first();
                            if ($prendaRecibo && $prendaRecibo->tallas) {
                                // Sumar cantidades usando el Metodo del modelo
                                foreach ($prendaRecibo->tallas as $talla) {
                                    $cantidadTotal += $talla->obtenerCantidadTotal();
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Error calculando cantidad para recibo', [
                            'recibo_id' => $recibo['id'],
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                // Agregar la cantidad calculada al recibo
                $recibo['cantidad_total'] = $cantidadTotal;
                $totalCantidadGlobal += $cantidadTotal;
                
                return $recibo;
            });

            // Si es una solicitud AJAX, retornar JSON con HTML de la tabla
            if ($request->ajax() || $request->wantsJson()) {
                // Renderizar el HTML del tbody
                $htmlTbody = view('components.recibos.recibos-costura-table-tbody', [
                    'recibos' => $recibosConCantidad,
                    'totalCantidadGlobal' => $totalCantidadGlobal
                ])->render();
                
                return response()->json([
                    'success' => true,
                    'recibos' => [
                        'html' => $htmlTbody,
                        'data' => $recibosConCantidad
                    ],
                    'total' => $recibosConCantidad->count(),
                    'total_cantidad' => $totalCantidadGlobal,
                    'filtros_aplicados' => $filtros
                ]);
            }

            return view('registros.recibos-costura', [
                'recibos' => $recibosConCantidad,
                'totalCantidadGlobal' => $totalCantidadGlobal,
                'title' => 'Recibos de Costura'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en recibosCostura: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al cargar los recibos de costura'
                ], 500);
            }
            
            return back()->with('error', 'Error al cargar los recibos de costura');
        }
    }
    
    /**
     * Aplicar filtros a la consulta según el tipo
     */
    private function aplicarFiltros($query, $filtros)
    {
        // Filtro por estado
        if (isset($filtros['estado']) && !empty($filtros['estado'])) {
            $query->whereIn('estado', $filtros['estado']);
        } elseif (empty($filtros)) {
            // Por defecto, excluir pendientes de insumos solo si no hay filtros
            $query->where('estado', '!=', 'PENDIENTE_INSUMOS');
        }
        
        // Filtro por numero de recibo
        if (isset($filtros['numero_recibo']) && !empty($filtros['numero_recibo'])) {
            $query->where(function($q) use ($filtros) {
                foreach ($filtros['numero_recibo'] as $numero) {
                    $q->orWhere('consecutivo_actual', 'LIKE', '%' . $numero . '%');
                }
            });
        }
        
        // Filtro por total de Dias (rango) - se aplicaria despues del calculo
        // Guardamos para procesamiento posterior
        if (isset($filtros['total_dias']) && count($filtros['total_dias']) >= 2) {
            \Log::info('[recibosCostura] Filtro por total_Dias se aplicaria en procesamiento posterior');
        }
        
        // Filtros que requieren JOIN con pedidos - usamos subconsultas
        if (isset($filtros['cliente']) && !empty($filtros['cliente'])) {
            $query->where(function($q) use ($filtros) {
                foreach ($filtros['cliente'] as $cliente) {
                    $q->orWhere('pedido_produccion_id', 'IN', function($subQuery) use ($cliente) {
                        $subQuery->select('id')
                               ->from('pedidos_produccion')
                               ->where('cliente', 'LIKE', '%' . $cliente . '%');
                    });
                }
            });
        }
        
        if (isset($filtros['dia_entrega']) && !empty($filtros['dia_entrega'])) {
            $query->whereIn('pedido_produccion_id', function($subQuery) use ($filtros) {
                $subQuery->select('id')
                       ->from('pedidos_produccion')
                       ->whereIn('dia_de_entrega', $filtros['dia_entrega']);
            });
        }
        
        if (isset($filtros['fecha_creacion']) && count($filtros['fecha_creacion']) >= 2) {
            $query->whereIn('pedido_produccion_id', function($subQuery) use ($filtros) {
                $subQuery->select('id')
                       ->from('pedidos_produccion')
                       ->whereBetween('fecha_de_creacion_de_orden', [
                           \Carbon\Carbon::parse($filtros['fecha_creacion'][0])->startOfDay(),
                           \Carbon\Carbon::parse($filtros['fecha_creacion'][1])->endOfDay()
                       ]);
            });
        }
        
        if (isset($filtros['fecha_estimada']) && count($filtros['fecha_estimada']) >= 2) {
            $query->whereIn('pedido_produccion_id', function($subQuery) use ($filtros) {
                $subQuery->select('id')
                       ->from('pedidos_produccion')
                       ->whereBetween('fecha_estimada_de_entrega', [
                           \Carbon\Carbon::parse($filtros['fecha_estimada'][0])->startOfDay(),
                           \Carbon\Carbon::parse($filtros['fecha_estimada'][1])->endOfDay()
                       ]);
            });
        }
        
        // Filtros por descripciÃ³n y cantidad (requieren procesamiento adicional)
        if (isset($filtros['descripcion']) && !empty($filtros['descripcion'])) {
            \Log::info('[recibosCostura] Filtro por descripciÃ³n requiere procesamiento adicional');
        }
        
        if (isset($filtros['cantidad']) && count($filtros['cantidad']) >= 2) {
            \Log::info('[recibosCostura] Filtro por cantidad requiere procesamiento adicional');
        }
        
        if (isset($filtros['novedades']) && !empty($filtros['novedades'])) {
            \Log::info('[recibosCostura] Filtro por novedades requiere procesamiento adicional');
        }
        
        if (isset($filtros['encargado']) && !empty($filtros['encargado'])) {
            \Log::info('[recibosCostura] Filtro por encargado requiere procesamiento adicional');
        }
    }

    /**
     * Mostrar recibos de reflectivo aprobados
     */
    public function recibosReflectivo(Request $request)
    {
        try {
            // Obtener todos los tipos de filtros desde la solicitud
            $filtros = [];
            $tiposFiltro = [
                'estado', 'dia_entrega', 'total_dias', 'numero_recibo', 
                'cliente', 'descripcion', 'cantidad', 'novedades', 
                'fecha_creacion', 'fecha_estimada', 'encargado'
            ];
            
            foreach ($tiposFiltro as $tipo) {
                $valor = $request->input($tipo, []);
                if (is_string($valor)) {
                    $valor = json_decode($valor, true) ?? [];
                }
                if (!empty($valor)) {
                    $filtros[$tipo] = $valor;
                }
            }
            
            \Log::info('[recibosReflectivo] Filtros aplicados', ['filtros' => $filtros]);
            
            // Obtener IDs de prendas con proceso REFLECTIVO (tipo_proceso_id=1) APROBADO
            $prendasAprobadas = DB::table('pedidos_procesos_prenda_detalles')
                ->where('tipo_proceso_id', 1)
                ->where('estado', 'APROBADO')
                ->whereNull('deleted_at')
                ->pluck('prenda_pedido_id')
                ->unique()
                ->values()
                ->toArray();
            
            \Log::info('[recibosReflectivo] Prendas con proceso REFLECTIVO aprobado', [
                'total_prendas_aprobadas' => count($prendasAprobadas),
                'prenda_ids' => $prendasAprobadas
            ]);
            
            // Construir consulta base - solo REFLECTIVO activos con proceso APROBADO
            // Se muestran independientemente del area o estado del recibo
            $query = DB::table('consecutivos_recibos_pedidos')
                ->where('tipo_recibo', 'REFLECTIVO')
                ->where('activo', 1)
                ->whereIn('prenda_id', $prendasAprobadas);
            
            // Aplicar solo filtros explicitos del usuario (NO el filtro por defecto de estado)
            if (isset($filtros['estado']) && !empty($filtros['estado'])) {
                $query->whereIn('estado', $filtros['estado']);
            }
            if (isset($filtros['numero_recibo']) && !empty($filtros['numero_recibo'])) {
                $query->where(function($q) use ($filtros) {
                    foreach ($filtros['numero_recibo'] as $numero) {
                        $q->orWhere('consecutivo_actual', 'LIKE', '%' . $numero . '%');
                    }
                });
            }
            if (isset($filtros['cliente']) && !empty($filtros['cliente'])) {
                $query->where(function($q) use ($filtros) {
                    foreach ($filtros['cliente'] as $cliente) {
                        $q->orWhereIn('pedido_produccion_id', function($subQuery) use ($cliente) {
                            $subQuery->select('id')
                                   ->from('pedidos_produccion')
                                   ->where('cliente', 'LIKE', '%' . $cliente . '%');
                        });
                    }
                });
            }
            if (isset($filtros['dia_entrega']) && !empty($filtros['dia_entrega'])) {
                $query->whereIn('pedido_produccion_id', function($subQuery) use ($filtros) {
                    $subQuery->select('id')
                           ->from('pedidos_produccion')
                           ->whereIn('dia_de_entrega', $filtros['dia_entrega']);
                });
            }
            
            $recibosReflectivo = $query->orderBy('consecutivo_actual', 'desc')->get();
            
            \Log::info('[recibosReflectivo] Recibos encontrados', [
                'total' => $recibosReflectivo->count()
            ]);

            // Obtener festivos para calculo de Dias
            $currentYear = now()->year;
            $nextYear = now()->addYear()->year;
            $festivos = array_merge(
                \App\Services\FestivosColombiaService::obtenerFestivos($currentYear),
                \App\Services\FestivosColombiaService::obtenerFestivos($nextYear)
            );

            // Obtener informaciÃ³n adicional de pedidos y prendas
            $recibosConInfo = $recibosReflectivo->map(function ($recibo) use ($festivos) {
                $pedido = PedidoProduccion::with([
                    'prendas.coloresTelas.tela',
                    'prendas.coloresTelas.color', 
                    'prendas.tallas'
                ])->find($recibo->pedido_produccion_id);
                
                // Calcular Dias para este pedido
                $diasCalculados = 0;
                if ($pedido && $pedido->fecha_de_creacion_de_orden) {
                    try {
                        $fechaInicio = $pedido->fecha_de_creacion_de_orden;
                        $fechaFin = \Carbon\Carbon::now();
                        
                        $festivosArray = \App\Models\Festivo::pluck('fecha')->toArray();
                        $festivosSet = [];
                        foreach ($festivosArray as $f) {
                            try {
                                $festivosSet[\Carbon\Carbon::parse($f)->format('Y-m-d')] = true;
                            } catch (\Exception $e) {}
                        }
                        
                        $current = $fechaInicio->copy()->addDay();
                        $totalDays = 0;
                        $maxIterations = 365;
                        $iterations = 0;
                        
                        while ($current <= $fechaFin && $iterations < $maxIterations) {
                            $dateString = $current->format('Y-m-d');
                            $isWeekend = $current->dayOfWeek === 0 || $current->dayOfWeek === 6;
                            $isFestivo = isset($festivosSet[$dateString]);
                            
                            if (!$isWeekend && !$isFestivo) {
                                $totalDays++;
                            }
                            
                            $current->addDay();
                            $iterations++;
                        }
                        
                        $diasCalculados = max(0, $totalDays);
                        
                    } catch (\Exception $e) {
                        \Log::warning('Error calculando Dias para recibo de reflectivo', [
                            'recibo_id' => $recibo->id,
                            'pedido_id' => $pedido->id ?? null,
                            'error' => $e->getMessage()
                        ]);
                        $diasCalculados = 0;
                    }
                }
                
                $area = $recibo->area ?? 'Insumos';
                
                // Obtener informaciÃ³n detallada de la prenda especifica del recibo
                $descripcionDetallada = '';
                if ($pedido && $recibo->prenda_id) {
                    $prendaRecibo = $pedido->prendas->where('id', $recibo->prenda_id)->first();
                    if ($prendaRecibo) {
                        $prendaInfo = "PRENDA: " . ($prendaRecibo->nombre_prenda ?? 'Sin nombre');
                        
                        if ($prendaRecibo->coloresTelas && $prendaRecibo->coloresTelas->count() > 0) {
                            $telasInfo = [];
                            foreach ($prendaRecibo->coloresTelas as $colorTela) {
                                $telaNombre = $colorTela->tela ? $colorTela->tela->nombre : 'Sin tela';
                                $colorNombre = $colorTela->color ? $colorTela->color->nombre : 'Sin color';
                                $referencia = $colorTela->referencia ?? '';
                                $telasInfo[] = "TELA: {$telaNombre} / COLOR: {$colorNombre}" . ($referencia ? " (REF: {$referencia})" : '');
                            }
                            if (!empty($telasInfo)) {
                                $prendaInfo .= " | " . implode(' | ', $telasInfo);
                            }
                        }
                        
                        if ($prendaRecibo->tallas && $prendaRecibo->tallas->count() > 0) {
                            $tallasInfo = [];
                            foreach ($prendaRecibo->tallas as $talla) {
                                $cantidad = $talla->cantidad ?? 0;
                                if ($cantidad > 0) {
                                    $tallasInfo[] = $talla->talla . ": " . $cantidad;
                                }
                            }
                            if (!empty($tallasInfo)) {
                                $prendaInfo .= " | TALLAS: " . implode(', ', $tallasInfo);
                            }
                        }
                        
                        $descripcionDetallada = $prendaInfo;
                    }
                }
                
                return [
                    'id' => $recibo->id,
                    'consecutivo_actual' => $recibo->consecutivo_actual,
                    'pedido_produccion_id' => $recibo->pedido_produccion_id,
                    'prenda_id' => $recibo->prenda_id,
                    'tipo_recibo' => $recibo->tipo_recibo,
                    'notas' => $recibo->notas,
                    'estado' => $recibo->estado ?? 'PENDIENTE_INSUMOS',
                    'area' => $recibo->area ?? 'Insumos',
                    'created_at' => $recibo->created_at,
                    'updated_at' => $recibo->updated_at,
                    'dias_calculados' => $diasCalculados,
                    'descripcion_detallada' => $descripcionDetallada,
                    'pedido_info' => $pedido ? [
                        'numero_pedido' => $pedido->numero_pedido,
                        'cliente' => $pedido->cliente,
                        'estado' => $pedido->estado,
                        'area' => $area,
                        'dia_de_entrega' => $pedido->dia_de_entrega,
                        'fecha_estimada_de_entrega' => $pedido->fecha_estimada_de_entrega ? $pedido->fecha_estimada_de_entrega->format('d/m/Y') : null,
                        'fecha_creacion_orden' => $pedido->fecha_de_creacion_de_orden ? $pedido->fecha_de_creacion_de_orden->format('Y-m-d H:i:s') : null,
                    ] : null,
                ];
            });

            // Calcular cantidad total para cada recibo y suma global
            $totalCantidadGlobal = 0;
            $recibosConCantidad = $recibosConInfo->map(function ($recibo) use (&$totalCantidadGlobal) {
                $cantidadTotal = 0;
                
                // Obtener la prenda especifica del recibo
                if ($recibo['pedido_produccion_id'] && $recibo['prenda_id']) {
                    try {
                        $pedido = PedidoProduccion::find($recibo['pedido_produccion_id']);
                        if ($pedido && $pedido->prendas) {
                            $prendaRecibo = $pedido->prendas->where('id', $recibo['prenda_id'])->first();
                            if ($prendaRecibo && $prendaRecibo->tallas) {
                                // Sumar cantidades usando el Metodo del modelo
                                foreach ($prendaRecibo->tallas as $talla) {
                                    $cantidadTotal += $talla->obtenerCantidadTotal();
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Error calculando cantidad para recibo de reflectivo', [
                            'recibo_id' => $recibo['id'],
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                // Agregar la cantidad calculada al recibo
                $recibo['cantidad_total'] = $cantidadTotal;
                $totalCantidadGlobal += $cantidadTotal;
                
                return $recibo;
            });

            // Si es una solicitud AJAX, retornar JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'recibos' => $recibosConCantidad,
                    'total' => $recibosConCantidad->count(),
                    'total_cantidad' => $totalCantidadGlobal,
                    'filtros_aplicados' => $filtros
                ]);
            }

            return view('registros.recibos-reflectivo', [
                'recibos' => $recibosConCantidad,
                'totalCantidadGlobal' => $totalCantidadGlobal,
                'title' => 'Recibos de Reflectivo'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en recibosReflectivo: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al cargar los recibos de reflectivo'
                ], 500);
            }
            
            return back()->with('error', 'Error al cargar los recibos de reflectivo');
        }
    }

    /**
     * Obtener datos de un recibo de reflectivo especifico como JSON
     */
    public function getReciboReflectivoJson($reciboId)
    {
        try {
            $recibo = DB::table('consecutivos_recibos_pedidos')
                ->where('id', $reciboId)
                ->where('tipo_recibo', 'REFLECTIVO')
                ->where('activo', 1)
                ->first();
            
            if (!$recibo) {
                return response()->json(['success' => false, 'message' => 'Recibo no encontrado'], 404);
            }
            
            $pedido = PedidoProduccion::find($recibo->pedido_produccion_id);
            
            $diasCalculados = 0;
            if ($pedido && $pedido->fecha_de_creacion_de_orden) {
                try {
                    $fechaInicio = $pedido->fecha_de_creacion_de_orden;
                    $fechaFin = \Carbon\Carbon::now();
                    $festivosArray = \App\Models\Festivo::pluck('fecha')->toArray();
                    $festivosSet = [];
                    foreach ($festivosArray as $f) {
                        try { $festivosSet[\Carbon\Carbon::parse($f)->format('Y-m-d')] = true; } catch (\Exception $e) {}
                    }
                    $current = $fechaInicio->copy()->addDay();
                    $totalDays = 0;
                    $maxIterations = 365;
                    $iterations = 0;
                    while ($current <= $fechaFin && $iterations < $maxIterations) {
                        $dateString = $current->format('Y-m-d');
                        $isWeekend = $current->dayOfWeek === 0 || $current->dayOfWeek === 6;
                        $isFestivo = isset($festivosSet[$dateString]);
                        if (!$isWeekend && !$isFestivo) { $totalDays++; }
                        $current->addDay();
                        $iterations++;
                    }
                    $diasCalculados = max(0, $totalDays);
                } catch (\Exception $e) {
                    $diasCalculados = 0;
                }
            }
            
            $nombrePrenda = 'Sin prendas';
            if ($pedido && $pedido->prendas && $pedido->prendas->count() > 0) {
                $primeraPrenda = $pedido->prendas->first();
                $nombrePrenda = $primeraPrenda->nombre_prenda ?? $primeraPrenda->nombre ?? 'Prenda';
            }
            
            return response()->json([
                'success' => true,
                'recibo' => [
                    'id' => $recibo->id,
                    'consecutivo_actual' => $recibo->consecutivo_actual,
                    'pedido_produccion_id' => $recibo->pedido_produccion_id,
                    'prenda_id' => $recibo->prenda_id,
                    'tipo_recibo' => $recibo->tipo_recibo,
                    'estado' => $recibo->estado ?? 'PENDIENTE_INSUMOS',
                    'area' => $recibo->area ?? 'Insumos',
                    'dias_calculados' => $diasCalculados,
                    'nombre_prenda' => $nombrePrenda,
                    'cliente' => $pedido ? $pedido->cliente : '',
                    'numero_pedido' => $pedido ? $pedido->numero_pedido : '',
                    'fecha_creacion' => $pedido && $pedido->fecha_de_creacion_de_orden ? $pedido->fecha_de_creacion_de_orden->format('d/m/Y') : '-',
                    'created_at' => $recibo->created_at,
                ],
            ]);
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
            $recibo = DB::table('consecutivos_recibos_pedidos')
                ->where('id', $reciboId)
                ->where('tipo_recibo', 'COSTURA')
                ->where('activo', 1)
                ->first();
            
            if (!$recibo) {
                return response()->json(['success' => false, 'message' => 'Recibo no encontrado'], 404);
            }
            
            $pedido = PedidoProduccion::find($recibo->pedido_produccion_id);
            
            // Calcular Dias
            $diasCalculados = 0;
            if ($pedido && $pedido->fecha_de_creacion_de_orden) {
                try {
                    $fechaInicio = $pedido->fecha_de_creacion_de_orden;
                    $fechaFin = \Carbon\Carbon::now();
                    $festivosArray = \App\Models\Festivo::pluck('fecha')->toArray();
                    $festivosSet = [];
                    foreach ($festivosArray as $f) {
                        try { $festivosSet[\Carbon\Carbon::parse($f)->format('Y-m-d')] = true; } catch (\Exception $e) {}
                    }
                    $current = $fechaInicio->copy()->addDay();
                    $totalDays = 0;
                    $maxIterations = 365;
                    $iterations = 0;
                    while ($current <= $fechaFin && $iterations < $maxIterations) {
                        $dateString = $current->format('Y-m-d');
                        $isWeekend = $current->dayOfWeek === 0 || $current->dayOfWeek === 6;
                        $isFestivo = isset($festivosSet[$dateString]);
                        if (!$isWeekend && !$isFestivo) { $totalDays++; }
                        $current->addDay();
                        $iterations++;
                    }
                    $diasCalculados = max(0, $totalDays);
                } catch (\Exception $e) {
                    $diasCalculados = 0;
                }
            }
            
            // Obtener nombre primera prenda
            $nombrePrenda = 'Sin prendas';
            if ($pedido && $pedido->prendas && $pedido->prendas->count() > 0) {
                $primeraPrenda = $pedido->prendas->first();
                $nombrePrenda = $primeraPrenda->nombre_prenda ?? $primeraPrenda->nombre ?? 'Prenda';
            }
            
            return response()->json([
                'success' => true,
                'recibo' => [
                    'id' => $recibo->id,
                    'consecutivo_actual' => $recibo->consecutivo_actual,
                    'pedido_produccion_id' => $recibo->pedido_produccion_id,
                    'prenda_id' => $recibo->prenda_id,
                    'tipo_recibo' => $recibo->tipo_recibo,
                    'estado' => $recibo->estado ?? 'PENDIENTE_INSUMOS',
                    'area' => $recibo->area ?? 'Insumos',
                    'dias_calculados' => $diasCalculados,
                    'nombre_prenda' => $nombrePrenda,
                    'cliente' => $pedido ? $pedido->cliente : '',
                    'numero_pedido' => $pedido ? $pedido->numero_pedido : '',
                    'fecha_creacion' => $pedido && $pedido->fecha_de_creacion_de_orden ? $pedido->fecha_de_creacion_de_orden->format('d/m/Y') : '-',
                    'created_at' => $recibo->created_at,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en getReciboJson: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno'], 500);
        }
    }
    
    /**
     * Obtener el area del proceso mas reciente de una prenda
     */
    private function obtenerAreaProcesoMasReciente($pedidoProduccionId, $prendaId = null)
    {
        try {
            \Log::info('[obtenerAreaProcesoMasReciente] Buscando proceso mas reciente', [
                'pedido_produccion_id' => $pedidoProduccionId,
                'prenda_id' => $prendaId
            ]);
            
            // Primero obtener el numero_pedido desde la tabla pedidos_produccion
            $pedido = \DB::table('pedidos_produccion')
                ->where('id', $pedidoProduccionId)
                ->first();
            
            if (!$pedido) {
                \Log::warning('[obtenerAreaProcesoMasReciente] Pedido no encontrado', ['pedido_produccion_id' => $pedidoProduccionId]);
                return 'Sin procesos';
            }
            
            $numeroPedido = $pedido->numero_pedido;
            \Log::info('[obtenerAreaProcesoMasReciente] Usando numero_pedido', [
                'pedido_produccion_id' => $pedidoProduccionId,
                'numero_pedido' => $numeroPedido
            ]);
            
            $query = \DB::table('procesos_prenda')
                ->where('numero_pedido', $numeroPedido)
                ->whereNull('deleted_at');  // Excluir procesos eliminados (soft delete)
            
            // Si se especifica prenda_id, filtrar por esa prenda
            if ($prendaId) {
                // Convertir a entero para asegurar comparaciÃ³n correcta
                $prendaId = (int)$prendaId;
                $query->where('prenda_pedido_id', $prendaId);
                \Log::info('[obtenerAreaProcesoMasReciente] Filtrando por prenda_id', ['prenda_id' => $prendaId]);
            } else {
                \Log::info('[obtenerAreaProcesoMasReciente] Buscando todos los procesos del pedido');
            }
            
            // Para debugging: ver todos los procesos disponibles
            $todosLosProcesos = $query->get();
            \Log::info('[obtenerAreaProcesoMasReciente] Todos los procesos encontrados:', [
                'total' => $todosLosProcesos->count(),
                'procesos' => $todosLosProcesos->toArray()
            ]);
            
            // Obtener el proceso mas reciente por created_at
            $procesoReciente = $query->orderBy('created_at', 'desc')
                ->first();
            
            if ($procesoReciente) {
                $area = $procesoReciente->proceso;
                \Log::info('[obtenerAreaProcesoMasReciente] Proceso mas reciente encontrado', [
                    'pedido_produccion_id' => $pedidoProduccionId,
                    'numero_pedido' => $numeroPedido,
                    'prenda_id' => $prendaId,
                    'area' => $area,
                    'proceso_id' => $procesoReciente->id,
                    'created_at' => $procesoReciente->created_at
                ]);
                return $area;
            }
            
            \Log::info('[obtenerAreaProcesoMasReciente] No se encontraron procesos', [
                'pedido_produccion_id' => $pedidoProduccionId,
                'numero_pedido' => $numeroPedido,
                'prenda_id' => $prendaId
            ]);
            
            return 'Sin procesos';
            
        } catch (\Exception $e) {
            \Log::error('[obtenerAreaProcesoMasReciente] Error: ' . $e->getMessage(), [
                'pedido_produccion_id' => $pedidoProduccionId,
                'prenda_id' => $prendaId
            ]);
            return 'Error';
        }
    }
    
    /**
     * Obtener el area mas reciente de un pedido (API)
     */
    public function getAreaReciente($id)
    {
        try {
            \Log::info('[getAreaReciente] Obteniendo area mas reciente para pedido', ['pedido_id' => $id]);
            
            $pedido = PedidoProduccion::find($id);
            
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'error' => 'Pedido no encontrado'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'area' => $pedido->area ?? 'Insumos',
                'pedido_id' => $id
            ]);
            
        } catch (\Exception $e) {
            \Log::error('[getAreaReciente] Error: ' . $e->getMessage(), ['pedido_id' => $id]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener area reciente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Contar recibos de COSTURA en ejecuciÃ³n (area Corte) para la campana
     * GET /api/recibos-costura/ejecutando-corte
     */
    public function contarRecibosEjecutandoCostura()
    {
        try {
            $userId = auth()->id();
            
            // Obtener recibos COSTURA en estado "En EjecuciÃ³n" con area "Corte"
            // EXCLUYENDO los que el usuario actual ya marcÃ³ como visto
            $recibos = DB::table('consecutivos_recibos_pedidos')
                ->where('tipo_recibo', 'COSTURA')
                ->where('estado', 'En EjecuciÃ³n')
                ->where('area', 'Corte')
                ->where('activo', 1)
                ->whereNotIn('id', function($query) use ($userId) {
                    $query->select('consecutivo_recibo_id')
                        ->from('recibos_usuario_vistos')
                        ->where('user_id', $userId)
                        ->where('tipo_recibo', 'COSTURA');
                })
                ->select([
                    'id',
                    'consecutivo_actual as numero_recibo',
                    'pedido_produccion_id',
                    'prenda_id',
                    'created_at'
                ])
                ->get();

            // Enriquecer datos con informaciÃ³n del pedido
            $recibosConInfo = $recibos->map(function ($recibo) {
                $pedido = PedidoProduccion::find($recibo->pedido_produccion_id);
                
                return [
                    'id' => $recibo->id,
                    'numero_recibo' => $recibo->numero_recibo,
                    'cliente' => $pedido ? $pedido->cliente : '-',
                    'pedido_id' => $pedido ? $pedido->numero_pedido : '-',
                    'fecha' => Carbon::parse($recibo->created_at)->format('d/m/Y H:i')
                ];
            });

            return response()->json([
                'success' => true,
                'total' => $recibosConInfo->count(),
                'recibos' => $recibosConInfo->values()->toArray()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en contarRecibosEjecutandoCostura: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al contar recibos de costura',
                'total' => 0,
                'recibos' => []
            ], 500);
        }
    }

    /**
     * Marcar un recibo de COSTURA como visto por el usuario actual
     * POST /api/recibos-costura/{id}/marcar-visto-corte
     */
    public function marcarReciboVistoCostura($reciboId)
    {
        try {
            $userId = auth()->id();
            
            // Obtener el recibo
            $recibo = DB::table('consecutivos_recibos_pedidos')
                ->where('id', $reciboId)
                ->where('tipo_recibo', 'COSTURA')
                ->first();
            
            if (!$recibo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recibo no encontrado'
                ], 404);
            }
            
            // Crear o ignorar si ya existe (gracias a unique constraint)
            DB::table('recibos_usuario_vistos')->insertOrIgnore([
                'consecutivo_recibo_id' => $reciboId,
                'user_id' => $userId,
                'tipo_recibo' => 'COSTURA',
                'created_at' => Carbon::now()
            ]);
            
            \Log::info('Recibo de costura marcado como visto', [
                'recibo_id' => $reciboId,
                'user_id' => $userId,
                'numero_recibo' => $recibo->consecutivo_actual
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Recibo marcado como visto',
                'recibo_id' => $reciboId
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error al marcar recibo como visto: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar el recibo como visto'
            ], 500);
        }
    }

    /**
     * Guardar día de entrega y calcular fecha estimada
     * POST /registros/{id}/dia-entrega
     */
    public function saveDiaEntrega(Request $request, $id)
    {
        try {
            // Validar que el ID sea numerico
            if (!is_numeric($id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de orden invalido'
                ], 400);
            }

            $diaDeEntrega = $request->input('dia_de_entrega');
            $calcularFechaEstimada = $request->input('calcular_fecha_estimada', true);

            // Obtener la orden
            $orden = PedidoProduccion::where('numero_pedido', $id)
                ->orWhere('id', $id)
                ->first();

            if (!$orden) {
                return response()->json([
                    'success' => false,
                    'message' => 'Orden no encontrada'
                ], 404);
            }

            // Preparar datos para actualizar
            $updateData = [];
            
            if ($diaDeEntrega !== null) {
                // Validar que sea un numero entre 1 y 35
                $diaDeEntrega = intval($diaDeEntrega);
                if ($diaDeEntrega < 1 || $diaDeEntrega > 35) {
                    return response()->json([
                        'success' => false,
                        'message' => 'día de entrega invalido. Debe ser entre 1 y 35'
                    ], 400);
                }
                $updateData['dia_de_entrega'] = $diaDeEntrega;
            } else {
                // Si es null, establecer como null
                $updateData['dia_de_entrega'] = null;
            }

            // Calcular fecha estimada si se solicita y hay un día valido
            if ($calcularFechaEstimada && $diaDeEntrega && $diaDeEntrega > 0) {
                // Obtener la fecha de creaciÃ³n del recibo
                $fechaInicio = $orden->fecha_de_creacion_de_orden;
                
                if (!$fechaInicio) {
                    $fechaInicio = $orden->created_at;
                }

                if ($fechaInicio) {
                    // Calcular fecha estimada sumando Dias habiles
                    $fechaEstimada = $this->calcularFechaEstimadaConDiasHabiles(
                        $fechaInicio,
                        $diaDeEntrega
                    );
                    $updateData['fecha_estimada_de_entrega'] = $fechaEstimada;
                }
            } else if (!$diaDeEntrega || $diaDeEntrega == 0) {
                // Si se deselecciona, limpiar la fecha estimada
                $updateData['fecha_estimada_de_entrega'] = null;
            }

            // Actualizar la orden
            $orden->update($updateData);
            $orden->refresh();

            // Log de la actividad
            \Log::info('día de entrega actualizado', [
                'numero_pedido' => $orden->numero_pedido,
                'dia_de_entrega' => $diaDeEntrega,
                'fecha_estimada_de_entrega' => $orden->fecha_estimada_de_entrega,
                'usuario_id' => auth()->id()
            ]);

            // Broadcast el evento
            try {
                broadcast(new \App\Events\OrdenUpdated(
                    $orden,
                    'updated',
                    ['dia_de_entrega', 'fecha_estimada_de_entrega']
                ));
            } catch (\Exception $e) {
                \Log::warning('Error en broadcast: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'día de entrega actualizado correctamente',
                'data' => [
                    'numero_pedido' => $orden->numero_pedido,
                    'dia_de_entrega' => $orden->dia_de_entrega,
                    'fecha_estimada_de_entrega' => $orden->fecha_estimada_de_entrega,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al actualizar día de entrega: ' . $e->getMessage(), [
                'orden_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el día de entrega: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular fecha estimada sumando Dias habiles (excluyendo fines de semana y festivos)
     */
    private function calcularFechaEstimadaConDiasHabiles($fechaInicio, $diasHabiles)
    {
        try {
            $fecha = Carbon::parse($fechaInicio);
            $diasAgregados = 0;

            // Obtener festivos del aano actual y siguiente
            $currentYear = $fecha->year;
            $nextYear = $currentYear + 1;
            $festivos = array_merge(
                FestivosColombiaService::obtenerFestivos($currentYear),
                FestivosColombiaService::obtenerFestivos($nextYear)
            );

            // Convertir festivos a formato YYYY-MM-DD para comparaciÃ³n facil
            $festivosFormatted = array_map(function ($fechaFestivo) {
                return Carbon::parse($fechaFestivo)->format('Y-m-d');
            }, $festivos);

            // Sumar Dias habiles
            while ($diasAgregados < $diasHabiles) {
                $fecha->addDay();

                // Verificar si es fin de semana (sabado=6, domingo=0)
                $diaSemana = $fecha->dayOfWeek;
                $esFinde = ($diaSemana === 0 || $diaSemana === 6);

                // Verificar si es festivo
                $esFestivo = in_array($fecha->format('Y-m-d'), $festivosFormatted);

                // Si no es fin de semana ni festivo, contar como día habil
                if (!$esFinde && !$esFestivo) {
                    $diasAgregados++;
                }
            }

            return $fecha;

        } catch (\Exception $e) {
            \Log::error('Error calculando fecha estimada: ' . $e->getMessage());
            // Fallback: sumar Dias simples sin considerar festivos
            return Carbon::parse($fechaInicio)->addDays($diasHabiles);
        }
    }

}
