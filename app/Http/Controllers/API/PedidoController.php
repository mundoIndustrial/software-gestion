<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Application\Pedidos\UseCases\CrearPedidoUseCase;
use App\Application\Pedidos\UseCases\ConfirmarPedidoUseCase;
use App\Application\Pedidos\DTOs\CrearPedidoDTO;
use App\Domain\Pedidos\Repositories\PedidoRepository;
use App\Domain\Pedidos\Exceptions\PedidoNoEncontrado;
use App\Domain\Pedidos\Exceptions\EstadoPedidoInvalido;

/**
 * PedidoController
 * 
 * Controlador para gestionar pedidos usando DDD (Fase 3)
 * 
 * Endpoints:
 * - POST /api/pedidos → Crear pedido (CrearPedidoUseCase)
 * - PATCH /api/pedidos/{id}/confirmar → Confirmar pedido (ConfirmarPedidoUseCase)
 * - GET /api/pedidos/{id} → Obtener pedido (Lectura directa)
 */
class PedidoController extends Controller
{
    public function __construct(
        private CrearPedidoUseCase $crearPedidoUseCase,
        private ConfirmarPedidoUseCase $confirmarPedidoUseCase,
        private PedidoRepository $pedidoRepository
    ) {}

    /**
     * POST /api/pedidos
     * 
     * Crear un nuevo pedido usando DDD
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validar entrada básica
            $request->validate([
                'cliente_id' => 'required|integer',
                'descripcion' => 'required|string|max:1000',
                'observaciones' => 'nullable|string|max:1000',
                'prendas' => 'required|array|min:1',
                'prendas.*.prenda_id' => 'required|integer',
                'prendas.*.descripcion' => 'required|string',
                'prendas.*.cantidad' => 'required|integer|min:1',
                'prendas.*.tallas' => 'required|array',
            ]);

            // Crear DTO desde request
            $dto = CrearPedidoDTO::fromRequest($request->all());

            // Ejecutar Use Case
            $response = $this->crearPedidoUseCase->ejecutar($dto);

            return response()->json([
                'success' => true,
                'message' => $response->mensaje,
                'data' => $response->toArray()
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PATCH /api/pedidos/{id}/confirmar
     * 
     * Confirmar un pedido existente
     */
    public function confirmar(int $id): JsonResponse
    {
        try {
            // Ejecutar Use Case
            $response = $this->confirmarPedidoUseCase->ejecutar($id);

            return response()->json([
                'success' => true,
                'message' => 'Pedido confirmado exitosamente',
                'data' => $response->toArray()
            ], 200);

        } catch (PedidoNoEncontrado $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado',
            ], 404);

        } catch (EstadoPedidoInvalido $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede confirmar el pedido: ' . $e->getMessage(),
            ], 422);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/pedidos/{id}
     * 
     * Obtener un pedido (lectura - CQRS read side)
     */
    public function show(int $id): JsonResponse
    {
        try {
            $pedido = $this->pedidoRepository->porId($id);

            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $pedido->id(),
                    'numero' => (string)$pedido->numero(),
                    'cliente_id' => $pedido->clienteId(),
                    'descripcion' => $pedido->descripcion(),
                    'estado' => $pedido->estado()->valor(),
                    'observaciones' => $pedido->observaciones(),
                    'total_prendas' => $pedido->totalPrendas(),
                    'total_articulos' => $pedido->totalArticulos(),
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pedido: ' . $e->getMessage()
            ], 500);
        }
    }
}
