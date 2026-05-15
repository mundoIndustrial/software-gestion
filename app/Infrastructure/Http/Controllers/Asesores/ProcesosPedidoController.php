<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Pedidos\UseCases\CrearProcesoUseCase;
use App\Application\Pedidos\UseCases\EditarProcesoUseCase;
use App\Application\Pedidos\UseCases\EliminarProcesoUseCase;
use App\Application\Pedidos\UseCases\ObtenerProcesosPorPedidoUseCase;
use App\Infrastructure\Http\Requests\Asesores\CrearProcesoPedidoRequest;
use App\Infrastructure\Http\Requests\Asesores\EditarProcesoPedidoRequest;
use App\Infrastructure\Http\Requests\Asesores\EliminarProcesoPedidoRequest;
use App\Infrastructure\Http\Requests\Asesores\ObtenerProcesosPedidoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ProcesosPedidoController
 *
 * Responsabilidad: Gestionar procesos asociados a pedidos de produccion.
 */
class ProcesosPedidoController
{
    public function __construct(
        private readonly ObtenerProcesosPorPedidoUseCase $obtenerProcesosPedidoUseCase,
        private readonly CrearProcesoUseCase $crearProcesoUseCase,
        private readonly EditarProcesoUseCase $editarProcesoUseCase,
        private readonly EliminarProcesoUseCase $eliminarProcesoUseCase,
    ) {
    }

