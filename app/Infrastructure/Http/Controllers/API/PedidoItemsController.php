<?php

namespace App\Infrastructure\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Application\Pedidos\UseCases\AgregarItemAlPedidoUseCase;
use App\Application\Pedidos\UseCases\EliminarItemDelPedidoUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Infrastructure HTTP Controller: PedidoItemsController
 * 
 * Capa de Infraestructura HTTP
 * Responsabilidades:
 * - Validar entrada HTTP
 * - Invocar Use Cases (Application Layer)
 * - Formatear respuestas JSON
 * - Manejar excepciones HTTP
 * 
 * Rutas:
 * - POST /api/pedidos/{pedidoId}/items - Agregar item
 * - DELETE /api/items/{itemId} - Eliminar item
 * - GET /api/pedidos/{pedidoId}/items - Obtener items
 */
class PedidoItemsController extends Controller
{
    public function __construct(
        private AgregarItemAlPedidoUseCase $agregarItemUseCase,
        private EliminarItemDelPedidoUseCase $eliminarItemUseCase
    ) {}

    /**
     * Agregar item (Prenda o EPP) al pedido
     * 
     * POST /api/pedidos/{pedidoId}/items
     * 
     * Request Body:
     * {
     *   "tipo": "prenda|epp",
     *   "referencia_id": 123,
     *   "nombre": "Camisa Azul",
     *   "descripcion": "Descripción opcional",
     *   "datos_presentacion": { ... }
     * }
     * 
     * Response Success (201):
     * {
     *   "success": true,
     *   "item": { ... },
     *   "items": [ ... ],
     *   "message": "Item agregado correctamente"
     * }
     */
    public function agregarItem(Request $request, int $pedidoId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tipo' => 'required|in:prenda,epp',
                'referencia_id' => 'required|integer|min:1',
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'datos_presentacion' => 'nullable|array',
            ]);

            $validated['pedido_id'] = $pedidoId;

            $resultado = $this->agregarItemUseCase->ejecutar($validated);

            return response()->json($resultado, 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'validation_errors' => $e->errors()
            ], 422);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'validation_errors' => [
                    ['field' => 'general', 'message' => $e->getMessage()]
                ]
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Error al agregar item al pedido', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al agregar item',
            ], 500);
        }
    }

    /**
     * Eliminar item del pedido
     * 
     * DELETE /api/pedidos/{pedidoId}/items/{itemId}
     * 
     * Response Success (200):
     * {
     *   "success": true,
     *   "items": [ ... ],
     *   "message": "Item eliminado correctamente",
     *   "relacionados_eliminados": {
     *     "procesos": 0,
     *     "variantes": 0
     *   }
     * }
     */
    public function eliminarItem(int $pedidoId, int $itemId): JsonResponse
    {
        try {
            $resultado = $this->eliminarItemUseCase->ejecutar($itemId, $pedidoId);

            return response()->json($resultado, 200);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);

        } catch (\Exception $e) {
            \Log::error('Error al eliminar item del pedido', [
                'item_id' => $itemId,
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar item',
            ], 500);
        }
    }

    /**
     * Obtener items de un pedido
     * 
     * GET /api/pedidos/{pedidoId}/items
     * 
     * Response Success (200):
     * {
     *   "success": true,
     *   "items": [ ... ],
     *   "total": 3
     * }
     */
    public function obtenerItems(int $pedidoId): JsonResponse
    {
        try {
            // TODO: Implementar Use Case para obtener items
            // Por ahora es placeholder
            return response()->json([
                'success' => true,
                'items' => [],
                'total' => 0
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al obtener items del pedido', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener items',
            ], 500);
        }
    }
}
