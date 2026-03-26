<?php

namespace App\Infrastructure\Http\Controllers\Asesores\PedidosProduccion;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Application\Pedidos\UseCases\ListarProduccionPedidosUseCase;
use App\Application\Pedidos\UseCases\FiltrarPedidosPorEstadoUseCase;
use App\Application\Pedidos\UseCases\BuscarPedidoPorNumeroUseCase;
use App\Application\Pedidos\DTOs\ListarProduccionPedidosDTO;
use App\Application\Pedidos\DTOs\FiltrarPedidosPorEstadoDTO;
use App\Application\Pedidos\DTOs\BuscarPedidoPorNumeroDTO;

/**
 * ListarPedidosController
 * 
 *  RESPONSABILIDAD ÚNICA: GET endpoints para listar y buscar pedidos
 * 
 * HTTP Methods:
 * - GET /api/pedidos                           → index()
 * - GET /api/pedidos/filtro/estado             → filtrarPorEstado()
 * - GET /api/pedidos/buscar/{numero}           → buscarPorNumero()
 * 
 * Dependencias:
 * - ListarProduccionPedidosUseCase
 * - FiltrarPedidosPorEstadoUseCase
 * - BuscarPedidoPorNumeroUseCase
 */
class ListarPedidosController extends Controller
{
    public function __construct(
        private ListarProduccionPedidosUseCase $listarPedidosUseCase,
        private FiltrarPedidosPorEstadoUseCase $filtrarEstadoUseCase,
        private BuscarPedidoPorNumeroUseCase $buscarNumeroUseCase,
    ) {}

    /**
     * GET /api/pedidos
     * 
     * Listar todos los pedidos con paginación
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

            return response()->json($pedidos, 200);

        } catch (\Exception $e) {
            Log::error('[ListarPedidosController::index] Error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error listando pedidos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/pedidos/filtro/estado
     * 
     * Filtrar pedidos por estado
     */
    public function filtrarPorEstado(Request $request): JsonResponse
    {
        try {
            Log::info('[ListarPedidosController::filtrarPorEstado] Iniciado', [
                'estado' => $request->get('estado'),
            ]);

            $validated = $request->validate([
                'estado' => 'required|string|in:activo,pendiente,completado,cancelado',
                'page' => 'sometimes|integer|min:1',
                'per_page' => 'sometimes|integer|min:1|max:100',
            ]);

            $dto = FiltrarPedidosPorEstadoDTO::fromRequest($validated);
            $pedidos = $this->filtrarEstadoUseCase->ejecutar($dto);

            Log::info('[ListarPedidosController::filtrarPorEstado] Completado', [
                'estado' => $validated['estado'],
                'total' => is_object($pedidos) && method_exists($pedidos, 'total') ? $pedidos->total() : count($pedidos),
            ]);

            return response()->json($pedidos, 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[ListarPedidosController::filtrarPorEstado] Validación fallida', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Parámetros inválidos',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[ListarPedidosController::filtrarPorEstado] Error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error filtrando pedidos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/pedidos/buscar/{numero}
     * 
     * Buscar pedido por número
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

                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado',
                ], 404);
            }

            Log::info('[ListarPedidosController::buscarPorNumero] Completado', [
                'numero' => $numero,
                'pedido_id' => $pedido->id ?? null,
            ]);

            return response()->json($pedido, 200);

        } catch (\Exception $e) {
            Log::error('[ListarPedidosController::buscarPorNumero] Error', [
                'numero' => $numero,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error buscando pedido: ' . $e->getMessage(),
            ], 500);
        }
    }
}
