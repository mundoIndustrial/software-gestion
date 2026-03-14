<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\ProcesoSeguimiento\DTOs\GuardarProcesoSeguimientoDTO;
use App\Application\ProcesoSeguimiento\UseCases\GuardarProcesoSeguimientoUseCase;
use App\Infrastructure\Http\Requests\GuardarProcesoSeguimientoRequest;
use App\Models\ProcesoPrenda;
use App\Models\PrendaPedido;
use App\Models\ConsecutivoReciboPedido;
use App\Events\OperarioRecibosActualizados;
use App\Events\CorteAsignadoOperario;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ProcesoSeguimientoController extends Controller
{
    public function __construct(
        private readonly GuardarProcesoSeguimientoUseCase $guardarUseCase,
    ) {}
    
    /**
     * Guardar un nuevo proceso de seguimiento para una prenda.
     *
     * La validación vive en GuardarProcesoSeguimientoRequest.
     * La lógica de negocio vive en GuardarProcesoSeguimientoUseCase.
     */
    public function guardar(GuardarProcesoSeguimientoRequest $request): JsonResponse
    {
        try {
            $resultado = $this->guardarUseCase->execute(
                GuardarProcesoSeguimientoDTO::fromRequest($request)
            );

            $proceso = $resultado->proceso;

            // Cargar seguimiento actualizado para la respuesta
            $prendaActualizada = null;
            try {
                $registroController = new \App\Infrastructure\Http\Controllers\RegistroOrdenQueryController();
                $seguimientoResponse = $registroController->getSeguimientoPorPrenda($request->pedido_produccion_id);
                $seguimientoData     = json_decode($seguimientoResponse->getContent(), true);

                foreach ($seguimientoData['prendas'] ?? [] as $prenda) {
                    if ($prenda['id'] == $request->prenda_id) {
                        $prendaActualizada = $prenda;
                        break;
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('[ProcesoSeguimientoController] Error obteniendo seguimiento: ' . $e->getMessage());
            }

            try {
                $proceso->load(['prenda', 'pedido']);
            } catch (\Exception $e) {
                \Log::warning('[ProcesoSeguimientoController] Error cargando relaciones: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Proceso de seguimiento ' . $resultado->accion . ' correctamente',
                'action'  => $resultado->accion,
                'data'    => [
                    'proceso' => $proceso,
                    'prenda'  => $prendaActualizada,
                    'pedido'  => $proceso->pedido ?? null,
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('[ProcesoSeguimientoController] Error en guardar: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el proceso: ' . $e->getMessage(),
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
     * Actualizar un proceso completamente
     */
    public function actualizar(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'area' => 'required|string|max:100',
            'estado' => 'required|in:Pendiente,En Progreso,Completado,Pausado',
            'fecha_inicio' => 'nullable|date',
            'encargado' => 'nullable|string|max:255',
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
            
            $proceso->proceso = $request->area;
            $proceso->estado_proceso = $request->estado;
            
            // Actualizar fecha de inicio si se proporciona
            if ($request->has('fecha_inicio') && $request->fecha_inicio) {
                $proceso->fecha_inicio = $request->fecha_inicio;
            }
            
            $proceso->encargado = $request->encargado;
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

            // Broadcast en actualización: si se asigna/actualiza CORTE, notificar al operario en tiempo real
            try {
                $areaNormalizada = strtolower(trim((string) $request->area));
                if ($areaNormalizada === 'corte') {
                    $encargadoNormalizado = strtolower(trim((string) ($request->encargado ?? '')));

                    \Log::info('[ProcesoSeguimientoController] Broadcast CORTE (actualizar) - inicio', [
                        'broadcasting_default' => config('broadcasting.default'),
                        'area' => (string) $request->area,
                        'encargado' => (string) ($request->encargado ?? ''),
                        'pedido_produccion_id' => (int) ($proceso->numero_pedido ?? 0),
                        'prenda_id' => (int) ($proceso->prenda_pedido_id ?? 0),
                        'proceso_id' => (int) $proceso->id,
                    ]);

                    broadcast(new CorteAsignadoOperario([
                        'area' => (string) $request->area,
                        'accion' => 'actualizado',
                        'numero_pedido' => (int) ($proceso->numero_pedido ?? 0),
                        'prenda_id' => (int) ($proceso->prenda_pedido_id ?? 0),
                        'proceso_id' => (int) $proceso->id,
                        'encargado' => (string) ($request->encargado ?? ''),
                    ]));

                    \Log::info('[ProcesoSeguimientoController] Broadcast CORTE (actualizar) - emitido a canal publico operarios.corte');

                    if ($encargadoNormalizado !== '') {
                        $operario = User::query()
                            ->whereRaw('LOWER(TRIM(name)) = ?', [$encargadoNormalizado])
                            ->first();

                        if ($operario && $operario->hasRole('cortador')) {
                            broadcast(new OperarioRecibosActualizados(
                                userId: (int) $operario->id,
                                payload: [
                                    'area' => (string) $request->area,
                                    'accion' => 'actualizado',
                                    'numero_pedido' => (int) ($proceso->numero_pedido ?? 0),
                                    'prenda_id' => (int) ($proceso->prenda_pedido_id ?? 0),
                                    'proceso_id' => (int) $proceso->id,
                                ]
                            ));

                            \Log::info('[ProcesoSeguimientoController] Broadcast CORTE (actualizar) - emitido a canal privado App.Models.User', [
                                'user_id' => (int) $operario->id,
                            ]);
                        }
                    }
                }

                // Broadcast en actualización: si se asigna/actualiza COSTURA, notificar al operario costura-reflectivo
                if ($areaNormalizada === 'costura') {
                    $encargadoNormalizado = strtolower(trim((string) ($request->encargado ?? '')));
	
                    if ($encargadoNormalizado !== '') {
                        $operario = User::query()
                            ->whereRaw('LOWER(TRIM(name)) = ?', [$encargadoNormalizado])
                            ->first();
	
                        if ($operario && $operario->hasRole('costura-reflectivo')) {
                            broadcast(new OperarioRecibosActualizados(
                                userId: (int) $operario->id,
                                payload: [
                                    'area' => (string) $request->area,
                                    'accion' => 'actualizado',
                                    'numero_pedido' => (int) ($proceso->numero_pedido ?? 0),
                                    'prenda_id' => (int) ($proceso->prenda_pedido_id ?? 0),
                                    'proceso_id' => (int) $proceso->id,
                                ]
                            ));

                            \Log::info('[ProcesoSeguimientoController] Broadcast COSTURA (actualizar) - emitido a canal privado App.Models.User', [
                                'user_id' => (int) $operario->id,
                            ]);
                        }
                    }
                }
            } catch (\Exception $broadcastError) {
                \Log::warning('[ProcesoSeguimientoController] Error broadcasting a operario en actualizar(): ' . $broadcastError->getMessage(), [
                    'file' => $broadcastError->getFile(),
                    'line' => $broadcastError->getLine(),
                ]);
            }

            // Sincronizar el area en consecutivos_recibos_pedidos para esta prenda
            try {
                $prenda = PrendaPedido::find($proceso->prenda_pedido_id);

                if ($prenda && $prenda->pedido_produccion_id) {
                    $consecutivo = ConsecutivoReciboPedido::where('pedido_produccion_id', $prenda->pedido_produccion_id)
                        ->where('prenda_id', $proceso->prenda_pedido_id)
                        ->first();

                    if ($consecutivo) {
                        $datosActualizar = [
                            'area' => $request->area,
                        ];

                        if ($request->area === 'Insumos') {
                            $datosActualizar['estado'] = 'Pendiente_Insumos';
                        }

                        $consecutivo->update($datosActualizar);
                    }
                }
            } catch (\Exception $consecutivoError) {
                \Log::warning('[ProcesoSeguimientoController] Error actualizando consecutivo en actualizar(): ' . $consecutivoError->getMessage());
            }

            \Log::info('[ProcesoSeguimientoController] Proceso actualizado', [
                'proceso_id' => $id,
                'area' => $request->area,
                'estado' => $request->estado,
                'encargado' => $request->encargado
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Proceso actualizado correctamente',
                'data' => $proceso
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al actualizar proceso: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el proceso'
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
            $prendaId = $proceso->prenda_pedido_id;
            $numeroPedido = $proceso->numero_pedido;
            
            // Eliminación definitiva (no soft delete)
            $proceso->forceDelete();

            // Obtener el ID del pedido a través de la prenda
            $prenda = PrendaPedido::find($prendaId);
            
            if (!$prenda || !$prenda->pedido_produccion_id) {
                \Log::warning('[ProcesoSeguimientoController] No se encontró prenda o no tiene pedido asociado al eliminar proceso:', [
                    'prenda_id' => $prendaId
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Proceso eliminado correctamente'
                ]);
            }

            // Buscar el proceso más reciente para la misma prenda (excluyendo el eliminado)
            $procesoMasReciente = ProcesoPrenda::where('prenda_pedido_id', $prendaId)
                ->where('numero_pedido', $numeroPedido)
                ->orderBy('created_at', 'desc')
                ->first();

            // Actualizar el area en consecutivos_recibos_pedidos
            try {
                $consecutivo = ConsecutivoReciboPedido::where('pedido_produccion_id', $prenda->pedido_produccion_id)
                    ->where('prenda_id', $prendaId)
                    ->first();

                if ($consecutivo) {
                    if ($procesoMasReciente) {
                        // Usar el proceso más reciente
                        $consecutivo->update([
                            'area' => $procesoMasReciente->proceso
                        ]);
                        \Log::info('[ProcesoSeguimientoController] Consecutivo actualizado con proceso más reciente:', [
                            'consecutivo_id' => $consecutivo->id,
                            'area' => $procesoMasReciente->proceso,
                            'proceso_id' => $procesoMasReciente->id
                        ]);
                    } else {
                        // Si no hay más procesos, volver a Insumos
                        $consecutivo->update([
                            'area' => 'Insumos',
                            'estado' => 'Pendiente_Insumos',
                        ]);
                        \Log::info('[ProcesoSeguimientoController] Consecutivo actualizado a Insumos (sin procesos restantes):', [
                            'consecutivo_id' => $consecutivo->id
                        ]);
                    }
                } else {
                    \Log::warning('[ProcesoSeguimientoController] No se encontró consecutivo para actualizar al eliminar:', [
                        'pedido_id' => $prenda->pedido_produccion_id,
                        'prenda_id' => $prendaId
                    ]);
                }
            } catch (\Exception $consecutivoError) {
                \Log::warning('[ProcesoSeguimientoController] Error actualizando consecutivo tras eliminar: ' . $consecutivoError->getMessage());
                // Continuar aunque falle la actualización del consecutivo
            }

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
