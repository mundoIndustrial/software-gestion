<?php

namespace App\Http\Controllers\Insumos;

use App\Http\Controllers\Controller;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\MaterialesOrdenInsumos;
use App\Models\PedidoAnchoMetraje;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class InsumosController extends Controller
{
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
     * Obtener valores únicos de una columna para filtros
     */
    public function obtenerValoresFiltro($column)
    {
        try {
            $user = Auth::user();
            $this->verificarRolInsumos($user);
            
            // Validar que la columna sea permitida
            $columnasPermitidas = ['numero_pedido', 'cliente', 'estado', 'area', 'fecha_de_creacion_de_orden'];
            if (!in_array($column, $columnasPermitidas)) {
                \Log::warning('Columna no permitida en filtro: ' . $column);
                return response()->json([
                    'success' => false,
                    'message' => 'Columna no permitida',
                    'column' => $column
                ], 400);
            }
            
            // Obtener valores únicos de la columna especificada
            // Usar la misma query base que en materiales() - Filtrar por Estados y Áreas permitidas
            $query = PedidoProduccion::where(function($q) {
                // Estados permitidos
                $q->whereIn('estado', ['Pendiente', 'No iniciado', 'En Ejecución', 'Anulada', 'PENDIENTE_INSUMOS']);
            })->where(function($q) {
                // Áreas permitidas
                $q->where('area', 'LIKE', '%Corte%')
                  ->orWhere('area', 'LIKE', '%Creación%orden%')
                  ->orWhere('area', 'LIKE', '%Creación de orden%');
            });
            
            // Obtener valores únicos
            if ($column === 'fecha_de_creacion_de_orden') {
                // Para fechas, obtener primero y luego formatear
                $allRecords = $query->get();
                $totalRegistros = $allRecords->count();
                
                \Log::info('📅 FILTRO FECHA - Registros totales encontrados:', [
                    'total_registros' => $totalRegistros,
                    'filtros_aplicados' => 'Estado (Pendiente, No iniciado, En Ejecución, Anulada, PENDIENTE_INSUMOS)'
                ]);
                
                $valores = $allRecords
                    ->pluck($column)
                    ->map(function($value) {
                        if ($value) {
                            // Si es un objeto Carbon, formatear a string
                            if (is_object($value) && method_exists($value, 'format')) {
                                return $value->format('d/m/Y');
                            }
                            // Si es string, intentar convertir de Y-m-d a d/m/Y
                            $strValue = trim((string)$value);
                            try {
                                // Intentar parsear como fecha Y-m-d
                                if (preg_match('/^\d{4}-\d{2}-\d{2}/', $strValue)) {
                                    $fecha = \Carbon\Carbon::createFromFormat('Y-m-d', substr($strValue, 0, 10));
                                    return $fecha->format('d/m/Y');
                                }
                            } catch (\Exception $e) {
                                // Si falla, retornar como está
                            }
                            return $strValue;
                        }
                        return null;
                    })
                    ->filter(function($value) {
                        return !empty($value);
                    })
                    ->unique()
                    ->sort()
                    ->values()
                    ->toArray();
                    
                \Log::info('📅 FILTRO FECHA - Valores únicos obtenidos:', [
                    'total_valores_unicos' => count($valores),
                    'primeros_5' => array_slice($valores, 0, 5),
                    'ultimos_5' => array_slice($valores, -5)
                ]);
            } else {
                // Para otras columnas
                $valores = $query->distinct()
                    ->orderBy($column, 'asc')
                    ->pluck($column)
                    ->filter(function($value) {
                        return !empty($value);
                    })
                    ->map(function($value) {
                        // Convertir PENDIENTE_INSUMOS a "Pendiente Insumos" para el filtro de estado
                        if ($value === 'PENDIENTE_INSUMOS') {
                            return 'Pendiente Insumos';
                        }
                        return $value;
                    })
                    ->values()
                    ->toArray();
            }
            
            \Log::info('Valores de filtro obtenidos:', [
                'column' => $column,
                'total' => count($valores),
                'valores' => array_slice($valores, 0, 5) // Mostrar solo los primeros 5 en logs
            ]);
            
            return response()->json([
                'success' => true,
                'column' => $column,
                'valores' => $valores,
                'total' => count($valores)
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener valores de filtro:', [
                'column' => $column,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener valores: ' . $e->getMessage(),
                'column' => $column
            ], 500);
        }
    }

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
        
        // Obtener parámetro de búsqueda
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
        
        \Log::info('📥 PARÁMETROS RECIBIDOS:', [
            'all_params' => $request->all(),
            'filterColumns' => $filterColumns,
            'filterValuesArray' => $filterValuesArray,
            'filterColumn' => $filterColumn,
            'filterValues' => $filterValues,
            'search' => $search
        ]);
        
        // CAMBIO PRINCIPAL: Obtener recibos de costura en lugar de pedidos
        $baseQuery = DB::table('consecutivos_recibos_pedidos')
            ->where('tipo_recibo', 'COSTURA')
            ->where('activo', 1)
            ->join('pedidos_produccion', 'consecutivos_recibos_pedidos.pedido_produccion_id', '=', 'pedidos_produccion.id')
            ->select(
                'consecutivos_recibos_pedidos.*',
                'pedidos_produccion.numero_pedido',
                'pedidos_produccion.numero_pedido as numero_pedido_original',
                'pedidos_produccion.cliente',
                'pedidos_produccion.estado',
                'pedidos_produccion.area',
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
        
        // Aplicar múltiples filtros (nuevo sistema)
        $hasFilters = false;
        if (!empty($filterColumns) && !empty($filterValuesArray)) {
            $hasFilters = true;
            \Log::info(' Filtros recibidos:', [
                'filterColumns' => $filterColumns,
                'filterValuesArray' => $filterValuesArray
            ]);
            foreach ($filterColumns as $idx => $column) {
                if (isset($filterValuesArray[$idx])) {
                    $filterValue = $filterValuesArray[$idx];
                    \Log::info("📌 Aplicando filtro: {$column} = {$filterValue}");
                    
                    // Convertir "Pendiente Insumos" a "PENDIENTE_INSUMOS" para el filtro de estado
                    if ($column === 'estado' && $filterValue === 'Pendiente Insumos') {
                        $filterValue = 'PENDIENTE_INSUMOS';
                    }
                    
                    // Mapear columnas para recibos
                    if ($column === 'numero_pedido') {
                        $column = 'pedidos_produccion.numero_pedido';
                    } elseif ($column === 'cliente') {
                        $column = 'pedidos_produccion.cliente';
                    } elseif ($column === 'estado') {
                        $column = 'pedidos_produccion.estado';
                    } elseif ($column === 'area') {
                        $column = 'pedidos_produccion.area';
                    } elseif ($column === 'fecha_de_creacion_de_orden') {
                        $column = 'pedidos_produccion.fecha_de_creacion_de_orden';
                    }
                    
                    // Para campos de texto, usar LIKE
                    if (in_array($column, ['pedidos_produccion.numero_pedido', 'pedidos_produccion.cliente'])) {
                        $baseQuery->where($column, 'LIKE', "%{$filterValue}%");
                    } elseif ($column === 'pedidos_produccion.fecha_de_creacion_de_orden') {
                        // Para fechas, convertir de d/m/Y a Y-m-d
                        try {
                            $fecha = \Carbon\Carbon::createFromFormat('d/m/Y', $filterValue);
                            $baseQuery->whereDate($column, $fecha->format('Y-m-d'));
                        } catch (\Exception $e) {
                            \Log::warning("Error al convertir fecha: {$filterValue}", ['error' => $e->getMessage()]);
                        }
                    } else {
                        // Para otros campos, usar whereIn
                        $baseQuery->whereIn($column, [$filterValue]);
                    }
                }
            }
        }
        // Fallback para filtro antiguo (singular)
        elseif (!empty($filterColumn) && !empty($filterValues)) {
            $hasFilters = true;
            // Convertir "Pendiente Insumos" a "PENDIENTE_INSUMOS" para el filtro de estado
            if ($filterColumn === 'estado') {
                $filterValues = array_map(function($value) {
                    return $value === 'Pendiente Insumos' ? 'PENDIENTE_INSUMOS' : $value;
                }, $filterValues);
            }
            
            // Mapear columna
            $mappedColumn = 'pedidos_produccion.' . $filterColumn;
            $baseQuery->whereIn($mappedColumn, $filterValues);
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
                'estado' => $recibo->estado,
                'area' => $recibo->area, // Usar directamente el campo area del pedido
                'fecha_de_creacion_de_orden' => $recibo->fecha_de_creacion_de_orden,
                'dia_de_entrega' => $recibo->dia_de_entrega,
                'fecha_estimada_de_entrega' => $recibo->fecha_estimada_de_entrega,
                'dias_calculados' => $diasCalculados,
                'pedido_produccion_id' => $recibo->pedido_produccion_id,
                'prenda_id' => $recibo->prenda_id,
                'consecutivo_actual' => $recibo->consecutivo_actual,
                'tipo_recibo' => $recibo->tipo_recibo,
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
            ]);
            
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
                    $result = MaterialesOrdenInsumos::updateOrCreate(
                        [
                            'numero_pedido' => $orden->numero_pedido,
                            'nombre_material' => $material['nombre'],
                        ],
                        [
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
                    \Log::info(" Material guardado: {$material['nombre']}, ID: {$result->id}, Fecha Pedido: {$material['fecha_pedido']}, Fecha Llegada: {$material['fecha_llegada']}");
                } else {
                    // Eliminar si recibido es false
                    $deleted = MaterialesOrdenInsumos::where([
                        'numero_pedido' => $orden->numero_pedido,
                        'nombre_material' => $material['nombre'],
                    ])->delete();
                    
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
            
            // Obtener materiales guardados usando numero_pedido
            $materiales = MaterialesOrdenInsumos::where('numero_pedido', $pedido)->get();
            
            // Transformar los datos para la respuesta
            $materialesTransformados = $materiales->map(function($material) {
                return [
                    'id' => $material->id,
                    'nombre_material' => $material->nombre_material,
                    'recibido' => $material->recibido,
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

            // Buscar el registro de ancho y metraje
            $anchoMetraje = \App\Models\PedidoAnchoMetraje::where('pedido_produccion_id', $pedido->id)
                ->first();

            if (!$anchoMetraje) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'ancho' => null,
                        'metraje' => null
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'ancho' => $anchoMetraje->ancho,
                    'metraje' => $anchoMetraje->metraje
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
     * Guardar ancho y metraje de un pedido
     */
    public function guardarAnchoMetraje(Request $request, $numeroPedido)
    {
        try {
            $user = Auth::user();
            $this->verificarRolInsumos($user);
            
            // Validar datos de entrada
            $validated = $request->validate([
                'ancho' => 'required|numeric|min:0.01',
                'metraje' => 'required|numeric|min:0.01',
            ], [
                'ancho.required' => 'El ancho es requerido',
                'ancho.numeric' => 'El ancho debe ser un número',
                'ancho.min' => 'El ancho debe ser mayor que 0',
                'metraje.required' => 'El metraje es requerido',
                'metraje.numeric' => 'El metraje debe ser un número',
                'metraje.min' => 'El metraje debe ser mayor que 0',
            ]);

            // Obtener el pedido por número
            $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)
                ->firstOrFail();

            // Buscar si ya existe un registro para este pedido
            $anchoMetraje = PedidoAnchoMetraje::firstOrNew(
                ['pedido_produccion_id' => $pedido->id]
            );

            // Actualizar valores
            $anchoMetraje->ancho = $validated['ancho'];
            $anchoMetraje->metraje = $validated['metraje'];
            $anchoMetraje->creado_por = $anchoMetraje->creado_por ?? $user->id;
            $anchoMetraje->actualizado_por = $user->id;

            // Guardar
            $anchoMetraje->save();

            \Log::info(' Ancho y metraje guardado', [
                'numero_pedido' => $numeroPedido,
                'pedido_id' => $pedido->id,
                'ancho' => $validated['ancho'],
                'metraje' => $validated['metraje'],
                'usuario_id' => $user->id,
                'usuario_nombre' => $user->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ancho y metraje guardados correctamente',
                'data' => [
                    'ancho' => $anchoMetraje->ancho,
                    'metraje' => $anchoMetraje->metraje,
                    'actualizado_en' => $anchoMetraje->updated_at,
                ]
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
    public function obtenerAnchoMetrajePrenda($numeroPedido, $prendaId)
    {
        try {
            $user = Auth::user();
            $this->verificarRolInsumos($user);
            
            // Buscar el pedido por número para obtener el ID
            $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();
            
            // Buscar si ya existe un registro para esta prenda
            $anchoMetraje = \App\Models\PedidoAnchoMetraje::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_pedido_id', $prendaId)
                ->first();
            
            if ($anchoMetraje) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'ancho' => $anchoMetraje->ancho,
                        'metraje' => $anchoMetraje->metraje,
                        'prenda_id' => $anchoMetraje->prenda_pedido_id
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'data' => null
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('Error al obtener ancho/metraje de prenda: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ancho/metraje de prenda'
            ], 500);
        }
    }

    /**
     * Guardar ancho y metraje con relación a una prenda específica
     */
    public function guardarAnchoMetrajePrenda(Request $request, $numeroPedido)
    {
        try {
            $user = Auth::user();
            $this->verificarRolInsumos($user);
            
            // Validar datos
            $validated = $request->validate([
                'prenda_id' => 'required|integer|exists:prendas_pedido,id',
                'ancho' => 'required|numeric|min:0',
                'metraje' => 'required|numeric|min:0'
            ]);
            
            // Buscar el pedido por número para obtener el ID
            $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();
            
            // Buscar si ya existe un registro para esta prenda
            $anchoMetraje = \App\Models\PedidoAnchoMetraje::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_pedido_id', $validated['prenda_id'])
                ->first();
            
            if ($anchoMetraje) {
                // Actualizar registro existente
                $anchoMetraje->update([
                    'ancho' => $validated['ancho'],
                    'metraje' => $validated['metraje'],
                    'actualizado_por' => $user->id,
                    'updated_at' => now()
                ]);
            } else {
                // Crear nuevo registro
                $anchoMetraje = \App\Models\PedidoAnchoMetraje::create([
                    'pedido_produccion_id' => $pedido->id,
                    'prenda_pedido_id' => $validated['prenda_id'],
                    'ancho' => $validated['ancho'],
                    'metraje' => $validated['metraje'],
                    'creado_por' => $user->id,
                    'actualizado_por' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            
            \Log::info("Ancho y metraje guardados para pedido {$numeroPedido}, prenda {$validated['prenda_id']}: {$validated['ancho']}m x {$validated['metraje']}m", [
                'usuario_id' => $user->id,
                'usuario_nombre' => $user->name
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Ancho y metraje guardados correctamente'
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación: ' . $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al guardar ancho y metraje de prenda: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar ancho y metraje de prenda'
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

            // Obtener recibos de costura activos, excluyendo pedidos con estado PENDIENTE_SUPERVISOR
            $recibosCostura = DB::table('consecutivos_recibos_pedidos')
                ->where('tipo_recibo', 'COSTURA')
                ->where('activo', 1)
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
