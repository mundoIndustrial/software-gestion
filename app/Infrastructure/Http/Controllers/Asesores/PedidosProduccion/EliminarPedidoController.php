<?php

namespace App\Infrastructure\Http\Controllers\Asesores\PedidosProduccion;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Domain\Shared\CQRS\CommandBus;
use App\Domain\Pedidos\Commands\EliminarPedidoCommand;

/**
 * EliminarPedidoController
 * 
 * ✅ RESPONSABILIDAD ÚNICA: DELETE endpoint para eliminar pedido
 * 
 * HTTP Methods:
 * - DELETE /api/pedidos/{id}  → destroy()
 * 
 * Nota: Usa CQRS CommandBus para ejecutar comando de eliminación
 */
class EliminarPedidoController extends Controller
{
    public function __construct(
        private CommandBus $commandBus,
    ) {}

    /**
     * DELETE /api/pedidos/{id}
     * 
     * Eliminar pedido (soft delete)
     */
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[EliminarPedidoController::destroy] Iniciado', [
                'pedido_id' => $id,
            ]);

            $validated = $request->validate([
                'razon' => 'sometimes|string|max:500',
            ]);

            // Usar CQRS Command para eliminar
            $command = new EliminarPedidoCommand(
                (int) $id,
                $validated['razon'] ?? 'Sin especificar'
            );
            $this->commandBus->execute($command);

            Log::info('[EliminarPedidoController::destroy] Completado', [
                'pedido_id' => $id,
            ]);

            return response()->json([], 204);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[EliminarPedidoController::destroy] Validación fallida', [
                'pedido_id' => $id,
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Parámetros inválidos',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[EliminarPedidoController::destroy] Error', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error eliminando pedido: ' . $e->getMessage(),
            ], 500);
        }
    }
}
