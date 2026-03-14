<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Domain\Shared\CQRS\CommandBus;
use App\Domain\Pedidos\Commands\EliminarPedidoCommand;
use App\Application\Pedidos\UseCases\ListarProduccionPedidosUseCase;
use App\Application\Pedidos\UseCases\ObtenerProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\CrearProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\ActualizarProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\CambiarEstadoPedidoUseCase;
use App\Application\Pedidos\UseCases\FiltrarPedidosPorEstadoUseCase;
use App\Application\Pedidos\UseCases\BuscarPedidoPorNumeroUseCase;
use App\Application\Pedidos\DTOs\ListarProduccionPedidosDTO;
use App\Application\Pedidos\DTOs\ObtenerProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\CrearProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\ActualizarProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\CambiarEstadoPedidoDTO;
use App\Application\Pedidos\DTOs\FiltrarPedidosPorEstadoDTO;
use App\Application\Pedidos\DTOs\BuscarPedidoPorNumeroDTO;

/**
 * PedidosController
 *
 * Responsabilidad: Gestionar el ciclo de vida de pedidos de producción.
 * - Listar, crear, ver, actualizar y eliminar pedidos
 * - Cambiar estado de pedido
 * - Filtrar por estado y buscar por número
 *
 * Patrón: CQRS + Dependency Injection
 * SRP: Solo operaciones CRUD de pedidos, sin lógica de negocio
 */
class PedidosController
{
    public function __construct(
        private CommandBus $commandBus,
        private ListarProduccionPedidosUseCase $listarPedidosUseCase,
        private ObtenerProduccionPedidoUseCase $obtenerPedidoUseCase,
        private CrearProduccionPedidoUseCase $crearPedidoUseCase,
        private ActualizarProduccionPedidoUseCase $actualizarPedidoUseCase,
        private CambiarEstadoPedidoUseCase $cambiarEstadoUseCase,
        private FiltrarPedidosPorEstadoUseCase $filtrarEstadoUseCase,
        private BuscarPedidoPorNumeroUseCase $buscarNumeroUseCase,
    ) {}

