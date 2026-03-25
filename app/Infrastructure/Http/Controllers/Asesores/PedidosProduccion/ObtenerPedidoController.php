<?php

namespace App\Infrastructure\Http\Controllers\Asesores\PedidosProduccion;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Application\Pedidos\UseCases\ObtenerProduccionPedidoUseCase;
use App\Application\Pedidos\DTOs\ObtenerProduccionPedidoDTO;

/**
 * ObtenerPedidoController
 * 
 * ✅ RESPONSABILIDAD ÚNICA: GET endpoint para obtener un pedido específico
 * 
 * HTTP Methods:
 * - GET /api/pedidos/{id}  → show()
 */
class ObtenerPedidoController extends Controller
{
    public function __construct(
        private ObtenerProduccionPedidoUseCase $obtenerPedidoUseCase,
    ) {}

    /**
     * GET /api/pedidos/{id}
     * 
     * Obtener un pedido específico
     */
    public function show(int|string $id): JsonResponse
    {
        try {
            Log::info('[ObtenerPedidoController::show] Iniciado', [
                'pedido_id' => $id,
            ]);

            $dto = ObtenerProduccionPedidoDTO::fromRequest($id);
            $pedido = $this->obtenerPedidoUseCase->ejecutar($dto);

            if (!$pedido) {
                Log::warning('[ObtenerPedidoController::show] No encontrado', [
                    'pedido_id' => $id,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado',
                ], 404);
            }

            Log::info('[ObtenerPedidoController::show] Completado', [
                'pedido_id' => $pedido->id ?? $id,
            ]);

            return response()->json($pedido, 200);

        } catch (\Exception $e) {
            Log::error('[ObtenerPedidoController::show] Error', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo pedido: ' . $e->getMessage(),
            ], 500);
        }
    }
}
