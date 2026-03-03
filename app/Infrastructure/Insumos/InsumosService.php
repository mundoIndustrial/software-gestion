<?php

namespace App\Infrastructure\Insumos;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ConsecutivoReciboPedido;
use App\Models\MaterialesOrdenInsumos;
use App\Models\PedidoAnchoGeneral;
use App\Models\PedidoMetrajeColor;
use App\Events\ReciboAprobado;
use App\Application\Insumos\UseCases\GuardarAnchoMetrajeUseCase;
use App\Application\Insumos\DTOs\GuardarAnchoMetrajeDTO;
use App\Domain\Insumos\Services\AplicarFiltrosService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class InsumosService
{
    /**
     * Constructor con inyección de dependencias
     */
    public function __construct(
        private GuardarAnchoMetrajeUseCase $guardarAnchoMetrajeUseCase,
        private AplicarFiltrosService $aplicarFiltrosService
    ) {}
    
    /**
     * Dashboard del rol insumos
     */
    public function dashboard()
    {
        $user = Auth::user();
        
        // Verificar que sea usuario de insumos
        $this->verificarRolInsumos($user);
        
        return view('insumos.dashboard', [
            'user' => $user,
        ]);
    }

    /**
    /**
     * Control de materiales - Modificado para mostrar recibos de costura individuales
     */
    public function materiales(Request $request)
    {
        $startTime = microtime(true);
        \Log::info(' INSUMOS: Iniciando carga de recibos de costura individuales');
        
        $user = Auth::user();
        
        // Verificar que sea usuario de insumos
        $this->verificarRolInsumos($user);
        
        $queryStart = microtime(true);
        
        // Obtener parámetros de búsqueda y filtros
        $search = $request->get('search', '');
        
        // Obtener parámetros de filtro (soportar múltiples filtros)
        $filterColumns = $request->get('filter_columns', []);
        $filterValuesArray = $request->get('filter_values', []);
        
        // Asegurar que siempre sean arrays
        if (!is_array($filterColumns)) {
            $filterColumns = [$filterColumns];
        }
        if (!is_array($filterValuesArray)) {
            $filterValuesArray = [$filterValuesArray];
        }
        
        // Fallback para filtro antiguo (singular)
        $filterColumn = $request->get('filter_column', '');
        $filterValues = $request->get('filter_values', []);
        if (!is_array($filterValues)) {
            $filterValues = [$filterValues];
        }
        
        // CAMBIO PRINCIPAL: Obtener recibos de costura en lugar de pedidos
        $baseQuery = DB::table('consecutivos_recibos_pedidos')
            ->where('tipo_recibo', 'COSTURA')
            ->where('activo', 1)
            ->join('pedidos_produccion', 'consecutivos_recibos_pedidos.pedido_produccion_id', '=', 'pedidos_produccion.id')
            ->select(
                'consecutivos_recibos_pedidos.*',
                'consecutivos_recibos_pedidos.marcar_plooter',
                'pedidos_produccion.numero_pedido',
                'pedidos_produccion.numero_pedido as numero_pedido_original',
                'pedidos_produccion.cliente',
                'pedidos_produccion.estado as pedido_estado',
                'pedidos_produccion.area as pedido_area',
                'consecutivos_recibos_pedidos.estado as recibo_estado',
                'consecutivos_recibos_pedidos.area as recibo_area',
                'pedidos_produccion.fecha_de_creacion_de_orden',
                'pedidos_produccion.dia_de_entrega',
                'pedidos_produccion.fecha_estimada_de_entrega'
            );
        
        // Aplicar filtros de estados permitidos para recibos (excluyendo PENDIENTE_SUPERVISOR)
        $baseQuery->where(function($q) {
            $q->where('pedidos_produccion.estado', 'PENDIENTE_INSUMOS')
              ->where('pedidos_produccion.estado', '!=', 'PENDIENTE_SUPERVISOR')
              ->orWhere(function($q2) {
                  $q2->where('pedidos_produccion.area', 'LIKE', '%Corte%')
                     ->where('pedidos_produccion.estado', '!=', 'PENDIENTE_SUPERVISOR')
                     ->orWhere('pedidos_produccion.area', 'LIKE', '%Creación%orden%')
                     ->where('pedidos_produccion.estado', '!=', 'PENDIENTE_SUPERVISOR')
                     ->orWhere('pedidos_produccion.area', 'LIKE', '%Creación de orden%')
                     ->where('pedidos_produccion.estado', '!=', 'PENDIENTE_SUPERVISOR');
              });
        });
        
        // Aplicar filtros usando AplicarFiltrosService (DDD)
        $hasFilters = false;
        if (!empty($filterColumns) && !empty($filterValuesArray)) {
            $hasFilters = true;
            \Log::info(' Filtros recibidos (via AplicarFiltrosService):', [
                'filterColumns' => $filterColumns,
                'filterValuesArray' => $filterValuesArray
            ]);
            $baseQuery = $this->aplicarFiltrosService->aplicar($baseQuery, $filterColumns, $filterValuesArray);
        }
        // Fallback para filtro antiguo (singular)
        elseif (!empty($filterColumn) && !empty($filterValues)) {
            $hasFilters = true;
            // Convertir formato singular a formato multi para el service
            $columns = array_fill(0, count($filterValues), $filterColumn);
            $baseQuery = $this->aplicarFiltrosService->aplicar($baseQuery, $columns, $filterValues);
        }
        
        // Aplicar búsqueda si existe
        if (!empty($search)) {
            $hasFilters = true;
            $baseQuery->where(function($q) use ($search) {
                $q->where('pedidos_produccion.numero_pedido', 'LIKE', "%{$search}%")
                  ->orWhere('pedidos_produccion.cliente', 'LIKE', "%{$search}%");
            });
        }
        
        // Obtener todos los recibos con la información del pedido
        $allRecibos = $baseQuery->orderBy('consecutivos_recibos_pedidos.consecutivo_actual', 'desc')->get();
        
        // Transformar los datos para que sean compatibles con la vista
        $recibosTransformados = $allRecibos->map(function($recibo) {
            // Calcular días para este recibo
            $diasCalculados = 0;
            if ($recibo->fecha_de_creacion_de_orden) {
                try {
                    $fechaInicio = \Carbon\Carbon::parse($recibo->fecha_de_creacion_de_orden);
                    $fechaFin = \Carbon\Carbon::now();
                    
                    // Obtener festivos
                    $festivosArray = \App\Models\Festivo::pluck('fecha')->toArray();
                    $festivosSet = [];
                    foreach ($festivosArray as $f) {
                        try {
                            $festivosSet[\Carbon\Carbon::parse($f)->format('Y-m-d')] = true;
                        } catch (\Exception $e) {}
                    }
                    
                    // Calcular días hábiles
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
                    $diasCalculados = 0;
                }
            }
            
            // Crear objeto compatible con la vista
            return (object)[
                'id' => $recibo->id,
                'numero_pedido' => $recibo->consecutivo_actual, // N° de recibo
                'numero_pedido_original' => $recibo->numero_pedido_original, // N° de pedido original
                'cliente' => $recibo->cliente,
                'estado' => $recibo->recibo_estado ?? $recibo->pedido_estado, // Estado del recibo
                'area' => $recibo->recibo_area ?? $recibo->pedido_area, // Área del recibo
                'pedido_estado' => $recibo->pedido_estado, // Estado del pedido (para filtros)
                'fecha_de_creacion_de_orden' => $recibo->fecha_de_creacion_de_orden,
                'dia_de_entrega' => $recibo->dia_de_entrega,
                'fecha_estimada_de_entrega' => $recibo->fecha_estimada_de_entrega,
                'dias_calculados' => $diasCalculados,
                'pedido_produccion_id' => $recibo->pedido_produccion_id,
                'prenda_id' => $recibo->prenda_id,
                'consecutivo_actual' => $recibo->consecutivo_actual,
                'tipo_recibo' => $recibo->tipo_recibo,
                'marcar_plooter' => $recibo->marcar_plooter ?? false,
                'created_at' => $recibo->created_at,
                'updated_at' => $recibo->updated_at,
            ];
        });
        
        // Aplicar paginación manual
        $page = $request->get('page', 1);
        $perPage = 10;
        $total = $recibosTransformados->count();
        $items = $recibosTransformados->slice(($page - 1) * $perPage, $perPage)->values();
        
        // Crear paginador
        $ordenes = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => route('insumos.materiales.index'),
                'query' => $request->query(),
            ]
        );
        
        // Preservar parámetros de búsqueda y filtro en links de paginación
        $ordenes->appends($request->query());
        
        $queryTime = microtime(true) - $queryStart;
        \Log::info(" Consulta BD: {$queryTime}s, Total recibos: " . $ordenes->total() . ", Búsqueda: '{$search}'");
        
        $viewStart = microtime(true);
        $response = view('insumos.materiales.index', [
            'ordenes' => $ordenes,
            'user' => $user,
            'search' => $search,
        ]);
        $viewTime = microtime(true) - $viewStart;
        \Log::info(" Render vista: {$viewTime}s");
        
        $totalTime = microtime(true) - $startTime;
        \Log::info(" Total carga: {$totalTime}s");
        
        return $response;
    }

    /**
     * Verificar que el usuario tenga rol insumos, admin, supervisor_planta o patronista
     * Mejorado con validación más robusta
     */
    private function verificarRolInsumos($user)
    {
        if (!$user) {
            abort(401, 'Usuario no autenticado');
        }
        
        // Lista de roles permitidos para este módulo
        $rolesPermitidos = ['admin', 'supervisor_planta', 'patronista', 'insumos'];
        
        // Verificar usando el método del framework si está disponible
        if (method_exists($user, 'hasAnyRole')) {
            if (!$user->hasAnyRole($rolesPermitidos)) {
                Log::warning('Acceso denegado - rol no permitido', [
                    'user_id' => $user->id,
                    'user_roles' => $user->roles()->pluck('name')->toArray(),
                    'roles_permitidos' => $rolesPermitidos
                ]);
                abort(403, 'No autorizado para acceder a este módulo.');
            }
            return;
        }
        
        // Fallback: Verificación manual para compatibilidad
        $userRole = null;
        
        // Intentar obtener el rol del usuario de diferentes formas
        if (isset($user->role)) {
            if (is_string($user->role)) {
                $userRole = $user->role;
            } elseif (is_object($user->role) && isset($user->role->name)) {
                $userRole = $user->role->name;
            }
        }
        
        // Si no se encuentra el rol, denegar acceso
        if (!$userRole) {
            Log::warning('Acceso denegado - no se pudo determinar el rol', [
                'user_id' => $user->id,
                'user_data' => $user->toArray()
            ]);
            abort(403, 'No autorizado para acceder a este módulo.');
        }
        
        // Verificar si el rol está en la lista de permitidos
        if (!in_array($userRole, $rolesPermitidos)) {
            Log::warning('Acceso denegado - rol no permitido', [
                'user_id' => $user->id,
                'user_role' => $userRole,
                'roles_permitidos' => $rolesPermitidos
            ]);
            abort(403, 'No autorizado para acceder a este módulo.');
        }
    }

    /**
     * Guardar materiales de una orden
     */
    public function guardarMateriales(Request $request, $ordenId)
    {
        try {
            $user = Auth::user();
            $this->verificarRolInsumos($user);
            
            // Buscar por numero_pedido en lugar de ID
            $orden = PedidoProduccion::where('numero_pedido', $ordenId)->firstOrFail();
            
            // Validar datos
            $validated = $request->validate([
                'materiales' => 'array',
                'materiales.*.nombre' => 'required|string',
                'materiales.*.fecha_orden' => 'nullable|date',
                'materiales.*.fecha_pedido' => 'nullable|date',
                'materiales.*.fecha_pago' => 'nullable|date',
                'materiales.*.fecha_llegada' => 'nullable|date',
                'materiales.*.fecha_despacho' => 'nullable|date',
                'materiales.*.observaciones' => 'nullable|string',
                'materiales.*.recibido' => 'boolean',
                'prenda_id' => 'nullable|integer|exists:prendas_pedido,id',
            ]);
            
            $prendaId = $validated['prenda_id'] ?? null;
            
            // Si materiales no viene en el request, usar array vacío
            if (!isset($validated['materiales'])) {
                $validated['materiales'] = [];
            }
            
            // Guardar o eliminar materiales según el estado del checkbox
            $materialesGuardados = 0;
            $materialesEliminados = 0;
            
            \Log::info('🔵 GUARDANDO MATERIALES - Pedido ID: ' . $orden->id . ', Número: ' . $orden->numero_pedido);
            \Log::info(' Materiales recibidos:', $validated['materiales']);
            \Log::info(' Total de materiales: ' . count($validated['materiales']));
            
            foreach ($validated['materiales'] as $material) {
                $isRecibido = $material['recibido'] === true || $material['recibido'] === 'true' || $material['recibido'] === 1 || $material['recibido'] === '1';
                
                \Log::info(" Procesando material: {$material['nombre']}, recibido: {$material['recibido']}, isRecibido: " . ($isRecibido ? 'true' : 'false'));
                
                if ($isRecibido) {
                    // Guardar/actualizar si recibido es true
                    $matchCriteria = [
                        'numero_pedido' => $orden->numero_pedido,
                        'nombre_material' => $material['nombre'],
                    ];
                    if ($prendaId) {
                        $matchCriteria['prenda_id'] = $prendaId;
                    }
                    
                    $result = MaterialesOrdenInsumos::updateOrCreate(
                        $matchCriteria,
                        [
                            'prenda_id' => $prendaId,
                            'fecha_orden' => $material['fecha_orden'] ?? null,
                            'fecha_pedido' => $material['fecha_pedido'] ?? null,
                            'fecha_pago' => $material['fecha_pago'] ?? null,
                            'fecha_llegada' => $material['fecha_llegada'] ?? null,
                            'fecha_despacho' => $material['fecha_despacho'] ?? null,
                            'observaciones' => $material['observaciones'] ?? null,
                            'recibido' => true,
                        ]
                    );
                    $materialesGuardados++;
                    \Log::info(" Material guardado: {$material['nombre']}, ID: {$result->id}, Prenda: {$prendaId}, Fecha Pedido: {$material['fecha_pedido']}, Fecha Llegada: {$material['fecha_llegada']}");
                } else {
                    // Eliminar si recibido es false
                    $deleteCriteria = [
                        'numero_pedido' => $orden->numero_pedido,
                        'nombre_material' => $material['nombre'],
                    ];
                    if ($prendaId) {
                        $deleteCriteria['prenda_id'] = $prendaId;
                    }
                    
                    $deleted = MaterialesOrdenInsumos::where($deleteCriteria)->delete();
                    
                    if ($deleted > 0) {
                        $materialesEliminados++;
                        \Log::info("🗑️ Material eliminado: {$material['nombre']}");
                    } else {
                        \Log::info(" No se encontró material para eliminar: {$material['nombre']}");
                    }
                }
            }
            
            \Log::info(" Resumen: Guardados: $materialesGuardados, Eliminados: $materialesEliminados");
            
            $mensaje = [];
            if ($materialesGuardados > 0) {
                $mensaje[] = "Se guardaron {$materialesGuardados} material(es)";
            }
            if ($materialesEliminados > 0) {
                $mensaje[] = "Se eliminaron {$materialesEliminados} material(es)";
            }
            
            return response()->json([
                'success' => true,
                'message' => !empty($mensaje) 
                    ? implode(' y ', $mensaje) . ' correctamente' 
                    : 'Sin cambios',
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = [];
            foreach ($e->errors() as $field => $messages) {
                $errors = array_merge($errors, $messages);
            }
            return response()->json([
                'success' => false,
                'message' => 'Error de validación: ' . implode(', ', $errors)
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al guardar materiales: ' . $e->getMessage(), [
                'pedido' => $ordenId,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar los materiales: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un material inmediatamente
     */
    public function eliminarMaterial(Request $request, $ordenId)
    {
        try {
            $user = Auth::user();
            $this->verificarRolInsumos($user);
            
            // Buscar por numero_pedido en lugar de ID
            $orden = PedidoProduccion::where('numero_pedido', $ordenId)->firstOrFail();
            
            // Validar datos
            $validated = $request->validate([
                'nombre_material' => 'required|string',
            ]);
            
            // Eliminar el material
            $deleted = MaterialesOrdenInsumos::where([
                'numero_pedido' => $orden->numero_pedido,
                'nombre_material' => $validated['nombre_material'],
            ])->delete();
            
            if ($deleted > 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Material eliminado correctamente',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Material no encontrado',
                ], 404);
            }
        } catch (\Exception $e) {
            \Log::error('Error al eliminar material: ' . $e->getMessage(), [
                'pedido' => $ordenId,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el material: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener materiales de una orden (API)
     */
    public function obtenerMateriales($pedido)
    {
        try {
            $user = Auth::user();
            $this->verificarRolInsumos($user);
            
            // Validar que el pedido existe
            PedidoProduccion::where('numero_pedido', $pedido)->firstOrFail();
            
            // Obtener materiales guardados usando numero_pedido, filtrados por prenda si se envía
            $query = MaterialesOrdenInsumos::where('numero_pedido', $pedido);
            
            $prendaId = request('prenda_id');
            if ($prendaId) {
                $query->where('prenda_id', $prendaId);
            }
            
            $materiales = $query->get();
            
            // Obtener nombre de la prenda si se filtró por prenda_id
            $nombrePrenda = null;
            if ($prendaId) {
                $prenda = \App\Models\PrendaPedido::find($prendaId);
                $nombrePrenda = $prenda ? $prenda->nombre_prenda : null;
            }
            
            // Transformar los datos para la respuesta
            $materialesTransformados = $materiales->map(function($material) {
                return [
                    'id' => $material->id,
                    'nombre_material' => $material->nombre_material,
                    'recibido' => $material->recibido,
                    'prenda_id' => $material->prenda_id,
                    'fecha_orden' => $material->fecha_orden ? $material->fecha_orden->format('Y-m-d') : null,
                    'fecha_pedido' => $material->fecha_pedido ? $material->fecha_pedido->format('Y-m-d') : null,
                    'fecha_pago' => $material->fecha_pago ? $material->fecha_pago->format('Y-m-d') : null,
                    'fecha_llegada' => $material->fecha_llegada ? $material->fecha_llegada->format('Y-m-d') : null,
                    'fecha_despacho' => $material->fecha_despacho ? $material->fecha_despacho->format('Y-m-d') : null,
                    'dias_demora' => $material->dias_demora,
                    'observaciones' => $material->observaciones,
                ];
            });
            
            return response()->json([
                'success' => true,
                'materiales' => $materialesTransformados,
                'nombre_prenda' => $nombrePrenda,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener materiales: ' . $e->getMessage(), [
                'pedido' => $pedido,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los materiales: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar todas las notificaciones como leídas (para supervisor_planta)
     */
    public function markAllNotificationsAsRead()
    {
        try {
            $user = Auth::user();
            
            // Verificar que sea usuario de insumos
            $this->verificarRolInsumos($user);
            
            // Guardar en sesión los IDs de órdenes pendientes de revisión
            $ordenesPendientes = PedidoProduccion::whereNull('aprobado_por_supervisor_en')
                ->whereNotNull('cotizacion_id')
                ->where('estado', '!=', 'Anulada')
                ->pluck('id')
                ->toArray();
            
            session(['viewed_ordenes_' . $user->id => $ordenesPendientes]);
            
            return response()->json([
                'success' => true,
                'message' => 'Notificaciones marcadas como leídas'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al marcar notificaciones como leídas',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener ancho y metraje de un pedido
     */
    public function obtenerAnchoMetraje($numeroPedido)
    {
        try {
            $user = Auth::user();
            $this->verificarRolInsumos($user);
            
            // Obtener el pedido por número
            $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)
                ->firstOrFail();

            // Buscar registros de ancho y metraje en las nuevas tablas
            $prendas = $pedido->prendas()->get();
            
            $anchoData = [];
            $metrajeData = [];
            
            foreach ($prendas as $prenda) {
                // Buscar ancho general para esta prenda
                $ancho = PedidoAnchoGeneral::where('pedido_produccion_id', $pedido->id)
                    ->where('prenda_pedido_id', $prenda->id)
                    ->first();
                    
                if ($ancho) {
                    $anchoData[] = [
                        'prenda_id' => $prenda->id,
                        'prenda_nombre' => $prenda->nombre_prenda,
                        'ancho' => $ancho->ancho
                    ];
                }
                
                // Buscar metrajes por color para esta prenda
                $metrajes = PedidoMetrajeColor::where('pedido_produccion_id', $pedido->id)
                    ->where('prenda_pedido_id', $prenda->id)
                    ->get();
                    
                foreach ($metrajes as $metraje) {
                    $metrajeData[] = [
                        'prenda_id' => $prenda->id,
                        'prenda_nombre' => $prenda->nombre_prenda,
                        'color' => $metraje->color,
                        'metraje' => $metraje->metraje
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'ancho_registros' => $anchoData,
                    'metraje_registros' => $metrajeData
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error al obtener ancho y metraje: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ancho y metraje'
            ], 500);
        }
    }

    /**
     */
    public function cambiarEstado(Request $request, $numeroPedido)
    {
        try {
            $user = Auth::user();
            $this->verificarRolInsumos($user);
            
            // Buscar el pedido por número con bloqueo para evitar concurrencia
            $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)
                ->lockForUpdate()
                ->firstOrFail();
            
            // Validar datos con todos los estados permitidos
            $validated = $request->validate([
                'estado' => [
                    'required',
                    'string',
                    Rule::in(['No iniciado', 'En Ejecución', 'PENDIENTE_INSUMOS'])
                ],
            ]);
            
            // Validar transición de estado
            $estadoActual = $pedido->estado;
            $nuevoEstado = $validated['estado'];
            
            // No permitir cambiar al mismo estado
            if ($estadoActual === $nuevoEstado) {
                return response()->json([
                    'success' => false,
                    'message' => 'El pedido ya se encuentra en el estado "' . $nuevoEstado . '"'
                ], 422);
            }
            
            // Validar transiciones permitidas
            if (!$this->esTransicionPermitida($estadoActual, $nuevoEstado, $user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transición de estado no permitida: de "' . $estadoActual . '" a "' . $nuevoEstado . '"'
                ], 422);
            }
            
            // Determinar el área según el nuevo estado
            $nuevaArea = $this->determinarAreaPorEstado($nuevoEstado);
            
            // Guardar estado anterior para logging
            $estadoAnterior = $pedido->estado;
            
            // Actualizar estado y área
            $pedido->estado = $nuevoEstado;
            $pedido->area = $nuevaArea;
            $pedido->save();
            
            // Crear procesos automáticos si el pedido pasa a producción
            $procesosCreados = 0;
            $detallesProcesos = [];
            
            if (($estadoActual === 'Pendiente' || $estadoActual === 'PENDIENTE_INSUMOS') && $nuevoEstado === 'En Ejecución') {
                try {
                    $procesoService = new \App\Services\Insumos\ProcesoAutomaticoService();
                    $resultadoProcesos = $procesoService->crearProcesosParaPedido($numeroPedido);
                    
                    if ($resultadoProcesos['success']) {
                        $procesosCreados = $resultadoProcesos['procesos_creados'];
                        $detallesProcesos = $resultadoProcesos['detalles'] ?? [];
                    }
                } catch (\Exception $e) {
                    Log::warning('Error al crear procesos automáticos', [
                        'numero_pedido' => $numeroPedido,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Logging sin emojis para mejor compatibilidad
            Log::info('Estado del pedido cambiado', [
                'numero_pedido' => $numeroPedido,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $nuevoEstado,
                'area_anterior' => $pedido->getOriginal('area'),
                'area_nueva' => $nuevaArea,
                'usuario_id' => $user->id,
                'usuario_nombre' => $user->name,
                'timestamp' => now()->toISOString(),
                'procesos_creados' => $procesosCreados
            ]);
            
            // Construir mensaje mejorado
            $message = 'Estado actualizado correctamente';
            if ($procesosCreados > 0) {
                $message .= ". Se crearon {$procesosCreados} procesos automáticamente";
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'estado_anterior' => $estadoAnterior,
                'nuevo_estado' => $nuevoEstado,
                'nueva_area' => $nuevaArea,
                'procesos_creados' => $procesosCreados,
                'detalles_procesos' => $detallesProcesos
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado del pedido', [
                'numero_pedido' => $numeroPedido,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado'
            ], 500);
        }
    }
    
    /**
     * Validar si la transición de estado es permitida
     */
    private function esTransicionPermitida($estadoActual, $nuevoEstado, $user)
    {
        // Reglas de transición por rol y estado actual
        
        // Desde PENDIENTE_INSUMOS: Solo puede ir a "No iniciado" o "En Ejecución"
        if ($estadoActual === 'PENDIENTE_INSUMOS') {
            return in_array($nuevoEstado, ['No iniciado', 'En Ejecución']);
        }
        
        // Desde "No iniciado": Puede ir a "En Ejecución"
        if ($estadoActual === 'No iniciado') {
            return $nuevoEstado === 'En Ejecución';
        }
        
        // Desde "En Ejecución": No permite cambios hacia atrás (solo casos especiales)
        if ($estadoActual === 'En Ejecución') {
            // Solo admin o supervisor_planta pueden revertir estados
            return $user->hasAnyRole(['admin', 'supervisor_planta']) && 
                   in_array($nuevoEstado, ['No iniciado', 'PENDIENTE_INSUMOS']);
        }
        
        // Otros estados: no permiten cambios
        return false;
    }
    
    /**
     * Determinar el área según el estado
     */
    private function determinarAreaPorEstado($estado)
    {
        switch ($estado) {
            case 'No iniciado':
                return 'Corte';
            case 'En Ejecución':
                return 'Corte';
            case 'PENDIENTE_INSUMOS':
                return 'Insumos';
            default:
                return 'Corte';
        }
    }

    /**
     * Cambiar estado de un recibo individual (consecutivos_recibos_pedidos)
     * Solo aprueba ese recibo específico, NO todo el pedido
     */
    public function cambiarEstadoRecibo(Request $request, $reciboId)
    {
        try {
            $user = Auth::user();
            $this->verificarRolInsumos($user);
            
            // Buscar el recibo específico
            $recibo = ConsecutivoReciboPedido::where('id', $reciboId)
                ->lockForUpdate()
                ->firstOrFail();
            
            $validated = $request->validate([
                'estado' => ['required', 'string', Rule::in(['No iniciado', 'En Ejecución'])],
            ]);
            
            $nuevoEstado = $validated['estado'];
            $estadoAnteriorRecibo = $recibo->estado ?? 'PENDIENTE_INSUMOS';
            
            // Solo permitir aprobar si está en PENDIENTE_INSUMOS
            if ($estadoAnteriorRecibo !== 'PENDIENTE_INSUMOS') {
                return response()->json([
                    'success' => false,
                    'message' => 'Este recibo ya ha sido aprobado (estado actual: ' . $estadoAnteriorRecibo . ')'
                ], 422);
            }
            
            // Actualizar estado y area del recibo
            $recibo->estado = $nuevoEstado;
            $recibo->area = $this->determinarAreaPorEstado($nuevoEstado);
            $recibo->save();
            
            // Contar recibos de costura pendientes restantes
            $recibosPendientes = ConsecutivoReciboPedido::where('pedido_produccion_id', $recibo->pedido_produccion_id)
                ->where('tipo_recibo', 'COSTURA')
                ->where('activo', 1)
                ->where('estado', 'PENDIENTE_INSUMOS')
                ->count();
            
            $pedido = PedidoProduccion::find($recibo->pedido_produccion_id);
            $procesosCreados = 0;
            $detallesProcesos = [];
            
            // Actualizar el estado del pedido padre inmediatamente al aprobar cualquier recibo
            if ($pedido && $pedido->estado === 'PENDIENTE_INSUMOS') {
                $estadoAnteriorPedido = $pedido->estado;
                $pedido->estado = $nuevoEstado;
                $pedido->area = $this->determinarAreaPorEstado($nuevoEstado);
                $pedido->save();
                
                Log::info('Pedido padre actualizado al aprobar recibo', [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'estado_anterior' => $estadoAnteriorPedido,
                    'estado_nuevo' => $nuevoEstado,
                    'recibos_pendientes' => $recibosPendientes
                ]);
            }
            
            // Crear proceso de Corte para la prenda específica del recibo aprobado
            if ($pedido && $nuevoEstado === 'En Ejecución' && $recibo->prenda_id) {
                try {
                    // Verificar que no exista ya un proceso de Corte para esta prenda
                    $yaExisteCorte = \App\Models\ProcesoPrenda::where('numero_pedido', $pedido->numero_pedido)
                        ->where('prenda_pedido_id', $recibo->prenda_id)
                        ->where('proceso', 'Corte')
                        ->whereNull('deleted_at')
                        ->exists();
                    
                    if (!$yaExisteCorte) {
                        \App\Models\ProcesoPrenda::create([
                            'numero_pedido' => $pedido->numero_pedido,
                            'prenda_pedido_id' => $recibo->prenda_id,
                            'proceso' => 'Corte',
                            'fecha_inicio' => now(),
                            'estado_proceso' => 'Pendiente',
                            'observaciones' => "Proceso de Corte creado automáticamente al aprobar recibo #{$recibo->consecutivo_actual}",
                            'codigo_referencia' => "P{$pedido->numero_pedido}-COR-PP{$recibo->prenda_id}-" . date('His'),
                        ]);
                        
                        $procesosCreados = 1;
                        $detallesProcesos = ['Corte (Prenda ID: ' . $recibo->prenda_id . ')'];
                        
                        Log::info('Proceso de Corte creado para prenda al aprobar recibo', [
                            'recibo_id' => $reciboId,
                            'prenda_id' => $recibo->prenda_id,
                            'numero_pedido' => $pedido->numero_pedido,
                        ]);
                    } else {
                        Log::info('Proceso de Corte ya existe para esta prenda, no se duplica', [
                            'prenda_id' => $recibo->prenda_id,
                            'numero_pedido' => $pedido->numero_pedido,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::warning('Error al crear proceso de Corte para prenda', [
                        'recibo_id' => $reciboId,
                        'prenda_id' => $recibo->prenda_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            Log::info('Estado de recibo individual cambiado', [
                'recibo_id' => $reciboId,
                'consecutivo' => $recibo->consecutivo_actual,
                'pedido_produccion_id' => $recibo->pedido_produccion_id,
                'estado_anterior' => $estadoAnteriorRecibo,
                'estado_nuevo' => $nuevoEstado,
                'recibos_pendientes' => $recibosPendientes,
                'usuario_id' => $user->id,
            ]);
            
            $message = "Recibo #{$recibo->consecutivo_actual} aprobado correctamente.";
            if ($recibosPendientes === 0) {
                $message .= " Todos los recibos del pedido fueron aprobados.";
            } else {
                $message .= " Quedan {$recibosPendientes} recibo(s) pendiente(s).";
            }
            if ($procesosCreados > 0) {
                $message .= " Se crearon {$procesosCreados} procesos automáticamente.";
            }
            
            // Disparar evento en tiempo real para recibos-costura
            try {
                $clienteNombre = $pedido ? $pedido->cliente : '';
                $numeroPedido = $pedido ? $pedido->numero_pedido : null;
                
                event(new ReciboAprobado([
                    'recibo_id' => $recibo->id,
                    'consecutivo' => $recibo->consecutivo_actual,
                    'pedido_produccion_id' => $recibo->pedido_produccion_id,
                    'prenda_id' => $recibo->prenda_id,
                    'tipo_recibo' => $recibo->tipo_recibo,
                    'estado' => $nuevoEstado,
                    'area' => $recibo->area,
                    'cliente' => $clienteNombre,
                    'numero_pedido' => $numeroPedido,
                ]));
            } catch (\Exception $e) {
                Log::warning('Error al broadcast ReciboAprobado', [
                    'recibo_id' => $reciboId,
                    'error' => $e->getMessage()
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'estado_anterior' => $estadoAnteriorRecibo,
                'nuevo_estado' => $nuevoEstado,
                'recibos_pendientes' => $recibosPendientes,
                'pedido_actualizado' => $recibosPendientes === 0,
                'procesos_creados' => $procesosCreados,
                'detalles_procesos' => $detallesProcesos
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Recibo no encontrado'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al cambiar estado del recibo', [
                'recibo_id' => $reciboId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado del recibo'
            ], 500);
        }
    }

    /**
     * Guardar ancho y metraje de un pedido
     * DEPRECATED: Use guardarAnchoMetrajePrenda() for per-prenda saving
     */
    public function guardarAnchoMetraje(Request $request, $numeroPedido)
    {
        try {
            $user = Auth::user();
            $this->verificarRolInsumos($user);
            
            // Validar datos de entrada
            $validated = $request->validate([
                'prenda_pedido_id' => 'required|integer',
                'ancho' => 'nullable|numeric|min:0',
                'metraje' => 'nullable|numeric|min:0',
                'color' => 'nullable|string',
            ]);

            // Obtener el pedido por número
            $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)
                ->firstOrFail();

            $prendaId = $validated['prenda_pedido_id'];
            
            // Guardar ancho general si se proporciona
            if (!empty($validated['ancho'])) {
                PedidoAnchoGeneral::updateOrCreate(
                    [
                        'pedido_produccion_id' => $pedido->id,
                        'prenda_pedido_id' => $prendaId
                    ],
                    [
                        'ancho' => $validated['ancho'],
                        'creado_por' => $user->id,
                        'actualizado_por' => $user->id
                    ]
                );
            }
            
            // Guardar metraje por color si se proporciona
            if (!empty($validated['metraje']) && !empty($validated['color'])) {
                PedidoMetrajeColor::updateOrCreate(
                    [
                        'pedido_produccion_id' => $pedido->id,
                        'prenda_pedido_id' => $prendaId,
                        'color' => $validated['color']
                    ],
                    [
                        'metraje' => $validated['metraje'],
                        'creado_por' => $user->id,
                        'actualizado_por' => $user->id
                    ]
                );
            }

            \Log::info('Ancho y metraje guardado', [
                'numero_pedido' => $numeroPedido,
                'pedido_id' => $pedido->id,
                'prenda_id' => $prendaId,
                'ancho' => $validated['ancho'] ?? null,
                'metraje' => $validated['metraje'] ?? null,
                'color' => $validated['color'] ?? null,
                'usuario_id' => $user->id,
                'usuario_nombre' => $user->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ancho y metraje guardados correctamente'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::warning('Pedido no encontrado para ancho/metraje', [
                'numero_pedido' => $numeroPedido,
                'usuario_id' => Auth::id(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al guardar ancho y metraje: ' . $e->getMessage(), [
                'numero_pedido' => $numeroPedido,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar ancho y metraje: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener las prendas de un pedido para el selector
     */
    public function obtenerPrendas($numeroPedido)
    {
        try {
            $user = Auth::user();
            $this->verificarRolInsumos($user);
            
            // Buscar el pedido por número para obtener el ID
            $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();
            
            // Obtener las prendas del pedido usando el ID
            $prendas = $pedido->prendas()
                ->select('id', 'nombre_prenda', 'descripcion')
                ->get();
            
            return response()->json([
                'success' => true,
                'prendas' => $prendas
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error al obtener prendas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener prendas'
            ], 500);
        }
    }

    /**
     * Obtener ancho y metraje de una prenda específica
     */
    /**
     * Obtener colores/telas disponibles para una prenda (detecta el flujo: normal, piezas o talla-color)
     * 
     * Lógica:
     * 1. Si prenda_pedido_talla_colores tiene registros → MODO TALLA-COLOR (matriz)
     * 2. Else si prenda_pedido_colores_telas tiene registros → MODO PIEZAS (múltiples telas/colores)
     * 3. Else → MODO NORMAL (una sola tela)
     */
    public function obtenerColoresPrenda($numeroPedido, $prendaId)
    {
        try {
            $user = Auth::user();
            $this->verificarRolInsumos($user);
            
            $pedido = PedidoProduccion::find($numeroPedido)
                ?? PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();
            
            // ==================== VERIFICAR FLUJO 3: TALLA-COLOR ====================
            // Obtener tallas con sus colores asignados
            $tallasConColores = \App\Models\PrendaPedidoTalla::where('prenda_pedido_id', $prendaId)
                ->with('coloresAsignados')  // relación a prenda_pedido_talla_colores
                ->get();
            
            // Contar si hay registros en prenda_pedido_talla_colores
            $totalTallaColores = 0;
            $tallaColorData = [];
            
            foreach ($tallasConColores as $talla) {
                if ($talla->coloresAsignados && count($talla->coloresAsignados) > 0) {
                    $totalTallaColores += count($talla->coloresAsignados);
                    foreach ($talla->coloresAsignados as $colorData) {
                        $tallaColorData[] = [
                            'talla' => $talla->talla,
                            'color_nombre' => $colorData->color_nombre,
                            'tela_nombre' => $colorData->tela_nombre,
                            'cantidad' => $colorData->cantidad
                        ];
                    }
                }
            }
            
            if ($totalTallaColores > 0) {
                // MODO TALLA-COLOR: Matriz completa
                // Agrupar datos para el frontend
                $coloresPorNombre = [];
                foreach ($tallaColorData as $tc) {
                    if (!isset($coloresPorNombre[$tc['color_nombre']])) {
                        $coloresPorNombre[$tc['color_nombre']] = [
                            'nombre' => $tc['color_nombre'],
                            'tallas' => [],
                            'telas' => []
                        ];
                    }
                    if (!in_array($tc['talla'], $coloresPorNombre[$tc['color_nombre']]['tallas'])) {
                        $coloresPorNombre[$tc['color_nombre']]['tallas'][] = $tc['talla'];
                    }
                    if (!in_array($tc['tela_nombre'], $coloresPorNombre[$tc['color_nombre']]['telas'])) {
                        $coloresPorNombre[$tc['color_nombre']]['telas'][] = $tc['tela_nombre'];
                    }
                }
                
                return response()->json([
                    'success' => true,
                    'modo' => 'talla-color',
                    'colores' => array_values($coloresPorNombre),
                    'tallaColorData' => $tallaColorData,
                    'esCombinada' => count($coloresPorNombre) > 1,
                    'esMatriz' => true
                ]);
            }
            
            // ==================== VERIFICAR FLUJO 2: PIEZAS (múltiples telas/colores) ====================
            $coloresTelas = \App\Models\PrendaPedidoColorTela::where('prenda_pedido_id', $prendaId)
                ->get();
            
            if ($coloresTelas->count() > 0) {
                // MODO PIEZAS: Múltiples telas/colores sin asignación específica por talla
                $coloresUnicos = [];
                $telasUnicas = [];
                
                foreach ($coloresTelas as $ct) {
                    $colorNombre = $ct->color_nombre ?? \App\Models\Color::find($ct->color_id)?->nombre ?? "Color {$ct->color_id}";
                    $telaNombre = $ct->tela_nombre ?? \App\Models\Tela::find($ct->tela_id)?->nombre ?? "Tela {$ct->tela_id}";
                    
                    // Crear clave única para color-tela
                    $clave = "{$colorNombre}-{$telaNombre}";
                    
                    if (!isset($coloresUnicos[$clave])) {
                        $coloresUnicos[$clave] = [
                            'nombre' => $colorNombre,
                            'tela' => $telaNombre,
                            'color_id' => $ct->color_id,
                            'tela_id' => $ct->tela_id,
                            'tallas' => []  // Sin tallas específicas en este modo
                        ];
                    }
                    if (!in_array($colorNombre, $telasUnicas)) {
                        $telasUnicas[] = $telaNombre;
                    }
                }
                
                return response()->json([
                    'success' => true,
                    'modo' => 'piezas',
                    'colores' => array_values($coloresUnicos),
                    'esCombinada' => count($coloresUnicos) > 1,
                    'esMatriz' => false
                ]);
            }
            
            // ==================== FLUJO 1: NORMAL (una sola tela) ====================
            // Si llegamos aquí, es una prenda normal con una única tela
            return response()->json([
                'success' => true,
                'modo' => 'normal',
                'colores' => [],
                'esCombinada' => false,
                'esMatriz' => false
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error al obtener colores de prenda: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener colores de prenda: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtener ancho general y metrajes por color para una prenda
     */
    public function obtenerAnchoMetrajePrenda($numeroPedido, $prendaId)
    {
        try {
            $user = Auth::user();
            $this->verificarRolInsumos($user);
            
            $pedido = PedidoProduccion::find($numeroPedido)
                ?? PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();
            
            // Obtener ancho general
            $anchoGeneral = PedidoAnchoGeneral::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_pedido_id', $prendaId)
                ->first();
            
            // Obtener metrajes por color
            $metrajesPorColor = PedidoMetrajeColor::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_pedido_id', $prendaId)
                ->get();
            
            // Construir respuesta con formato consistente
            $data = [];
            
            // Agregar metrajes por color (formato principal para ReceiptRenderer)
            foreach ($metrajesPorColor as $metraje) {
                $data[] = [
                    'color' => $metraje->color,
                    'metraje' => $metraje->metraje,
                    'prenda_id' => $prendaId
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'ancho' => $anchoGeneral ? $anchoGeneral->ancho : null
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error al obtener ancho/metraje de prenda: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ancho/metraje de prenda'
            ], 500);
        }
    }

    /**
     * Guardar ancho general y/o metraje por color
     */
    public function guardarAnchoMetrajePrenda(Request $request, $numeroPedido)
    {
        try {
            $user = Auth::user();
            $this->verificarRolInsumos($user);
            
            // Validar datos
            $validated = $request->validate([
                'prenda_id' => 'required|integer|exists:prendas_pedido,id',
                'color' => 'nullable|string|max:100',
                'tela' => 'nullable|string|max:100',
                'talla' => 'nullable|string|max:50',
                'tipo_modo' => 'nullable|in:normal,color,pieza',
                'ancho' => 'nullable|numeric|min:0',
                'metraje' => 'nullable|numeric|min:0'
            ]);
            
            // Crear DTO desde datos validados
            $dto = GuardarAnchoMetrajeDTO::fromRequest($validated, $numeroPedido, $user->id);
            
            // Ejecutar UseCase
            $resultado = $this->guardarAnchoMetrajeUseCase->execute($dto);
            
            if (!$resultado['success']) {
                return response()->json($resultado, 422);
            }
            
            return response()->json($resultado);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al guardar ancho y metraje de prenda: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar ancho y metraje de prenda'
            ], 500);
        }
    }

    /**
     * Obtener el número de recibo para una prenda en un pedido
     */
    public function obtenerReciboPrenda($numeroPedido, $prendaId)
    {
        try {
            $user = Auth::user();
            $this->verificarRolInsumos($user);
            
            $pedido = PedidoProduccion::find($numeroPedido)
                ?? PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();
            
            // Buscar el recibo más reciente/activo para esta prenda en este pedido
            $recibo = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_id', $prendaId)
                ->where('activo', 1)
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($recibo) {
                return response()->json([
                    'success' => true,
                    'recibo' => $recibo->consecutivo_actual
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'recibo' => null
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('Error al obtener recibo de prenda: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener recibo de prenda'
            ], 500);
        }
    }

    /**
     * Mostrar recibos de costura para el módulo de insumos
     * Funciona como /recibos-costura pero manteniendo las opciones de insumos
     */
    public function recibosCostura(Request $request)
    {
        try {
            $user = Auth::user();
            $this->verificarRolInsumos($user);

            // Obtener recibos de costura activos, aprobados y excluyendo pedidos con estado PENDIENTE_SUPERVISOR
            $recibosCostura = DB::table('consecutivos_recibos_pedidos')
                ->where('tipo_recibo', 'COSTURA')
                ->where('activo', 1)
                ->where('estado', '!=', 'PENDIENTE_INSUMOS')
                ->whereNotExists(function($query) {
                    $query->select(DB::raw(1))
                          ->from('pedidos_produccion')
                          ->whereRaw('pedidos_produccion.id = consecutivos_recibos_pedidos.pedido_produccion_id')
                          ->where('pedidos_produccion.estado', 'PENDIENTE_SUPERVISOR');
                })
                ->orderBy('consecutivo_actual', 'desc')
                ->get();

            \Log::info('[recibosCostura] Filtrando recibos de costura - excluyendo pedidos PENDIENTE_SUPERVISOR', [
                'total_recibos_encontrados' => $recibosCostura->count()
            ]);

            // Obtener festivos para cálculo de días
            $currentYear = now()->year;
            $nextYear = now()->addYear()->year;
            $festivos = array_merge(
                \App\Services\FestivosColombiaService::obtenerFestivos($currentYear),
                \App\Services\FestivosColombiaService::obtenerFestivos($nextYear)
            );

            // Obtener información adicional de pedidos y prendas
            $recibosConInfo = $recibosCostura->map(function ($recibo) use ($festivos) {
                $pedido = PedidoProduccion::find($recibo->pedido_produccion_id);
                
                \Log::info('[materiales] Procesando recibo para insumos', [
                    'recibo_id' => $recibo->id,
                    'pedido_produccion_id' => $recibo->pedido_produccion_id,
                    'prenda_id' => $recibo->prenda_id,
                    'tipo_recibo' => $recibo->tipo_recibo,
                    'pedido_encontrado' => $pedido ? true : false
                ]);
                
                if ($pedido) {
                    \Log::info('[materiales] Prendas del pedido', [
                        'pedido_id' => $pedido->id,
                        'total_prendas' => $pedido->prendas ? $pedido->prendas->count() : 0,
                        'prendas_ids' => $pedido->prendas ? $pedido->prendas->pluck('id')->toArray() : []
                    ]);
                }
                
                // Calcular días para este pedido (desde fecha de creación del pedido hasta hoy)
                $diasCalculados = 0;
                if ($pedido && $pedido->fecha_de_creacion_de_orden) {
                    try {
                        // Para recibos, calcular desde fecha_de_creacion_de_orden del pedido hasta hoy
                        $fechaInicio = $pedido->fecha_de_creacion_de_orden;
                        $fechaFin = \Carbon\Carbon::now();
                        
                        // Obtener festivos
                        $festivosArray = \App\Models\Festivo::pluck('fecha')->toArray();
                        $festivosSet = [];
                        foreach ($festivosArray as $f) {
                            try {
                                $festivosSet[\Carbon\Carbon::parse($f)->format('Y-m-d')] = true;
                            } catch (\Exception $e) {}
                        }
                        
                        // Calcular días hábiles manualmente (misma lógica que CacheCalculosService)
                        $current = $fechaInicio->copy()->addDay();  // Saltar al próximo día
                        $totalDays = 0;
                        $maxIterations = 365;
                        $iterations = 0;
                        
                        while ($current <= $fechaFin && $iterations < $maxIterations) {
                            $dateString = $current->format('Y-m-d');
                            $isWeekend = $current->dayOfWeek === 0 || $current->dayOfWeek === 6;
                            $isFestivo = isset($festivosSet[$dateString]);
                            
                            // Solo contar si es día hábil (no es fin de semana ni festivo)
                            if (!$isWeekend && !$isFestivo) {
                                $totalDays++;
                            }
                            
                            $current->addDay();
                            $iterations++;
                        }
                        
                        $diasCalculados = max(0, $totalDays);
                        
                        \Log::info('[recibosCostura] Días calculados para pedido', [
                            'recibo_id' => $recibo->id,
                            'pedido_id' => $pedido->id,
                            'numero_pedido' => $pedido->numero_pedido,
                            'fecha_creacion_pedido' => $pedido->fecha_de_creacion_de_orden->format('Y-m-d H:i:s'),
                            'dias_calculados' => $diasCalculados
                        ]);
                        
                    } catch (\Exception $e) {
                        \Log::warning('Error calculando días para recibo de costura', [
                            'recibo_id' => $recibo->id,
                            'pedido_id' => $pedido->id,
                            'error' => $e->getMessage()
                        ]);
                        $diasCalculados = 0;
                    }
                }
                
                // Obtener el proceso más reciente para el área
                $areaProcesoReciente = $this->obtenerAreaProcesoMasReciente($recibo->pedido_produccion_id, $recibo->prenda_id);
                
                return [
                    'id' => $recibo->id,
                    'consecutivo_actual' => $recibo->consecutivo_actual,
                    'pedido_produccion_id' => $recibo->pedido_produccion_id,
                    'prenda_id' => $recibo->prenda_id,
                    'tipo_recibo' => $recibo->tipo_recibo,
                    'notas' => $recibo->notas,
                    'created_at' => $recibo->created_at,
                    'updated_at' => $recibo->updated_at,
                    'dias_calculados' => $diasCalculados,
                    'pedido_info' => $pedido ? [
                        'numero_pedido' => $pedido->numero_pedido,
                        'cliente' => $pedido->cliente,
                        'estado' => $pedido->estado,
                        'area' => $areaProcesoReciente,
                        'dia_de_entrega' => $pedido->dia_de_entrega,
                        'fecha_estimada_de_entrega' => $pedido->fecha_estimada_de_entrega ? $pedido->fecha_estimada_de_entrega->format('d/m/Y') : null,
                        'fecha_creacion_orden' => $pedido->fecha_de_creacion_de_orden ? $pedido->fecha_de_creacion_de_orden->format('Y-m-d H:i:s') : null,
                    ] : null,
                ];
            });

            return view('insumos.materiales.recibos-costura', [
                'recibos' => $recibosConInfo,
                'title' => 'Recibos de Costura - Insumos'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en recibosCostura (Insumos): ' . $e->getMessage());
            return back()->with('error', 'Error al cargar los recibos de costura');
        }
    }

    /**
     * Obtener el área del proceso más reciente de una prenda
     */
    private function obtenerAreaProcesoMasReciente($pedidoId, $prendaId)
    {
        try {
            $procesoReciente = DB::table('pedidos_procesos_prenda_detalles')
                ->where('pedido_produccion_id', $pedidoId)
                ->where('prenda_pedido_id', $prendaId)
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($procesoReciente && $procesoReciente->tipo_proceso_id) {
                $tipoProceso = DB::table('tipos_procesos')->where('id', $procesoReciente->tipo_proceso_id)->first();
                return $tipoProceso ? $tipoProceso->nombre : 'Sin área';
            }

            return 'Sin procesos';
        } catch (\Exception $e) {
            \Log::warning('Error obteniendo área proceso más reciente', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);
            return 'Error';
        }
    }
}
