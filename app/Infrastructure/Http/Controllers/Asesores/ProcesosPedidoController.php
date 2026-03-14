<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Application\Pedidos\UseCases\ObtenerProcesosPorPedidoUseCase;
use App\Application\Pedidos\UseCases\CrearProcesoUseCase;
use App\Application\Pedidos\UseCases\EditarProcesoUseCase;
use App\Application\Pedidos\UseCases\EliminarProcesoUseCase;
use App\Application\Pedidos\UseCases\ObtenerHistorialProcesosUseCase;

/**
 * ProcesosPedidoController
 *
 * Responsabilidad: Gestionar procesos asociados a pedidos de producción.
 * - Obtener procesos de un pedido
 * - Crear, editar y eliminar procesos
 * - Obtener historial de procesos
 *
 * Patrón: CQRS + Dependency Injection
 * SRP: Solo operaciones de procesos, sin lógica de negocio
 */
class ProcesosPedidoController
{
    public function __construct(
        private ObtenerProcesosPorPedidoUseCase $obtenerProcesosPedidoUseCase,
        private CrearProcesoUseCase $crearProcesoUseCase,
        private EditarProcesoUseCase $editarProcesoUseCase,
        private EliminarProcesoUseCase $eliminarProcesoUseCase,
        private ObtenerHistorialProcesosUseCase $obtenerHistorialProcesosUseCase,
    ) {}

    /**
     * GET /api/ordenes/{id}/procesos
     * Obtener procesos de un pedido con cálculo de días hábiles
     */
    public function getProcesos($id): JsonResponse
    {
        try {
            Log::info('[ProcesosPedidoController] GET /procesos', ['id' => $id]);

            $resultado = $this->obtenerProcesosPedidoUseCase->ejecutar($id);

            return response()->json($resultado['procesos'] ?? [], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('[ProcesosPedidoController] Pedido no encontrado', ['id' => $id]);

            return response()->json([
                'error' => 'No se encontró la orden o no tiene permiso para verla'
            ], 404);

        } catch (\Exception $e) {
            Log::error('[ProcesosPedidoController] Error en getProcesos', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Error al obtener procesos'
            ], 500);
        }
    }

    /**
     * POST /api/procesos
     * Crear un nuevo proceso
     */
    public function crearProceso(Request $request): JsonResponse
    {
        try {
            Log::info('[ProcesosPedidoController] POST /procesos', ['data' => $request->all()]);

            $validated = $request->validate([
                'numero_pedido' => 'required|integer',
                'proceso' => 'required|string|max:255',
                'fecha_inicio' => 'required|date',
                'encargado' => 'nullable|string|max:255',
                'estado_proceso' => 'required|in:Pendiente,En Progreso,Completado,Pausado',
            ]);

            $resultado = $this->crearProcesoUseCase->ejecutar($validated);

            return response()->json($resultado, 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[ProcesosPedidoController] Validación fallida en crear proceso', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('[ProcesosPedidoController] Error creando proceso', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el proceso: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /api/procesos/{id}/editar
     * Editar un proceso existente
     */
    public function editarProceso(Request $request, $id): JsonResponse
    {
        try {
            Log::info('[ProcesosPedidoController] PUT /procesos/{id}', ['id' => $id]);

            $validated = $request->validate([
                'numero_pedido' => 'required|integer',
                'proceso' => 'required|string|max:255',
                'fecha_inicio' => 'required|date',
                'encargado' => 'nullable|string|max:255',
                'estado_proceso' => 'required|in:Pendiente,En Progreso,Completado,Pausado',
                'observaciones' => 'nullable|string',
            ]);

            $resultado = $this->editarProcesoUseCase->ejecutar((int)$id, $validated);

            return response()->json($resultado, 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[ProcesosPedidoController] Validación fallida en editar proceso', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors()
            ], 422);

        } catch (\DomainException $e) {
            Log::warning('[ProcesosPedidoController] Error de dominio en editar proceso', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);

        } catch (\Exception $e) {
            Log::error('[ProcesosPedidoController] Error editando proceso', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al editar proceso: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/procesos/{id}/eliminar
     * Eliminar un proceso
     */
    public function eliminarProceso(Request $request, $id): JsonResponse
    {
        try {
            Log::info('[ProcesosPedidoController] DELETE /procesos/{id}', ['id' => $id]);

            $validated = $request->validate([
                'numero_pedido' => 'required|integer',
            ]);

            $resultado = $this->eliminarProcesoUseCase->ejecutar((int)$id, $validated['numero_pedido']);

            return response()->json($resultado, 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[ProcesosPedidoController] Validación fallida en eliminar proceso', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors()
            ], 422);

        } catch (\DomainException $e) {
            Log::warning('[ProcesosPedidoController] Error de dominio en eliminar proceso', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);

        } catch (\Exception $e) {
            Log::error('[ProcesosPedidoController] Error eliminando proceso', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar proceso: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/pedidos/{numeroPedido}/procesos/historial
     * Obtener historial de procesos
     */
    public function obtenerHistorial($numeroPedido): JsonResponse
    {
        try {
            Log::info('[ProcesosPedidoController] GET /procesos/historial', ['numero_pedido' => $numeroPedido]);

            $resultado = $this->obtenerHistorialProcesosUseCase->ejecutar((int)$numeroPedido);

            return response()->json($resultado, 200);

        } catch (\Exception $e) {
            Log::error('[ProcesosPedidoController] Error al obtener historial', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial'
            ], 500);
        }
    }
}
