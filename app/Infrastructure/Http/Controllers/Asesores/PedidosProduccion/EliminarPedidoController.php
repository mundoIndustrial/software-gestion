<?php

namespace App\Infrastructure\Http\Controllers\Asesores\PedidosProduccion;

use App\Domain\Pedidos\Commands\EliminarPedidoCommand;
use App\Domain\Shared\CQRS\CommandBus;
use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Requests\Asesores\EliminarPedidoProduccionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * EliminarPedidoController
 *
 * Responsabilidad unica: endpoint DELETE para eliminar pedido.
 */
class EliminarPedidoController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {
    }

    private function json(mixed $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status);
    }

    private function failure(string $message, int $status, array $extra = []): JsonResponse
    {
        return $this->json(array_merge([
            'success' => false,
            'message' => $message,
        ], $extra), $status);
    }

    /**
     * DELETE /api/pedidos/{id}
     */
    public function destroy(EliminarPedidoProduccionRequest $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[EliminarPedidoController::destroy] Iniciado', [
                'pedido_id' => $id,
            ]);

            $validated = $request->validated();

            $command = new EliminarPedidoCommand(
                (int) $id,
                $validated['razon'] ?? 'Sin especificar'
            );
            $this->commandBus->execute($command);

            Log::info('[EliminarPedidoController::destroy] Completado', [
                'pedido_id' => $id,
            ]);

            return $this->json([], 204);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[EliminarPedidoController::destroy] Validacion fallida', [
                'pedido_id' => $id,
                'errors' => $e->errors(),
            ]);

            return $this->failure('Parametros invalidos', 422, [
                'errors' => $e->errors(),
            ]);
        } catch (\Exception $e) {
            Log::error('[EliminarPedidoController::destroy] Error', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Error eliminando pedido: ' . $e->getMessage(), 500);
        }
    }
}
