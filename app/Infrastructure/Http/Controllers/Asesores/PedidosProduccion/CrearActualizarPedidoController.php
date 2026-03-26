<?php

namespace App\Infrastructure\Http\Controllers\Asesores\PedidosProduccion;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Application\Pedidos\UseCases\CrearProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\ActualizarProduccionPedidoUseCase;
use App\Application\Pedidos\DTOs\CrearProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\ActualizarProduccionPedidoDTO;

/**
 * CrearActualizarPedidoController
 * 
 *  RESPONSABILIDAD ÚNICA: POST/PUT endpoints para CRUD de pedidos
 * 
 * HTTP Methods:
 * - POST /api/pedidos        → store()
 * - PUT /api/pedidos/{id}    → update()
 */
class CrearActualizarPedidoController extends Controller
{
    public function __construct(
        private CrearProduccionPedidoUseCase $crearPedidoUseCase,
        private ActualizarProduccionPedidoUseCase $actualizarPedidoUseCase,
    ) {}

    /**
     * POST /api/pedidos
     * 
     * Crear nuevo pedido
     */
    public function store(Request $request): JsonResponse
    {
        try {
            Log::info('[CrearActualizarPedidoController::store] Iniciado');

            $validated = $request->validate([
                'numero_pedido' => 'required|string|max:50',
                'cliente' => 'required|string|max:255',
                'forma_pago' => 'required|string|in:contado,credito,transferencia,cheque',
                'asesor_id' => 'required|integer|min:1',
                'cantidad_inicial' => 'sometimes|integer|min:0',
                'epps' => 'sometimes|array',
                'epps.*.epp_id' => 'required_with:epps|integer|min:1',
                'epps.*.cantidad' => 'sometimes|integer|min:1',
                'epps.*.observaciones' => 'sometimes|nullable|string|max:1000',
            ]);
            $validated['cantidad_inicial'] ??= 0;

            $dto = CrearProduccionPedidoDTO::fromRequest($validated);
            $pedido = $this->crearPedidoUseCase->ejecutar($dto);

            Log::info('[CrearActualizarPedidoController::store] Completado', [
                'pedido_id' => is_array($pedido) ? ($pedido['id'] ?? null) : (method_exists($pedido, 'getId') ? $pedido->getId() : null),
                'epps_procesados' => count($validated['epps'] ?? []),
            ]);

            return response()->json($pedido, 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[CrearActualizarPedidoController::store] Validación fallida', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors(),
            ], 422);

        } catch (\InvalidArgumentException $e) {
            Log::warning('[CrearActualizarPedidoController::store] Validación de negocio fallida', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validación de negocio fallida: ' . $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[CrearActualizarPedidoController::store] Error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error creando pedido: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/pedidos/{id}
     * 
     * Actualizar pedido
     */
    public function update(Request $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[CrearActualizarPedidoController::update] Iniciado', [
                'pedido_id' => $id,
            ]);

            $validated = $request->validate([
                'cliente' => 'sometimes|string|max:255',
                'forma_pago' => 'sometimes|string|in:contado,credito,transferencia,cheque',
            ]);

            $dto = ActualizarProduccionPedidoDTO::fromRequest($id, $validated);
            $pedido = $this->actualizarPedidoUseCase->ejecutar($dto);

            Log::info('[CrearActualizarPedidoController::update] Completado', [
                'pedido_id' => $pedido->id ?? $id,
            ]);

            return response()->json($pedido, 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[CrearActualizarPedidoController::update] Validación fallida', [
                'pedido_id' => $id,
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors(),
            ], 422);

        } catch (\InvalidArgumentException $e) {
            Log::warning('[CrearActualizarPedidoController::update] Validación de negocio fallida', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validación de negocio fallida: ' . $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[CrearActualizarPedidoController::update] Error', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error actualizando pedido: ' . $e->getMessage(),
            ], 500);
        }
    }
}