    /**
     * GET /pedidos-produccion
     * Listar todos los pedidos con paginación
     */
    public function index(Request $request): JsonResponse
    {
        try {
            Log::info('[PedidosController] GET /pedidos-produccion');

            $filtros = [
                'estado' => $request->get('estado'),
                'search' => $request->get('search'),
            ];

            $dto = ListarProduccionPedidosDTO::fromRequest(null, $filtros);
            $pedidos = $this->listarPedidosUseCase->ejecutar($dto);

            Log::info('[PedidosController] Listado obtenido', [
                'total' => $pedidos->total(),
            ]);

            return response()->json($pedidos, 200);

        } catch (\Exception $e) {
            Log::error('[PedidosController] Error listando pedidos', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Error listando pedidos',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /pedidos-produccion/{id}
     * Obtener un pedido específico
     */
    public function show(int|string $id): JsonResponse
    {
        try {
            Log::info('[PedidosController] GET /pedidos-produccion/{id}', ['id' => $id]);

            $dto = ObtenerProduccionPedidoDTO::fromRequest($id);
            $pedido = $this->obtenerPedidoUseCase->ejecutar($dto);

            if (!$pedido) {
                Log::warning('[PedidosController] Pedido no encontrado', ['id' => $id]);
                return response()->json([
                    'error' => 'Pedido no encontrado',
                ], 404);
            }

            Log::info('[PedidosController] Pedido obtenido', [
                'pedido_id' => $pedido->id,
            ]);

            return response()->json($pedido, 200);

        } catch (\Exception $e) {
            Log::error('[PedidosController] Error obteniendo pedido', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Error obteniendo pedido',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/pedidos
     * Crear nuevo pedido
     */
    public function store(Request $request): JsonResponse
    {
        try {
            Log::info('[PedidosController] POST /api/pedidos');

            $validated = $request->validate([
                'numero_pedido' => 'required|string|max:50',
                'cliente' => 'required|string|max:255',
                'forma_pago' => 'required|string|in:contado,credito,transferencia,cheque',
                'asesor_id' => 'required|integer|min:1',
                'cantidad_inicial' => 'sometimes|integer|min:0|default:0',
                'epps' => 'sometimes|array',
                'epps.*.epp_id' => 'required_with:epps|integer|min:1',
                'epps.*.cantidad' => 'sometimes|integer|min:1',
                'epps.*.observaciones' => 'sometimes|nullable|string|max:1000',
            ]);

            $dto = CrearProduccionPedidoDTO::fromRequest($validated);
            $pedido = $this->crearPedidoUseCase->ejecutar($dto);

            Log::info('[PedidosController] Pedido creado', [
                'pedido_id' => is_array($pedido) ? ($pedido['id'] ?? null) : (method_exists($pedido, 'getId') ? $pedido->getId() : null),
                'epps_procesados' => count($validated['epps'] ?? []),
            ]);

            return response()->json($pedido, 201);

        } catch (\InvalidArgumentException $e) {
            Log::warning('[PedidosController] Validación de negocio fallida', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Validación de negocio fallida',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[PedidosController] Error creando pedido', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Error creando pedido',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/pedidos/{id}
     * Actualizar pedido
     */
    public function update(Request $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[PedidosController] PUT /api/pedidos/{id}', ['id' => $id]);

            $validated = $request->validate([
                'cliente' => 'sometimes|string|max:255',
                'forma_pago' => 'sometimes|string|in:contado,credito,transferencia,cheque',
            ]);

            $dto = ActualizarProduccionPedidoDTO::fromRequest($id, $validated);
            $pedido = $this->actualizarPedidoUseCase->ejecutar($dto);

            Log::info('[PedidosController] Pedido actualizado', [
                'pedido_id' => $pedido->id,
            ]);

            return response()->json($pedido, 200);

        } catch (\InvalidArgumentException $e) {
            Log::warning('[PedidosController] Validación fallida', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Validación fallida',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[PedidosController] Error actualizando pedido', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Error actualizando pedido',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/pedidos/{id}/estado
     * Cambiar estado de pedido
     */
    public function cambiarEstado(Request $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[PedidosController] PUT /api/pedidos/{id}/estado', ['id' => $id]);

            $validated = $request->validate([
                'nuevo_estado' => 'required|string|in:activo,pendiente,completado,cancelado',
                'razon' => 'sometimes|string|max:500',
            ]);

            $dto = CambiarEstadoPedidoDTO::fromRequest($id, $validated);
            $pedido = $this->cambiarEstadoUseCase->ejecutar($dto);

            Log::info('[PedidosController] Estado cambiado exitosamente', [
                'pedido_id' => $pedido->id,
                'nuevo_estado' => $pedido->estado,
            ]);

            return response()->json($pedido, 200);

        } catch (\InvalidArgumentException $e) {
            Log::warning('[PedidosController] Transición no permitida', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Transición de estado no permitida',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[PedidosController] Error cambiando estado', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Error cambiando estado',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/pedidos/{id}
     * Eliminar pedido (soft delete)
     */
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[PedidosController] DELETE /api/pedidos/{id}', ['id' => $id]);

            $validated = $request->validate([
                'razon' => 'sometimes|string|max:500',
            ]);

            $command = new EliminarPedidoCommand(
                (int)$id,
                $validated['razon'] ?? 'Sin especificar'
            );
            $this->commandBus->execute($command);

            Log::info('[PedidosController] Pedido eliminado', ['pedido_id' => $id]);

            return response()->json([], 204);

        } catch (\Exception $e) {
            Log::error('[PedidosController] Error eliminando pedido', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Error eliminando pedido',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/pedidos/filtro/estado
     * Filtrar pedidos por estado
     */
    public function filtrarPorEstado(Request $request): JsonResponse
    {
        try {
            Log::info('[PedidosController] GET /api/pedidos/filtro/estado');

            $validated = $request->validate([
                'estado' => 'required|string|in:activo,pendiente,completado,cancelado',
                'page' => 'sometimes|integer|min:1',
                'per_page' => 'sometimes|integer|min:1|max:100',
            ]);

            $dto = FiltrarPedidosPorEstadoDTO::fromRequest($validated);
            $pedidos = $this->filtrarEstadoUseCase->ejecutar($dto);

            Log::info('[PedidosController] Filtrado por estado exitosamente', [
                'estado' => $validated['estado'],
                'total' => is_object($pedidos) && method_exists($pedidos, 'total') ? $pedidos->total() : count($pedidos),
            ]);

            return response()->json($pedidos, 200);

        } catch (\InvalidArgumentException $e) {
            Log::warning('[PedidosController] Estado inválido', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Estado inválido',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[PedidosController] Error filtrando por estado', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Error filtrando pedidos',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/pedidos/buscar/{numero}
     * Buscar pedido por número
     */
    public function buscarPorNumero(string $numero): JsonResponse
    {
        try {
            Log::info('[PedidosController] GET /api/pedidos/buscar/{numero}', ['numero' => $numero]);

            $dto = BuscarPedidoPorNumeroDTO::fromRequest($numero);
            $pedido = $this->buscarNumeroUseCase->ejecutar($dto);

            if (!$pedido) {
                Log::warning('[PedidosController] Pedido no encontrado', ['numero' => $numero]);
                return response()->json([
                    'error' => 'Pedido no encontrado',
                ], 404);
            }

            Log::info('[PedidosController] Pedido encontrado exitosamente', [
                'numero' => $numero,
                'pedido_id' => $pedido->id,
            ]);

            return response()->json($pedido, 200);

        } catch (\Exception $e) {
            Log::error('[PedidosController] Error buscando pedido', [
                'numero' => $numero,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Error buscando pedido',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
