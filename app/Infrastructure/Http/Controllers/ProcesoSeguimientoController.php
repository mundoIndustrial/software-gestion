<?php

namespace App\Infrastructure\Http\Controllers;

use App\Application\ProcesoSeguimiento\DTOs\GuardarProcesoSeguimientoDTO;
use App\Application\ProcesoSeguimiento\DTOs\ActualizarProcesoSeguimientoDTO;
use App\Application\ProcesoSeguimiento\UseCases\GuardarProcesoSeguimientoUseCase;
use App\Application\ProcesoSeguimiento\UseCases\ActualizarProcesoSeguimientoUseCase;
use App\Application\ProcesoSeguimiento\UseCases\ActualizarEstadoProcesoUseCase;
use App\Application\ProcesoSeguimiento\UseCases\EliminarProcesoSeguimientoUseCase;
use App\Infrastructure\Http\Requests\GuardarProcesoSeguimientoRequest;
use App\Infrastructure\Http\Requests\ActualizarProcesoSeguimientoRequest;
use App\Infrastructure\Http\Requests\ActualizarEstadoProcesoRequest;
use App\Models\ProcesoPrenda;
use Illuminate\Http\JsonResponse;

class ProcesoSeguimientoController extends Controller
{
    public function __construct(
        private readonly GuardarProcesoSeguimientoUseCase    $guardarUseCase,
        private readonly ActualizarProcesoSeguimientoUseCase $actualizarUseCase,
        private readonly ActualizarEstadoProcesoUseCase      $actualizarEstadoUseCase,
        private readonly EliminarProcesoSeguimientoUseCase   $eliminarUseCase,
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
     * Actualizar un proceso completamente.
     */
    public function actualizar(ActualizarProcesoSeguimientoRequest $request, int $id): JsonResponse
    {
        try {
            $proceso = $this->actualizarUseCase->execute(
                ActualizarProcesoSeguimientoDTO::fromRequest($request, $id)
            );

            return response()->json([
                'success' => true,
                'message' => 'Proceso actualizado correctamente',
                'data'    => $proceso,
            ]);

        } catch (\Exception $e) {
            \Log::error('[ProcesoSeguimientoController] Error en actualizar: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el proceso',
            ], 500);
        }
    }

    /**
     * Actualizar únicamente el estado de un proceso.
     */
    public function actualizarEstado(ActualizarEstadoProcesoRequest $request, int $id): JsonResponse
    {
        try {
            $proceso = $this->actualizarEstadoUseCase->execute(
                procesoId:     $id,
                estado:        (string) $request->estado,
                observaciones: $request->observaciones,
            );

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente',
                'data'    => [
                    'proceso' => $proceso,
                    'prenda'  => $proceso->prenda,
                    'pedido'  => $proceso->pedido,
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('[ProcesoSeguimientoController] Error en actualizarEstado: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estado',
            ], 500);
        }
    }

    /**
     * Eliminar un proceso de seguimiento.
     */
    public function eliminar(int $id): JsonResponse
    {
        try {
            $this->eliminarUseCase->execute($id);

            return response()->json([
                'success' => true,
                'message' => 'Proceso eliminado correctamente',
            ]);

        } catch (\Exception $e) {
            \Log::error('[ProcesoSeguimientoController] Error en eliminar: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el proceso',
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
