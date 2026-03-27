<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Pedidos\UseCases\CrearProcesoUseCase;
use App\Application\Pedidos\UseCases\EditarProcesoUseCase;
use App\Application\Pedidos\UseCases\EliminarProcesoUseCase;
use App\Application\Pedidos\UseCases\ObtenerHistorialProcesosUseCase;
use App\Application\Pedidos\UseCases\ObtenerProcesosPorPedidoUseCase;
use App\Infrastructure\Http\Requests\Asesores\CrearProcesoPedidoRequest;
use App\Infrastructure\Http\Requests\Asesores\EditarProcesoPedidoRequest;
use App\Infrastructure\Http\Requests\Asesores\EliminarProcesoPedidoRequest;
use App\Infrastructure\Http\Requests\Asesores\ObtenerProcesosPedidoRequest;
use Illuminate\Http\JsonResponse;
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
        private readonly ObtenerHistorialProcesosUseCase $obtenerHistorialProcesosUseCase,
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

    /**
     * GET /api/pedidos/{numeroPedido}/procesos/historial
     */
    public function obtenerHistorial(int|string $numeroPedido): JsonResponse
    {
        try {
            Log::info('[ProcesosPedidoController] GET /procesos/historial', [
                'numero_pedido' => $numeroPedido,
            ]);

            $resultado = $this->obtenerHistorialProcesosUseCase->ejecutar((int) $numeroPedido);

            return $this->json($resultado, 200);
        } catch (\Exception $e) {
            Log::error('[ProcesosPedidoController] Error al obtener historial', [
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Error al obtener el historial', 500);
        }
    }
}
