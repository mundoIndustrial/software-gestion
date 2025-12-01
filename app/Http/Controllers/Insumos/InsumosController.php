<?php

namespace App\Http\Controllers\Insumos;

use App\Http\Controllers\Controller;
use App\Models\TablaOriginal;
use App\Models\MaterialesOrdenInsumos;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

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
            $columnasPermitidas = ['pedido', 'cliente', 'descripcion', 'estado', 'area', 'fecha_de_creacion_de_orden'];
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
            $query = TablaOriginal::where(function($q) {
                // Estados permitidos
                $q->whereIn('estado', ['No iniciado', 'En Ejecución', 'Entregado', 'Anulada']);
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
                    'filtros_aplicados' => 'Estado (No iniciado, En Ejecución, Entregado, Anulada) + Área (Corte, Creación de orden)'
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
     * Control de materiales
     */
    public function materiales(Request $request)
    {
        $startTime = microtime(true);
        \Log::info('📊 INSUMOS: Iniciando carga de materiales');
        
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
        
        // Construir query base - Filtrar por:
        // - Estados: "No iniciado", "En Ejecución", "Entregado", "Anulada"
        // - Áreas: "Corte" o "Creación de orden"
        $baseQuery = TablaOriginal::where(function($q) {
            // Estados permitidos
            $q->whereIn('estado', ['No iniciado', 'En Ejecución', 'Entregado', 'Anulada']);
        })->where(function($q) {
            // Áreas permitidas - Usar LIKE para ser más flexible con espacios/mayúsculas
            $q->where('area', 'LIKE', '%Corte%')
              ->orWhere('area', 'LIKE', '%Creación%orden%')
              ->orWhere('area', 'LIKE', '%Creación de orden%');
        });
        
        // Aplicar múltiples filtros (nuevo sistema)
        $hasFilters = false;
        if (!empty($filterColumns) && !empty($filterValuesArray)) {
            $hasFilters = true;
            \Log::info('🔍 Filtros recibidos:', [
                'filterColumns' => $filterColumns,
                'filterValuesArray' => $filterValuesArray
            ]);
            foreach ($filterColumns as $idx => $column) {
                if (isset($filterValuesArray[$idx])) {
                    $filterValue = $filterValuesArray[$idx];
                    \Log::info("📌 Aplicando filtro: {$column} = {$filterValue}");
                    
                    // Para campos de texto (pedido, cliente, descripcion), usar LIKE
                    if (in_array($column, ['pedido', 'cliente', 'descripcion'])) {
                        $baseQuery->where($column, 'LIKE', "%{$filterValue}%");
                    } elseif ($column === 'fecha_de_creacion_de_orden') {
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
            $baseQuery->whereIn($filterColumn, $filterValues);
        }
        
        // Aplicar búsqueda si existe
        if (!empty($search)) {
            $hasFilters = true;
            $baseQuery->where(function($q) use ($search) {
                $q->where('pedido', 'LIKE', "%{$search}%")
                  ->orWhere('cliente', 'LIKE', "%{$search}%");
            });
        }
        
        // Siempre paginar, con o sin filtros
        $ordenes = $baseQuery->orderBy('pedido', 'asc')->paginate(10);
        
        // Preservar parámetros de búsqueda y filtro en links de paginación
        $ordenes->appends($request->query());
        
        // Cargar materiales guardados para cada orden
        $ordenesConMateriales = $ordenes->map(function($orden) {
            $materialesGuardados = MaterialesOrdenInsumos::where('tabla_original_pedido', $orden->pedido)->get();
            $orden->materiales_guardados = $materialesGuardados;
            return $orden;
        });
        
        $queryTime = microtime(true) - $queryStart;
        \Log::info("⏱️ Consulta BD: {$queryTime}s, Total: " . $ordenes->total() . ", Búsqueda: '{$search}'");
        
        $viewStart = microtime(true);
        $response = view('insumos.materiales.index', [
            'ordenes' => $ordenes,
            'user' => $user,
            'search' => $search,
        ]);
        $viewTime = microtime(true) - $viewStart;
        \Log::info("⏱️ Render vista: {$viewTime}s");
        
        $totalTime = microtime(true) - $startTime;
        \Log::info("✅ Total carga: {$totalTime}s");
        
        return $response;
    }

    /**
     * Verificar que el usuario tenga rol insumos o supervisor_planta
     */
    private function verificarRolInsumos($user)
    {
        // Cargar la relación de rol si no está cargada
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }

        $isInsumos = $user->role === 'insumos' ||
                    (is_object($user->role) && $user->role->name === 'insumos');

        $isSupervisorPlanta = $user->role === 'supervisor_planta' ||
                             (is_object($user->role) && $user->role->name === 'supervisor_planta');

        if (!$isInsumos && !$isSupervisorPlanta) {
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
            
            // Validar que la orden existe
            $orden = TablaOriginal::where('pedido', $ordenId)->firstOrFail();
            
            // Validar datos
            $validated = $request->validate([
                'materiales' => 'array',
                'materiales.*.nombre' => 'required|string',
                'materiales.*.fecha_pedido' => 'nullable|date',
                'materiales.*.fecha_llegada' => 'nullable|date',
                'materiales.*.recibido' => 'boolean',
            ]);
            
            // Si materiales no viene en el request, usar array vacío
            if (!isset($validated['materiales'])) {
                $validated['materiales'] = [];
            }
            
            // Guardar o eliminar materiales según el estado del checkbox
            $materialesGuardados = 0;
            $materialesEliminados = 0;
            
            \Log::info('🔵 GUARDANDO MATERIALES - Pedido: ' . $orden->pedido);
            \Log::info('📋 Materiales recibidos:', $validated['materiales']);
            \Log::info('📊 Total de materiales: ' . count($validated['materiales']));
            
            foreach ($validated['materiales'] as $material) {
                $isRecibido = $material['recibido'] === true || $material['recibido'] === 'true' || $material['recibido'] === 1 || $material['recibido'] === '1';
                
                \Log::info("🔍 Procesando material: {$material['nombre']}, recibido: {$material['recibido']}, isRecibido: " . ($isRecibido ? 'true' : 'false'));
                
                if ($isRecibido) {
                    // Guardar/actualizar si recibido es true
                    $result = MaterialesOrdenInsumos::updateOrCreate(
                        [
                            'tabla_original_pedido' => $orden->pedido,
                            'nombre_material' => $material['nombre'],
                        ],
                        [
                            'fecha_pedido' => $material['fecha_pedido'] ?? null,
                            'fecha_llegada' => $material['fecha_llegada'] ?? null,
                            'recibido' => true,
                        ]
                    );
                    $materialesGuardados++;
                    \Log::info("✅ Material guardado: {$material['nombre']}, ID: {$result->id}, Fecha Pedido: {$material['fecha_pedido']}, Fecha Llegada: {$material['fecha_llegada']}");
                } else {
                    // Eliminar si recibido es false
                    $deleted = MaterialesOrdenInsumos::where([
                        'tabla_original_pedido' => $orden->pedido,
                        'nombre_material' => $material['nombre'],
                    ])->delete();
                    
                    if ($deleted > 0) {
                        $materialesEliminados++;
                        \Log::info("🗑️ Material eliminado: {$material['nombre']}");
                    } else {
                        \Log::info("⚠️ No se encontró material para eliminar: {$material['nombre']}");
                    }
                }
            }
            
            \Log::info("📊 Resumen: Guardados: $materialesGuardados, Eliminados: $materialesEliminados");
            
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
            return response()->json([
                'success' => false,
                'message' => 'Error de validación: ' . implode(', ', array_reduce($e->errors(), 'array_merge', []))
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
            
            // Validar que la orden existe
            $orden = TablaOriginal::where('pedido', $ordenId)->firstOrFail();
            
            // Validar datos
            $validated = $request->validate([
                'nombre_material' => 'required|string',
            ]);
            
            // Eliminar el material
            $deleted = MaterialesOrdenInsumos::where([
                'tabla_original_pedido' => $orden->pedido,
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
            
            // Validar que la orden existe
            $orden = TablaOriginal::where('pedido', $pedido)->firstOrFail();
            
            // Obtener materiales guardados
            $materiales = MaterialesOrdenInsumos::where('tabla_original_pedido', $pedido)->get();
            
            // Transformar los datos para la respuesta
            $materialesTransformados = $materiales->map(function($material) {
                return [
                    'nombre_material' => $material->nombre_material,
                    'recibido' => $material->recibido,
                    'fecha_pedido' => $material->fecha_pedido ? $material->fecha_pedido->format('Y-m-d') : null,
                    'fecha_llegada' => $material->fecha_llegada ? $material->fecha_llegada->format('Y-m-d') : null,
                    'dias_demora' => $material->dias_demora,
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
}
