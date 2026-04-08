<?php

namespace App\Infrastructure\Http\Controllers\Asesores\PedidosProduccion;

use App\Application\Pedidos\DTOs\ActualizarProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\CrearProduccionPedidoDTO;
use App\Application\Pedidos\UseCases\ActualizarProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\CrearProduccionPedidoUseCase;
use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Requests\Asesores\ActualizarPedidoProduccionRequest;
use App\Infrastructure\Http\Requests\Asesores\CrearPedidoProduccionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * CrearActualizarPedidoController
 *
 * Responsabilidad unica: endpoints POST/PUT para crear y actualizar pedidos.
 */
class CrearActualizarPedidoController extends Controller
{
    public function __construct(
        private readonly CrearProduccionPedidoUseCase $crearPedidoUseCase,
        private readonly ActualizarProduccionPedidoUseCase $actualizarPedidoUseCase,
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
     * POST /api/pedidos
     */
    public function store(CrearPedidoProduccionRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['cantidad_inicial'] ??= 0;

            Log::info('[CrearActualizarPedidoController::store] Iniciado', [
                'numero_pedido' => $validated['numero_pedido'] ?? null,
            ]);

            $dto = CrearProduccionPedidoDTO::fromRequest($validated);
            $pedido = $this->crearPedidoUseCase->ejecutar($dto);

            Log::info('[CrearActualizarPedidoController::store] Completado', [
                'pedido_id' => is_array($pedido) ? ($pedido['id'] ?? null) : (method_exists($pedido, 'getId') ? $pedido->getId() : null),
                'epps_procesados' => count($validated['epps'] ?? []),
            ]);

            return $this->json($pedido, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[CrearActualizarPedidoController::store] Validacion fallida', [
                'errors' => $e->errors(),
            ]);

            return $this->failure('Datos invalidos', 422, [
                'errors' => $e->errors(),
            ]);
        } catch (\InvalidArgumentException $e) {
            Log::warning('[CrearActualizarPedidoController::store] Validacion de negocio fallida', [
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Validacion de negocio fallida: ' . $e->getMessage(), 422);
        } catch (\Exception $e) {
            Log::error('[CrearActualizarPedidoController::store] Error', [
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Error creando pedido: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/pedidos/{id}
     */
    public function update(ActualizarPedidoProduccionRequest $request, int|string $id): JsonResponse
    {
        try {
            $validated = $request->validated();

            Log::info('[CrearActualizarPedidoController::update] Iniciado', [
                'pedido_id' => $id,
            ]);

            $dto = ActualizarProduccionPedidoDTO::fromRequest($id, $validated);
            $pedido = $this->actualizarPedidoUseCase->ejecutar($dto);

            Log::info('[CrearActualizarPedidoController::update] Completado', [
                'pedido_id' => $pedido->id ?? $id,
            ]);

            return $this->json($pedido);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[CrearActualizarPedidoController::update] Validacion fallida', [
                'pedido_id' => $id,
                'errors' => $e->errors(),
            ]);

            return $this->failure('Datos invalidos', 422, [
                'errors' => $e->errors(),
            ]);
        } catch (\InvalidArgumentException $e) {
            Log::warning('[CrearActualizarPedidoController::update] Validacion de negocio fallida', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Validacion de negocio fallida: ' . $e->getMessage(), 422);
        } catch (\Exception $e) {
            Log::error('[CrearActualizarPedidoController::update] Error', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Error actualizando pedido: ' . $e->getMessage(), 500);
        }
    }
}
