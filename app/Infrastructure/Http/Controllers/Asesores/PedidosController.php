<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Application\Pedidos\DTOs\ActualizarProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\BuscarPedidoPorNumeroDTO;
use App\Application\Pedidos\DTOs\CambiarEstadoPedidoDTO;
use App\Application\Pedidos\DTOs\CrearProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\FiltrarPedidosPorEstadoDTO;
use App\Application\Pedidos\DTOs\ListarProduccionPedidosDTO;
use App\Application\Pedidos\DTOs\ObtenerProduccionPedidoDTO;
use App\Application\Pedidos\UseCases\ActualizarProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\BuscarPedidoPorNumeroUseCase;
use App\Application\Pedidos\UseCases\CambiarEstadoPedidoUseCase;
use App\Application\Pedidos\UseCases\CrearProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\FiltrarPedidosPorEstadoUseCase;
use App\Application\Pedidos\UseCases\ListarProduccionPedidosUseCase;
use App\Application\Pedidos\UseCases\ObtenerProduccionPedidoUseCase;
use App\Domain\Pedidos\Commands\EliminarPedidoCommand;
use App\Domain\Shared\CQRS\CommandBus;
use App\Infrastructure\Http\Requests\Asesores\ActualizarPedidoProduccionRequest;
use App\Infrastructure\Http\Requests\Asesores\CambiarEstadoPedidoProduccionRequest;
use App\Infrastructure\Http\Requests\Asesores\CrearPedidoProduccionRequest;
use App\Infrastructure\Http\Requests\Asesores\EliminarPedidoProduccionRequest;
use App\Infrastructure\Http\Requests\Asesores\FiltrarPedidosPorEstadoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * PedidosController
 *
 * Responsabilidad: Gestionar el ciclo de vida de pedidos de produccion.
 */
