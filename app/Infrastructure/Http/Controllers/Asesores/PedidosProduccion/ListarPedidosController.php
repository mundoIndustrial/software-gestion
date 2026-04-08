<?php

namespace App\Infrastructure\Http\Controllers\Asesores\PedidosProduccion;

use App\Application\Pedidos\DTOs\BuscarPedidoPorNumeroDTO;
use App\Application\Pedidos\DTOs\FiltrarPedidosPorEstadoDTO;
use App\Application\Pedidos\DTOs\ListarProduccionPedidosDTO;
use App\Application\Pedidos\UseCases\BuscarPedidoPorNumeroUseCase;
use App\Application\Pedidos\UseCases\FiltrarPedidosPorEstadoUseCase;
use App\Application\Pedidos\UseCases\ListarProduccionPedidosUseCase;
use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Requests\Asesores\FiltrarPedidosPorEstadoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * ListarPedidosController
 *
 * Responsabilidad unica: endpoints GET para listar y buscar pedidos.
 */
class ListarPedidosController extends Controller
{
    public function __construct(
        private readonly ListarProduccionPedidosUseCase $listarPedidosUseCase,
        private readonly FiltrarPedidosPorEstadoUseCase $filtrarEstadoUseCase,
        private readonly BuscarPedidoPorNumeroUseCase $buscarNumeroUseCase,
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
     * GET /api/pedidos
     */
    public function index(Request $request): JsonResponse
    {
        try {
            Log::info('[ListarPedidosController::index] Iniciado', [
                'filtros' => $request->all(),
            ]);

            $filtros = [
                'estado' => $request->get('estado'),
                'search' => $request->get('search'),
                'page' => $request->get('page', 1),
                'per_page' => $request->get('per_page', 15),
            ];

            $usuario = Auth::user();
            $dto = ListarProduccionPedidosDTO::fromRequest(
                null,
                $filtros,
                $usuario?->id,
                (bool) ($usuario?->hasRole('asesor'))
            );
            $pedidos = $this->listarPedidosUseCase->ejecutar($dto);

            Log::info('[ListarPedidosController::index] Completado', [
                'total' => is_object($pedidos) && method_exists($pedidos, 'total') ? $pedidos->total() : count($pedidos),
            ]);

            return $this->json($pedidos);
        } catch (\Exception $e) {
            Log::error('[ListarPedidosController::index] Error', [
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Error listando pedidos: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/pedidos/filtro/estado
     */
    public function filtrarPorEstado(FiltrarPedidosPorEstadoRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            Log::info('[ListarPedidosController::filtrarPorEstado] Iniciado', [
                'estado' => $validated['estado'] ?? null,
            ]);

            $dto = FiltrarPedidosPorEstadoDTO::fromRequest($validated);
            $pedidos = $this->filtrarEstadoUseCase->ejecutar($dto);

            Log::info('[ListarPedidosController::filtrarPorEstado] Completado', [
                'estado' => $validated['estado'],
                'total' => is_object($pedidos) && method_exists($pedidos, 'total') ? $pedidos->total() : count($pedidos),
            ]);

            return $this->json($pedidos);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[ListarPedidosController::filtrarPorEstado] Validacion fallida', [
                'errors' => $e->errors(),
            ]);

            return $this->failure('Parametros invalidos', 422, [
                'errors' => $e->errors(),
            ]);
        } catch (\Exception $e) {
            Log::error('[ListarPedidosController::filtrarPorEstado] Error', [
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Error filtrando pedidos: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/pedidos/buscar/{numero}
     */
    public function buscarPorNumero(string $numero): JsonResponse
    {
        try {
            Log::info('[ListarPedidosController::buscarPorNumero] Iniciado', [
                'numero' => $numero,
            ]);

            $dto = BuscarPedidoPorNumeroDTO::fromRequest($numero);
            $pedido = $this->buscarNumeroUseCase->ejecutar($dto);

            if (!$pedido) {
                Log::warning('[ListarPedidosController::buscarPorNumero] No encontrado', [
                    'numero' => $numero,
                ]);

                return $this->failure('Pedido no encontrado', 404);
            }

            Log::info('[ListarPedidosController::buscarPorNumero] Completado', [
                'numero' => $numero,
                'pedido_id' => $pedido->id ?? null,
            ]);

            return $this->json($pedido);
        } catch (\Exception $e) {
            Log::error('[ListarPedidosController::buscarPorNumero] Error', [
                'numero' => $numero,
                'error' => $e->getMessage(),
            ]);

            return $this->failure('Error buscando pedido: ' . $e->getMessage(), 500);
        }
    }
}