    private function json(mixed $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status);
    }

    private function failure(string $message, int $status = 500, array $extra = []): JsonResponse
    {
        return $this->json(array_merge([
            'success' => false,
            'message' => $message,
        ], $extra), $status);
    }

    /**
     * GET /api/ordenes/{id}/procesos
     */
    public function getProcesos(ObtenerProcesosPedidoRequest $request, int|string $id): JsonResponse
    {
        try {
            $prendaId = $request->validated('prenda_id');
            Log::info('[ProcesosPedidoController] GET /procesos', [
                'id' => $id,
                'prenda_id' => $prendaId,
            ]);

            $resultado = $this->obtenerProcesosPedidoUseCase->ejecutar($id, $prendaId);

            return $this->json($resultado['procesos'] ?? [], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('[ProcesosPedidoController] Pedido no encontrado', ['id' => $id]);

            return $this->json([
                'error' => 'No se encontro la orden o no tiene permiso para verla',
            ], 404);
        } catch (\Exception $e) {
            Log::error('[ProcesosPedidoController] Error en getProcesos', [
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'error' => 'Error al obtener procesos',
            ], 500);
        }
    }

    /**
     * GET /api/recibos-bodega/{numeroRecibo}/procesos
     * Seguimiento para CORTE-PARA-BODEGA desde procesos_prenda por numero_recibo.
     */
    public function getProcesosPorNumeroReciboBodega(\Illuminate\Http\Request $request, int|string $numeroRecibo): JsonResponse
    {
        try {
            $numeroReciboInt = (int) $numeroRecibo;
            if ($numeroReciboInt <= 0) {
                return $this->json([], 200);
            }

            $prendaBodegaId = (int) $request->query('prenda_bodega_id', 0);

            $hasPrendaBodegaColumn = DB::getSchemaBuilder()->hasColumn('procesos_prenda', 'prenda_bodega_id');

            $query = DB::table('procesos_prenda')
                ->where('numero_recibo', $numeroReciboInt);

            if ($prendaBodegaId > 0 && $hasPrendaBodegaColumn) {
                $query->where('prenda_bodega_id', $prendaBodegaId);
            }

            $selectColumns = [
                'id',
                'numero_pedido',
                'prenda_pedido_id',
                'numero_recibo',
                'numero_recibo_parcial',
                'proceso',
                'fecha_inicio',
                'fecha_fin',
                'dias_duracion',
                'encargado',
                'fecha_de_asignacion_encargado',
                'estado_proceso',
                'observaciones',
                'novedades',
                'codigo_referencia',
                'created_at',
                'updated_at',
            ];

            if ($hasPrendaBodegaColumn) {
                $selectColumns[] = 'prenda_bodega_id';
            }

            $procesos = $query
                ->orderBy('fecha_inicio')
                ->get($selectColumns);

            // Fallback de contexto unificado:
            // Si procesos_prenda no trae numero_pedido/prenda_pedido_id, intentar resolver
            // desde consecutivos_recibos_pedidos por numero_recibo (+ prenda_bodega_id si existe).
            $fallbackReciboQuery = DB::table('consecutivos_recibos_pedidos')
                ->where('consecutivo_actual', $numeroReciboInt)
                ->whereNotNull('pedido_produccion_id')
                ->whereNotNull('prenda_id')
                ->orderByDesc('id');

            if ($prendaBodegaId > 0 && DB::getSchemaBuilder()->hasColumn('consecutivos_recibos_pedidos', 'prenda_bodega_id')) {
                $fallbackReciboQuery->where('prenda_bodega_id', $prendaBodegaId);
            }

            $fallbackRecibo = $fallbackReciboQuery->first([
                'pedido_produccion_id',
                'prenda_id',
            ]);

            if ($fallbackRecibo && $procesos->count() > 0) {
                $procesos = $procesos->map(function ($p) use ($fallbackRecibo) {
                    if (empty($p->numero_pedido)) {
                        $p->numero_pedido = (int) $fallbackRecibo->pedido_produccion_id;
                    }
                    if (empty($p->prenda_pedido_id)) {
                        $p->prenda_pedido_id = (int) $fallbackRecibo->prenda_id;
                    }
                    return $p;
                });
            }

            // Si no hay procesos pero sí hay contexto en consecutivos, devolver un registro mínimo
            // para que el frontend pueda abrir seguimiento unificado.
            if ($procesos->count() === 0 && $fallbackRecibo) {
                $procesos = collect([
                    (object) [
                        'id' => null,
                        'numero_pedido' => (int) $fallbackRecibo->pedido_produccion_id,
                        'prenda_pedido_id' => (int) $fallbackRecibo->prenda_id,
                        'numero_recibo' => $numeroReciboInt,
                        'numero_recibo_parcial' => null,
                        'proceso' => 'Corte',
                        'fecha_inicio' => null,
                        'fecha_fin' => null,
                        'dias_duracion' => null,
                        'encargado' => null,
                        'fecha_de_asignacion_encargado' => null,
                        'estado_proceso' => null,
                        'observaciones' => null,
                        'novedades' => null,
                        'codigo_referencia' => null,
                        'created_at' => null,
                        'updated_at' => null,
                    ],
                ]);
            }

            return $this->json($procesos, 200);
        } catch (\Throwable $e) {
            Log::error('[ProcesosPedidoController] Error en getProcesosPorNumeroReciboBodega', [
                'numero_recibo' => $numeroRecibo,
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'error' => 'Error al obtener procesos de bodega',
            ], 500);
        }
    }

    /**
     * PUT /api/recibos-bodega/procesos/{id}/encargado
     * Actualiza solo encargado para procesos de recibos de bodega.
     */
    public function actualizarEncargadoBodega(\Illuminate\Http\Request $request, int|string $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'encargado' => 'required|string|max:255',
            ]);

            $procesoId = (int) $id;
            if ($procesoId <= 0) {
                return $this->failure('ID de proceso inválido', 422);
            }

            $proceso = DB::table('procesos_prenda')->where('id', $procesoId)->first();
            if (!$proceso) {
                return $this->failure('Proceso no encontrado', 404);
            }

            DB::table('procesos_prenda')
                ->where('id', $procesoId)
                ->update([
                    'encargado' => strtoupper(trim((string) $validated['encargado'])),
                    'fecha_de_asignacion_encargado' => now(),
                    'updated_at' => now(),
                ]);

            return $this->json([
                'success' => true,
                'message' => 'Encargado actualizado correctamente',
                'data' => [
                    'id' => $procesoId,
                    'encargado' => strtoupper(trim((string) $validated['encargado'])),
                ],
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->failure('Validación fallida', 422, [
                'errors' => $e->errors(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[ProcesosPedidoController] Error en actualizarEncargadoBodega', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Error al actualizar encargado de bodega', 500);
        }
    }

    /**
     * POST /api/procesos
     */
    public function crearProceso(CrearProcesoPedidoRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            Log::info('[ProcesosPedidoController] POST /procesos', ['data' => $validated]);

            $resultado = $this->crearProcesoUseCase->ejecutar($validated);

            return $this->json($resultado, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[ProcesosPedidoController] Validacion fallida en crear proceso', [
                'errors' => $e->errors(),
            ]);

            return $this->failure('Validacion fallida', 422, [
                'errors' => $e->errors(),
            ]);
        } catch (\Exception $e) {
            Log::error('[ProcesosPedidoController] Error creando proceso', [
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Error al crear el proceso: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/procesos/{id}/editar
     */
    public function editarProceso(EditarProcesoPedidoRequest $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[ProcesosPedidoController] PUT /procesos/{id}', ['id' => $id]);
            $validated = $request->validated();

            $resultado = $this->editarProcesoUseCase->ejecutar((int) $id, $validated);

            return $this->json($resultado, 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[ProcesosPedidoController] Validacion fallida en editar proceso', [
                'errors' => $e->errors(),
            ]);

            return $this->failure('Validacion fallida', 422, [
                'errors' => $e->errors(),
            ]);
        } catch (\DomainException $e) {
            Log::warning('[ProcesosPedidoController] Error de dominio en editar proceso', [
                'error' => $e->getMessage(),
            ]);

            return $this->failure($e->getMessage(), 404);
        } catch (\Exception $e) {
            Log::error('[ProcesosPedidoController] Error editando proceso', [
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Error al editar proceso: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/procesos/{id}/eliminar
     */
    public function eliminarProceso(EliminarProcesoPedidoRequest $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[ProcesosPedidoController] DELETE /procesos/{id}', ['id' => $id]);
            $validated = $request->validated();

            $resultado = $this->eliminarProcesoUseCase->ejecutar((int) $id, (int) $validated['numero_pedido']);

            return $this->json($resultado, 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[ProcesosPedidoController] Validacion fallida en eliminar proceso', [
                'errors' => $e->errors(),
            ]);

            return $this->failure('Validacion fallida', 422, [
                'errors' => $e->errors(),
            ]);
        } catch (\DomainException $e) {
            Log::warning('[ProcesosPedidoController] Error de dominio en eliminar proceso', [
                'error' => $e->getMessage(),
            ]);

            return $this->failure($e->getMessage(), 422);
        } catch (\Exception $e) {
            Log::error('[ProcesosPedidoController] Error eliminando proceso', [
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Error al eliminar proceso: ' . $e->getMessage(), 500);
        }
    }

}
