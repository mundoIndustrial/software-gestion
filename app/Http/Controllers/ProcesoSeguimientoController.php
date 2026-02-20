<?php

namespace App\Http\Controllers;

use App\Models\ProcesoPrenda;
use App\Models\PrendaPedido;
use App\Models\PedidoProduccion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ProcesoSeguimientoController extends Controller
{
    public function __construct()
    {
        \Log::info('[ProcesoSeguimientoController] Constructor ejecutado');
    }
    
    /**
     * Guardar un nuevo proceso de seguimiento para una prenda
     */
    public function guardar(Request $request): JsonResponse
    {
        \Log::info('[ProcesoSeguimientoController] MÉTODO GUARDAR - INICIO');
        
        // Verificar si hay sesión activa (sin usar guards específicos)
        if (!session()->has('user_id') && !auth()->check()) {
            \Log::error('[ProcesoSeguimientoController] No hay sesión activa');
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }
        
        // Intentar obtener usuario autenticado
        $user = auth()->user();
        if (!$user) {
            \Log::error('[ProcesoSeguimientoController] No se pudo obtener usuario autenticado');
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }
        
        \Log::info('[ProcesoSeguimientoController] Usuario autenticado: ' . $user->name);
        
        // Debug: Ver qué datos recibimos
        \Log::info('[ProcesoSeguimientoController] INICIO - Request recibido');
        \Log::info('[ProcesoSeguimientoController] Método: ' . $request->method());
        \Log::info('[ProcesoSeguimientoController] URI: ' . $request->getRequestUri());
        \Log::info('[ProcesoSeguimientoController] Datos recibidos:', $request->all());
        \Log::info('[ProcesoSeguimientoController] Usuario autenticado:', ['user_id' => auth()->id(), 'user' => auth()->user()?->name]);
        
        $validator = Validator::make($request->all(), [
            'pedido_produccion_id' => 'required|integer|exists:pedidos_produccion,numero_pedido',
            'prenda_id' => 'required|integer|exists:prendas_pedido,id',
            'area' => 'required|string|max:255',
            'estado' => 'required|in:Pendiente,En Progreso,Completado,Pausado',
            'encargado' => 'required|string|max:100',
            'observaciones' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Debug: Ver datos antes de crear
            \Log::info('[ProcesoSeguimientoController] Creando proceso con datos:', [
                'pedido_produccion_id' => $request->pedido_produccion_id,
                'prenda_id' => $request->prenda_id,
                'area' => $request->area,
                'estado' => $request->estado,
                'encargado' => $request->encargado,
                'observaciones' => $request->observaciones,
            ]);

            // Generar código de referencia único
            $codigoReferencia = $this->generarCodigoReferencia($request->area, $request->prenda_id);
            \Log::info('[ProcesoSeguimientoController] Código generado:', ['codigo_referencia' => $codigoReferencia]);

            // Crear el proceso de seguimiento
            $proceso = ProcesoPrenda::create([
                'numero_pedido' => $request->pedido_produccion_id,
                'prenda_pedido_id' => $request->prenda_id,
                'proceso' => $request->area,
                'fecha_inicio' => now(),
                'estado_proceso' => $request->estado,
                'encargado' => $request->encargado,
                'observaciones' => $request->observaciones,
                'codigo_referencia' => $codigoReferencia,
            ]);

            \Log::info('[ProcesoSeguimientoController] Proceso creado:', ['proceso_id' => $proceso->id]);

            // Cargar relaciones para la respuesta
            $proceso->load(['prenda', 'pedido']);

            return response()->json([
                'success' => true,
                'message' => 'Proceso de seguimiento guardado correctamente',
                'data' => [
                    'proceso' => $proceso,
                    'prenda' => $proceso->prenda,
                    'pedido' => $proceso->pedido
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al guardar proceso de seguimiento: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el proceso: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener todos los procesos de seguimiento de una prenda
     */
    public function obtenerPorPrenda($prendaId): JsonResponse
    {
        try {
            $procesos = ProcesoPrenda::where('prenda_pedido_id', $prendaId)
                ->with(['prenda', 'pedido'])
                ->orderBy('created_at', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $procesos
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al obtener procesos de seguimiento: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los procesos'
            ], 500);
        }
    }

    /**
     * Actualizar el estado de un proceso
     */
    public function actualizarEstado(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'estado' => 'required|in:Pendiente,En Progreso,Completado,Pausado',
            'observaciones' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $proceso = ProcesoPrenda::findOrFail($id);
            
            $proceso->estado_proceso = $request->estado;
            $proceso->observaciones = $request->observaciones;

            // Si se completa, registrar fecha de fin
            if ($request->estado === 'Completado' && !$proceso->fecha_fin) {
                $proceso->fecha_fin = now();
                
                // Calcular días de duración
                if ($proceso->fecha_inicio) {
                    $dias = $proceso->fecha_inicio->diffInDays(now());
                    $proceso->dias_duracion = $dias > 0 ? $dias . ' días' : 'Menos de 1 día';
                }
            }

            $proceso->save();
            $proceso->load(['prenda', 'pedido']);

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'data' => [
                    'proceso' => $proceso,
                    'prenda' => $proceso->prenda,
                    'pedido' => $proceso->pedido
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al actualizar estado del proceso: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estado'
            ], 500);
        }
    }

    /**
     * Eliminar un proceso de seguimiento
     */
    public function eliminar($id): JsonResponse
    {
        try {
            $proceso = ProcesoPrenda::findOrFail($id);
            $proceso->delete();

            return response()->json([
                'success' => true,
                'message' => 'Proceso eliminado correctamente'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al eliminar proceso: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el proceso'
            ], 500);
        }
    }

    /**
     * Generar código de referencia único para el proceso
     */
    private function generarCodigoReferencia(string $area, int $prendaId): string
    {
        // Obtener las primeras 3 letras del área
        $areaAbrev = strtoupper(substr($area, 0, 3));
        
        // Obtener el ID de la prenda y formatearlo a 4 dígitos
        $prendaIdFormateado = str_pad($prendaId, 4, '0', STR_PAD_LEFT);
        
        // Generar número secuencial simple (basado en timestamp)
        $secuencial = date('His');
        
        return $areaAbrev . '-' . $prendaIdFormateado . '-' . $secuencial;
    }

    /**
     * Obtener estadísticas de seguimiento de una prenda
     */
    public function estadisticas($prendaId): JsonResponse
    {
        try {
            $procesos = ProcesoPrenda::where('prenda_pedido_id', $prendaId)->get();
            
            $estadisticas = [
                'total' => $procesos->count(),
                'pendientes' => $procesos->where('estado_proceso', 'Pendiente')->count(),
                'en_progreso' => $procesos->where('estado_proceso', 'En Progreso')->count(),
                'completados' => $procesos->where('estado_proceso', 'Completado')->count(),
                'pausados' => $procesos->where('estado_proceso', 'Pausado')->count(),
                'tiempo_promedio_dias' => $this->calcularTiempoPromedio($procesos),
                'proceso_actual' => $this->obtenerProcesoActual($procesos)
            ];

            return response()->json([
                'success' => true,
                'data' => $estadisticas
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al obtener estadísticas: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas'
            ], 500);
        }
    }

    /**
     * Calcular tiempo promedio en días de los procesos completados
     */
    private function calcularTiempoPromedio($procesos): float
    {
        $procesosCompletados = $procesos->where('estado_proceso', 'Completado')
            ->whereNotNull('fecha_inicio')
            ->whereNotNull('fecha_fin');

        if ($procesosCompletados->isEmpty()) {
            return 0;
        }

        $totalDias = $procesosCompletados->sum(function ($proceso) {
            return $proceso->fecha_inicio->diffInDays($proceso->fecha_fin);
        });

        return round($totalDias / $procesosCompletados->count(), 2);
    }

    /**
     * Obtener el proceso más reciente o actual
     */
    private function obtenerProcesoActual($procesos): ?ProcesoPrenda
    {
        return $procesos
            ->whereIn('estado_proceso', ['Pendiente', 'En Progreso'])
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
