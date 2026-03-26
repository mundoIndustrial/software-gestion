<?php

namespace App\Infrastructure\Http\Controllers\Asesores\PedidosProduccion;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Application\Pedidos\UseCases\CambiarEstadoPedidoUseCase;
use App\Application\Pedidos\DTOs\CambiarEstadoPedidoDTO;

/**
 * CambiarEstadoPedidoController
 * 
 *  RESPONSABILIDAD ÚNICA: PUT endpoint para cambiar estado de pedido
 * 
 * HTTP Methods:
 * - PUT /api/pedidos/{id}/estado  → cambiarEstado()
 */
class CambiarEstadoPedidoController extends Controller
{
    public function __construct(
        private CambiarEstadoPedidoUseCase $cambiarEstadoUseCase,
    ) {}

    /**
     * PUT /api/pedidos/{id}/estado
     * 
     * Cambiar estado de pedido
     */
    public function cambiarEstado(Request $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[CambiarEstadoPedidoController::cambiarEstado] Iniciado', [
                'pedido_id' => $id,
                'nuevo_estado' => $request->get('nuevo_estado'),
            ]);

            $validated = $request->validate([
                'nuevo_estado' => 'required|string|in:activo,pendiente,completado,cancelado',
                'razon' => 'sometimes|string|max:500',
            ]);

            $dto = CambiarEstadoPedidoDTO::fromRequest($id, $validated);
            $pedido = $this->cambiarEstadoUseCase->ejecutar($dto);

            Log::info('[CambiarEstadoPedidoController::cambiarEstado] Completado', [
                'pedido_id' => $pedido->id ?? $id,
                'nuevo_estado' => $pedido->estado ?? $validated['nuevo_estado'],
            ]);

            return response()->json($pedido, 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[CambiarEstadoPedidoController::cambiarEstado] Validación fallida', [
                'pedido_id' => $id,
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Parámetros inválidos',
                'errors' => $e->errors(),
            ], 422);

        } catch (\InvalidArgumentException $e) {
            Log::warning('[CambiarEstadoPedidoController::cambiarEstado] Transición no permitida', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Transición de estado no permitida: ' . $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[CambiarEstadoPedidoController::cambiarEstado] Error', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error cambiando estado: ' . $e->getMessage(),
            ], 500);
        }
    }
}
