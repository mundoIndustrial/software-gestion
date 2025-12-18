<?php

namespace App\Http\Controllers;

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

            // Verificar número consecutivo
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

            // Ejecutar actualización delegada al servicio
            $response = $this->updateService->updateOrder($orden, $validatedData);

            // 🆕 Obtener la orden actualizada con todos los campos calculados
            $ordenActualizada = $orden->fresh();
            
            // 🆕 Preparar campos que fueron realmente actualizados (incluyendo los calculados)
            $changedFields = array_keys($validatedData);
            
            // 🆕 Si se actualizó dia_de_entrega, añadir fecha_estimada_de_entrega a los campos cambiados
            if (in_array('dia_de_entrega', $changedFields) && !in_array('fecha_estimada_de_entrega', $changedFields)) {
                $changedFields[] = 'fecha_estimada_de_entrega';
            }
            
            // 🆕 Broadcast eventos con la orden actualizada y los campos reales
            broadcast(new \App\Events\OrdenUpdated($ordenActualizada, 'updated', $changedFields));
            \Log::info("Broadcast enviado para pedido {$ordenActualizada->numero_pedido} con campos:", $changedFields);

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
     * Invalidar caché de días calculados para una orden específica
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
                'message' => 'Número de pedido actualizado correctamente',
                'old_pedido' => $validatedData['old_pedido'],
                'new_pedido' => $validatedData['new_pedido']
            ]);
        });
    }

    /**
     * Obtener registros por orden (API para el modal de edición)
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
        });
    }

    /**
     * Actualizar descripción y regenerar registros_por_orden basado en el contenido
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
        });
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
     * Obtener detalles de una orden específica para el modal
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

            // Obtener datos básicos
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

            // Obtener prendas - usar plantilla si está relacionado a cotización
            try {
                // Verificar si el pedido está relacionado a una cotización
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
                    // Usar formato simple para pedidos sin cotización
                    $prendas = DB::table('prendas_pedido')
                        ->where('numero_pedido', $numeroPedido)
                        ->orderBy('id', 'asc')
                        ->get(['nombre_prenda', 'descripcion', 'cantidad_talla']);

                    // Formatear prendas con enumeración
                    $prendasFormato = [];
                    foreach ($prendas as $index => $prenda) {
                        $prendasFormato[] = [
                            'numero' => $index + 1,
                            'nombre' => $prenda->nombre_prenda ?? '-',
                            'descripcion' => $prenda->descripcion ?? '-',
                            'cantidad_talla' => $prenda->cantidad_talla ?? '-'
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
     * Obtener opciones de una columna específica con paginación y búsqueda
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
                    // Aplicar búsqueda si existe
                    $query = PedidoProduccion::distinct();
                    if (!empty($search)) {
                        $query->where('numero_pedido', 'LIKE', "%{$search}%");
                    }
                    $options = $query->pluck('numero_pedido')->filter()->sort()->values()->toArray();
                    break;
                case 'cliente':
                    // Aplicar búsqueda si existe
                    $query = PedidoProduccion::distinct();
                    if (!empty($search)) {
                        $query->where('cliente', 'LIKE', "%{$search}%");
                    }
                    $options = $query->pluck('cliente')->filter()->sort()->values()->toArray();
                    break;
                case 'descripcion':
                    // Para descripción, agrupar por descripción única
                    $ordenes = PedidoProduccion::all();
                    $descripcionesMap = [];
                    
                    foreach ($ordenes as $orden) {
                        $descripcion = $orden->getNombresPrendas();
                        if ($descripcion !== '-') {
                            // Si la descripción ya existe, agregar el pedido a la lista
                            if (!isset($descripcionesMap[$descripcion])) {
                                $descripcionesMap[$descripcion] = [];
                            }
                            $descripcionesMap[$descripcion][] = $orden->numero_pedido;
                        }
                    }
                    
                    // Convertir a array de opciones
                    $options = array_map(function($desc, $pedidos) {
                        return [
                            'value' => implode(',', $pedidos), // Guardar todos los pedidos con esa descripción
                            'display' => $desc
                        ];
                    }, array_keys($descripcionesMap), array_values($descripcionesMap));
                    break;
                case 'asesor':
                    $options = PedidoProduccion::with('asesora')->get()->pluck('asesora.name')->filter()->unique()->sort()->values()->toArray();
                    break;
                case 'forma_pago':
                    $options = PedidoProduccion::distinct()->pluck('forma_de_pago')->filter()->sort()->values()->toArray();
                    break;
                case 'encargado':
                    $options = PedidoProduccion::distinct()->pluck('encargado_orden')->filter()->sort()->values()->toArray();
                    break;
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Columna no válida'
                    ], 400);
            }

            // Filtrar por búsqueda si existe
            if (!empty($search)) {
                $options = array_filter($options, function($item) use ($search) {
                    $text = is_array($item) ? $item['display'] : $item;
                    return stripos($text, $search) !== false;
                });
                $options = array_values($options); // Reindexar array
            }

            $total = count($options);
            
            // Aplicar paginación
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
     * Filtrar órdenes por criterios específicos
     * POST /registros/filter-orders
     */
    public function filterOrders(Request $request)
    {
        try {
            $filters = $request->input('filters', []);
            $page = $request->input('page', 1);
            $perPage = 25;  // Sincronizado con la paginación inicial en RegistroOrdenQueryController

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
                            // Convertir "X días" a número
                            $dias = array_map(function($v) {
                                return (int) str_replace(' días', '', $v);
                            }, $values);
                            $query->whereIn('dia_de_entrega', $dias);
                            break;
                        case 'pedido':
                            $query->whereIn('numero_pedido', $values);
                            break;
                        case 'descripcion':
                            // Filtrar por descripciones (que pueden contener múltiples pedidos)
                            // Los valores vienen como "14342,14328,14329"
                            $allPedidos = [];
                            foreach ($values as $value) {
                                $pedidos = explode(',', $value);
                                $allPedidos = array_merge($allPedidos, $pedidos);
                            }
                            $query->whereIn('numero_pedido', $allPedidos);
                            break;
                        case 'cliente':
                            foreach ($values as $value) {
                                $query->orWhere('cliente', 'LIKE', '%' . $value . '%');
                            }
                            break;
                    }
                }
            }

            // Obtener resultados paginados
            $ordenes = $query->orderBy('created_at', 'asc')->paginate($perPage, ['*'], 'page', $page);

            // Transformar datos para la vista
            $ordenesData = $ordenes->map(function($orden) {
                return [
                    'id' => $orden->id,
                    'numero_pedido' => $orden->numero_pedido,
                    'cliente' => $orden->cliente,
                    'estado' => $orden->estado,
                    'area' => $orden->area,
                    'dia_de_entrega' => $orden->dia_de_entrega,
                    'dias_habiles' => $orden->calcularDiasHabiles(),
                    'descripcion' => $orden->getNombresPrendas(),
                    'cantidad' => $orden->prendas->sum('cantidad'),
                    'novedades' => $orden->novedades,
                    'asesor' => $orden->asesora ? $orden->asesora->name : '-',
                    'forma_de_pago' => $orden->forma_de_pago,
                    'fecha_creacion' => $orden->fecha_de_creacion_de_orden ? $orden->fecha_de_creacion_de_orden->format('d/m/Y') : '-',
                    'fecha_estimada' => $orden->fecha_estimada_entrega ? $orden->fecha_estimada_entrega->format('d/m/Y') : '-',
                    'encargado' => $orden->encargado_orden ?? '-',
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $ordenesData,
                'pagination' => [
                    'current_page' => $ordenes->currentPage(),
                    'total' => $ordenes->total(),
                    'per_page' => $ordenes->perPage(),
                    'last_page' => $ordenes->lastPage(),
                    'from' => $ordenes->firstItem(),
                    'to' => $ordenes->lastItem(),
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al filtrar órdenes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al filtrar órdenes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🆕 Búsqueda simple en tiempo real
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

            // Buscar por número de pedido o cliente
            $query = PedidoProduccion::where('numero_pedido', 'LIKE', '%' . $search . '%')
                ->orWhere('cliente', 'LIKE', '%' . $search . '%');

            // Si es búsqueda de tabla, retornar todos los campos con paginación
            if ($isTableSearch) {
                // Usar paginación
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
                )->with('prendas', 'asesora');

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

                // Reemplazar la colección con los datos mapeados
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

            // Si es búsqueda de dropdown, retornar solo lo necesario
            $ordenes = $query->select('id', 'numero_pedido', 'cliente', 'estado', 'area')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $ordenes
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en búsqueda de órdenes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error en búsqueda: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener imágenes de una orden (DEPRECATED - Usar RegistroOrdenQueryController)
     */

    /**
     * Actualizar novedades de una orden
     * POST /api/ordenes/{numero_pedido}/novedades
     */
    public function updateNovedades(Request $request, $numeroPedido)
    {
        try {
            \Log::info('📝 updateNovedades iniciado', ['numeroPedido' => $numeroPedido]);
            
            // Validar entrada
            $request->validate([
                'novedades' => 'nullable|string|max:5000'
            ]);

            \Log::info('✅ Validación exitosa');

            // Buscar la orden
            $orden = PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();
            
            \Log::info('✅ Orden encontrada', ['orden_id' => $orden->id]);

            // Actualizar novedades (reemplazo total)
            $orden->update([
                'novedades' => $request->input('novedades', '')
            ]);
            
            \Log::info('✅ Novedades actualizadas', ['novedades' => $request->input('novedades', '')]);

            // Registrar en auditoría si existe
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

            // Broadcast actualización en tiempo real
            broadcast(new \App\Events\OrdenUpdated($orden->fresh(), 'updated', ['novedades']));
            \Log::info('📡 Evento de broadcast enviado para novedades');

            return response()->json([
                'success' => true,
                'message' => 'Novedades actualizadas correctamente',
                'data' => [
                    'numero_pedido' => $orden->numero_pedido,
                    'novedades' => $orden->novedades
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('❌ Orden no encontrada', ['numeroPedido' => $numeroPedido]);
            return response()->json([
                'success' => false,
                'message' => 'Orden no encontrada'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('❌ Error al actualizar novedades: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
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
            \Log::info('📝 addNovedad iniciado', ['numeroPedido' => $numeroPedido]);
            
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
            
            // Concatenar con salto de línea si hay novedades anteriores
            if (!empty($novedadesActuales)) {
                $novedadesNuevas = $novedadesActuales . "\n\n" . $novedadFormato;
            } else {
                $novedadesNuevas = $novedadFormato;
            }
            
            // Actualizar novedades
            $orden->update([
                'novedades' => $novedadesNuevas
            ]);
            
            \Log::info('✅ Novedad agregada', [
                'usuario' => $usuario,
                'fecha_hora' => $fechaHora,
                'novedad' => $request->input('novedad')
            ]);

            // Registrar en auditoría si existe
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

            // Broadcast actualización en tiempo real
            broadcast(new \App\Events\OrdenUpdated($orden->fresh(), 'updated', ['novedades']));
            \Log::info('📡 Evento de broadcast enviado para nueva novedad');

            return response()->json([
                'success' => true,
                'message' => 'Novedad agregada correctamente',
                'data' => [
                    'numero_pedido' => $orden->numero_pedido,
                    'novedades' => $orden->novedades
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('❌ Orden no encontrada', ['numeroPedido' => $numeroPedido]);
            return response()->json([
                'success' => false,
                'message' => 'Orden no encontrada'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('❌ Error al agregar novedad: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar la novedad: ' . $e->getMessage()
            ], 500);
        }
    }
}
