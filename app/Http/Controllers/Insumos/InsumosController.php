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
        
        // Construir query base
        $baseQuery = TablaOriginal::where(function($q) {
            $q->where('area', 'Insumos')
              ->orWhere('estado', '!=', 'Anulada');
        });
        
        // Aplicar búsqueda si existe
        if (!empty($search)) {
            $baseQuery->where(function($q) use ($search) {
                $q->where('pedido', 'LIKE', "%{$search}%")
                  ->orWhere('cliente', 'LIKE', "%{$search}%");
            });
            // Si hay búsqueda, mostrar TODOS los resultados con paginación de 5, ordenados por pedido ascendente
            $ordenes = $baseQuery->orderBy('pedido', 'asc')->paginate(5);
        } else {
            // Si no hay búsqueda, mostrar solo 5 órdenes por defecto, ordenados por pedido ascendente
            $ordenes = $baseQuery->orderBy('pedido', 'asc')->paginate(5);
        }
        
        // Preservar parámetro de búsqueda en links de paginación
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
     * Verificar que el usuario tenga rol insumos
     */
    private function verificarRolInsumos($user)
    {
        $isInsumos = $user->role === 'insumos' || 
                    (is_object($user->role) && $user->role->name === 'insumos');
        
        if (!$isInsumos) {
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
                'materiales' => 'required|array',
                'materiales.*.nombre' => 'required|string',
                'materiales.*.fecha_pedido' => 'nullable|date',
                'materiales.*.fecha_llegada' => 'nullable|date',
                'materiales.*.recibido' => 'boolean',
            ]);
            
            // Guardar o eliminar materiales según el estado del checkbox
            $materialesGuardados = 0;
            $materialesEliminados = 0;
            
            \Log::info('Materiales recibidos para guardar:', $validated['materiales']);
            
            foreach ($validated['materiales'] as $material) {
                $isRecibido = $material['recibido'] === true || $material['recibido'] === 'true' || $material['recibido'] === 1 || $material['recibido'] === '1';
                
                \Log::info("Procesando material: {$material['nombre']}, recibido: {$material['recibido']}, isRecibido: " . ($isRecibido ? 'true' : 'false'));
                
                if ($isRecibido) {
                    // Guardar/actualizar si recibido es true
                    MaterialesOrdenInsumos::updateOrCreate(
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
                    \Log::info("Material guardado: {$material['nombre']}");
                } else {
                    // Eliminar si recibido es false
                    $deleted = MaterialesOrdenInsumos::where([
                        'tabla_original_pedido' => $orden->pedido,
                        'nombre_material' => $material['nombre'],
                    ])->delete();
                    
                    if ($deleted > 0) {
                        $materialesEliminados++;
                        \Log::info("Material eliminado: {$material['nombre']}");
                    } else {
                        \Log::info("No se encontró material para eliminar: {$material['nombre']}");
                    }
                }
            }
            
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
                    : 'No hay cambios para guardar',
            ]);
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
}
