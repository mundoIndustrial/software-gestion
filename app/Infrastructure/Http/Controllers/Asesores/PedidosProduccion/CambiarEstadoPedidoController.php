<?php

namespace App\Infrastructure\Http\Controllers\Asesores\PedidosProduccion;

use App\Application\Pedidos\DTOs\CambiarEstadoPedidoDTO;
use App\Application\Pedidos\UseCases\CambiarEstadoPedidoUseCase;
use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Requests\Asesores\CambiarEstadoPedidoProduccionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * CambiarEstadoPedidoController
 *
 * Responsabilidad unica: endpoint para cambiar estado de pedido.
 */
class CambiarEstadoPedidoController extends Controller
{
    public function __construct(
        private readonly CambiarEstadoPedidoUseCase $cambiarEstadoUseCase,
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
     * PUT /api/pedidos/{id}/estado
     */
    public function cambiarEstado(CambiarEstadoPedidoProduccionRequest $request, int|string $id): JsonResponse
    {
        try {
            $validated = $request->validated();
            Log::info('[CambiarEstadoPedidoController::cambiarEstado] Iniciado', [
                'pedido_id' => $id,
                'nuevo_estado' => $validated['nuevo_estado'] ?? null,
            ]);

            $dto = CambiarEstadoPedidoDTO::fromRequest($id, $validated);
            $pedido = $this->cambiarEstadoUseCase->ejecutar($dto);

            Log::info('[CambiarEstadoPedidoController::cambiarEstado] Completado', [
                'pedido_id' => $pedido->id ?? $id,
                'nuevo_estado' => $pedido->estado ?? $validated['nuevo_estado'],
            ]);

            return $this->json($pedido);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[CambiarEstadoPedidoController::cambiarEstado] Validacion fallida', [
                'pedido_id' => $id,
                'errors' => $e->errors(),
            ]);

            return $this->failure('Parametros invalidos', 422, [
                'errors' => $e->errors(),
            ]);
        } catch (\InvalidArgumentException $e) {
            Log::warning('[CambiarEstadoPedidoController::cambiarEstado] Transicion no permitida', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Transicion de estado no permitida: ' . $e->getMessage(), 422);
        } catch (\Exception $e) {
            Log::error('[CambiarEstadoPedidoController::cambiarEstado] Error', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Error cambiando estado: ' . $e->getMessage(), 500);
        }
    }
}