class PedidosController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ListarProduccionPedidosUseCase $listarPedidosUseCase,
        private readonly ObtenerProduccionPedidoUseCase $obtenerPedidoUseCase,
        private readonly CrearProduccionPedidoUseCase $crearPedidoUseCase,
        private readonly ActualizarProduccionPedidoUseCase $actualizarPedidoUseCase,
        private readonly CambiarEstadoPedidoUseCase $cambiarEstadoUseCase,
        private readonly FiltrarPedidosPorEstadoUseCase $filtrarEstadoUseCase,
        private readonly BuscarPedidoPorNumeroUseCase $buscarNumeroUseCase,
    ) {
    }

    private function json(mixed $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status);
    }

    private function errorResponse(string $error, string $message, int $status): JsonResponse
    {
        return $this->json([
            'error' => $error,
            'message' => $message,
        ], $status);
    }

    /**
     * GET /pedidos-produccion
     */
    public function index(Request $request): JsonResponse
    {
        try {
            Log::info('[PedidosController] GET /pedidos-produccion');

            $filtros = [
                'estado' => $request->get('estado'),
                'search' => $request->get('search'),
            ];

            $usuario = Auth::user();
            $dto = ListarProduccionPedidosDTO::fromRequest(
                null,
                $filtros,
                $usuario?->id,
                (bool) ($usuario?->hasRole('asesor'))
            );
            $pedidos = $this->listarPedidosUseCase->ejecutar($dto);

            Log::info('[PedidosController] Listado obtenido', [
                'total' => $pedidos->total(),
            ]);

            return $this->json($pedidos);
        } catch (\Exception $e) {
            Log::error('[PedidosController] Error listando pedidos', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Error listando pedidos', $e->getMessage(), 500);
        }
    }

    /**
     * GET /pedidos-produccion/{id}
     */
    public function show(int|string $id): JsonResponse
    {
        try {
            Log::info('[PedidosController] GET /pedidos-produccion/{id}', ['id' => $id]);

            $dto = ObtenerProduccionPedidoDTO::fromRequest($id);
            $pedido = $this->obtenerPedidoUseCase->ejecutar($dto);

            if (!$pedido) {
                Log::warning('[PedidosController] Pedido no encontrado', ['id' => $id]);
                return $this->json([
                    'error' => 'Pedido no encontrado',
                ], 404);
            }

            Log::info('[PedidosController] Pedido obtenido', [
                'pedido_id' => $pedido->id,
            ]);

            return $this->json($pedido);
        } catch (\Exception $e) {
            Log::error('[PedidosController] Error obteniendo pedido', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Error obteniendo pedido', $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/pedidos
     */
    public function store(CrearPedidoProduccionRequest $request): JsonResponse
    {
        try {
            Log::info('[PedidosController] POST /api/pedidos');

            $validated = $request->validated();
            $validated['cantidad_inicial'] ??= 0;

            $dto = CrearProduccionPedidoDTO::fromRequest($validated);
            $pedido = $this->crearPedidoUseCase->ejecutar($dto);

            return $this->json($pedido, 201);
        } catch (\InvalidArgumentException $e) {
            Log::warning('[PedidosController] Validacion de negocio fallida', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Validacion de negocio fallida', $e->getMessage(), 422);
        } catch (\Exception $e) {
            Log::error('[PedidosController] Error creando pedido', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Error creando pedido', $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/pedidos/{id}
     */
    public function update(ActualizarPedidoProduccionRequest $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[PedidosController] PUT /api/pedidos/{id}', ['id' => $id]);

            $dto = ActualizarProduccionPedidoDTO::fromRequest($id, $request->validated());
            $pedido = $this->actualizarPedidoUseCase->ejecutar($dto);

            Log::info('[PedidosController] Pedido actualizado', [
                'pedido_id' => $pedido->id,
            ]);

            return $this->json($pedido);
        } catch (\InvalidArgumentException $e) {
            Log::warning('[PedidosController] Validacion fallida', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Validacion fallida', $e->getMessage(), 422);
        } catch (\Exception $e) {
            Log::error('[PedidosController] Error actualizando pedido', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Error actualizando pedido', $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/pedidos/{id}/estado
     */
    public function cambiarEstado(CambiarEstadoPedidoProduccionRequest $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[PedidosController] PUT /api/pedidos/{id}/estado', ['id' => $id]);

            $dto = CambiarEstadoPedidoDTO::fromRequest($id, $request->validated());
            $pedido = $this->cambiarEstadoUseCase->ejecutar($dto);

            Log::info('[PedidosController] Estado cambiado exitosamente', [
                'pedido_id' => $pedido->id,
                'nuevo_estado' => $pedido->estado,
            ]);

            return $this->json($pedido);
        } catch (\InvalidArgumentException $e) {
            Log::warning('[PedidosController] Transicion no permitida', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Transicion de estado no permitida', $e->getMessage(), 422);
        } catch (\Exception $e) {
            Log::error('[PedidosController] Error cambiando estado', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Error cambiando estado', $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/pedidos/{id}
     */
    public function destroy(EliminarPedidoProduccionRequest $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[PedidosController] DELETE /api/pedidos/{id}', ['id' => $id]);

            $validated = $request->validated();

            $command = new EliminarPedidoCommand(
                (int) $id,
                $validated['razon'] ?? 'Sin especificar'
            );
            $this->commandBus->execute($command);

            Log::info('[PedidosController] Pedido eliminado', ['pedido_id' => $id]);

            return $this->json([], 204);
        } catch (\Exception $e) {
            Log::error('[PedidosController] Error eliminando pedido', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Error eliminando pedido', $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/pedidos/filtro/estado
     */
    public function filtrarPorEstado(FiltrarPedidosPorEstadoRequest $request): JsonResponse
    {
        try {
            Log::info('[PedidosController] GET /api/pedidos/filtro/estado');

            $validated = $request->validated();
            $dto = FiltrarPedidosPorEstadoDTO::fromRequest($validated);
            $pedidos = $this->filtrarEstadoUseCase->ejecutar($dto);

            Log::info('[PedidosController] Filtrado por estado exitosamente', [
                'estado' => $validated['estado'],
                'total' => is_object($pedidos) && method_exists($pedidos, 'total') ? $pedidos->total() : count($pedidos),
            ]);

            return $this->json($pedidos);
        } catch (\InvalidArgumentException $e) {
            Log::warning('[PedidosController] Estado invalido', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Estado invalido', $e->getMessage(), 422);
        } catch (\Exception $e) {
            Log::error('[PedidosController] Error filtrando por estado', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Error filtrando pedidos', $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/pedidos/buscar/{numero}
     */
    public function buscarPorNumero(string $numero): JsonResponse
    {
        try {
            Log::info('[PedidosController] GET /api/pedidos/buscar/{numero}', ['numero' => $numero]);

            $dto = BuscarPedidoPorNumeroDTO::fromRequest($numero);
            $pedido = $this->buscarNumeroUseCase->ejecutar($dto);

            if (!$pedido) {
                Log::warning('[PedidosController] Pedido no encontrado', ['numero' => $numero]);
                return $this->json([
                    'error' => 'Pedido no encontrado',
                ], 404);
            }

            Log::info('[PedidosController] Pedido encontrado exitosamente', [
                'numero' => $numero,
                'pedido_id' => $pedido->id,
            ]);

            return $this->json($pedido);
        } catch (\Exception $e) {
            Log::error('[PedidosController] Error buscando pedido', [
                'numero' => $numero,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Error buscando pedido', $e->getMessage(), 500);
        }
    }
}
