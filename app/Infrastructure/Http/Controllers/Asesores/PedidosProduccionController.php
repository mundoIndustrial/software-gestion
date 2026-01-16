<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Domain\Shared\CQRS\QueryBus;
use App\Domain\Shared\CQRS\CommandBus;
use App\Domain\PedidoProduccion\Queries\ObtenerPedidoQuery;
use App\Domain\PedidoProduccion\Queries\ListarPedidosQuery;
use App\Domain\PedidoProduccion\Queries\FiltrarPedidosPorEstadoQuery;
use App\Domain\PedidoProduccion\Queries\BuscarPedidoPorNumeroQuery;
use App\Domain\PedidoProduccion\Queries\ObtenerPrendasPorPedidoQuery;
use App\Domain\PedidoProduccion\Commands\CrearPedidoCommand;
use App\Domain\PedidoProduccion\Commands\ActualizarPedidoCommand;
use App\Domain\PedidoProduccion\Commands\CambiarEstadoPedidoCommand;
use App\Domain\PedidoProduccion\Commands\AgregarPrendaAlPedidoCommand;
use App\Domain\PedidoProduccion\Commands\EliminarPedidoCommand;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * PedidosProduccionController - REFACTORIZADO CON CQRS
 * 
 * Responsabilidad:
 * - Recibir requests HTTP
 * - Validar entrada HTTP (no de negocio)
 * - Ejecutar Queries/Commands via buses
 * - Formatear respuestas HTTP
 * - Manejo de errores HTTP
 * 
 * Patrón: CQRS + Dependency Injection
 * SRP: Solo HTTP, nada de lógica de negocio
 * 
 * Nota: Toda la lógica de negocio está en:
 * - QueryHandlers (lecturas con cache)
 * - CommandHandlers (escrituras con transacciones)
 * - Validators (validaciones de dominio)
 * - Services (lógica reutilizable)
 */
class PedidosProduccionController
{
    public function __construct(
        private QueryBus $queryBus,
        private CommandBus $commandBus,
    ) {}

    /**
     * GET /api/pedidos
     * Listar todos los pedidos con paginación
     * 
     * Query Parameters:
     * - page: int (default 1)
     * - per_page: int (default 15)
     * - ordenar: string (default 'created_at')
     * - direccion: string (default 'desc')
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            Log::info(' [PedidosController] GET /api/pedidos');

            $validated = $request->validate([
                'page' => 'sometimes|integer|min:1',
                'per_page' => 'sometimes|integer|min:1|max:100',
                'ordenar' => 'sometimes|string|in:numero_pedido,cliente,created_at,estado',
                'direccion' => 'sometimes|string|in:asc,desc',
            ]);

            $pedidos = $this->queryBus->execute(new ListarPedidosQuery(
                page: $validated['page'] ?? 1,
                perPage: $validated['per_page'] ?? 15,
                ordenar: $validated['ordenar'] ?? 'created_at',
                direccion: $validated['direccion'] ?? 'desc',
            ));

            Log::info(' [PedidosController] Listado obtenido', [
                'total' => $pedidos->total(),
                'per_page' => $pedidos->perPage(),
            ]);

            return response()->json($pedidos, 200);

        } catch (\Exception $e) {
            Log::error(' [PedidosController] Error listando pedidos', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Error listando pedidos',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/pedidos/:id
     * Obtener un pedido específico
     * 
     * @param int|string $id
     * @return JsonResponse
     */
    public function show(int|string $id): JsonResponse
    {
        try {
            Log::info('🔍 [PedidosController] GET /api/pedidos/{id}', ['id' => $id]);

            $pedido = $this->queryBus->execute(new ObtenerPedidoQuery($id));

            if (!$pedido) {
                Log::warning(' [PedidosController] Pedido no encontrado', ['id' => $id]);
                return response()->json([
                    'error' => 'Pedido no encontrado',
                ], 404);
            }

            Log::info(' [PedidosController] Pedido obtenido', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
            ]);

            return response()->json($pedido, 200);

        } catch (\Exception $e) {
            Log::error(' [PedidosController] Error obteniendo pedido', [
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
     * 
     * Body:
     * {
     *   "numero_pedido": "PED-001",
     *   "cliente": "Cliente XYZ",
     *   "forma_pago": "contado",
     *   "asesor_id": 1,
     *   "cantidad_inicial": 0
     * }
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            Log::info(' [PedidosController] POST /api/pedidos');

            // Validación HTTP (sintaxis/tipos)
            $validated = $request->validate([
                'numero_pedido' => 'required|string|max:50',
                'cliente' => 'required|string|max:255',
                'forma_pago' => 'required|string|in:contado,credito,transferencia,cheque',
                'asesor_id' => 'required|integer|min:1',
                'cantidad_inicial' => 'sometimes|integer|min:0|default:0',
            ]);

            // Validación de negocio (uniqueness, etc) → PedidoValidator en handler
            $pedido = $this->commandBus->execute(new CrearPedidoCommand(
                numeroPedido: $validated['numero_pedido'],
                cliente: $validated['cliente'],
                formaPago: $validated['forma_pago'],
                asesorId: $validated['asesor_id'],
                cantidadInicial: $validated['cantidad_inicial'] ?? 0,
            ));

            Log::info(' [PedidosController] Pedido creado', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
            ]);

            return response()->json($pedido, 201);

        } catch (\InvalidArgumentException $e) {
            // Validación de negocio fallida
            Log::warning(' [PedidosController] Validación de negocio fallida', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Validación de negocio fallida',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error(' [PedidosController] Error creando pedido', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Error creando pedido',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/pedidos/:id
     * Actualizar pedido
     * 
     * Body (todos opcionales):
     * {
     *   "cliente": "Nuevo cliente",
     *   "forma_pago": "credito"
     * }
     * 
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function update(Request $request, int|string $id): JsonResponse
    {
        try {
            Log::info(' [PedidosController] PUT /api/pedidos/{id}', ['id' => $id]);

            $validated = $request->validate([
                'cliente' => 'sometimes|string|max:255',
                'forma_pago' => 'sometimes|string|in:contado,credito,transferencia,cheque',
            ]);

            $pedido = $this->commandBus->execute(new ActualizarPedidoCommand(
                pedidoId: $id,
                cliente: $validated['cliente'] ?? null,
                formaPago: $validated['forma_pago'] ?? null,
            ));

            Log::info(' [PedidosController] Pedido actualizado', [
                'pedido_id' => $pedido->id,
            ]);

            return response()->json($pedido, 200);

        } catch (\InvalidArgumentException $e) {
            Log::warning(' [PedidosController] Validación fallida', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Validación fallida',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error(' [PedidosController] Error actualizando pedido', [
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
     * PUT /api/pedidos/:id/estado
     * Cambiar estado de pedido
     * 
     * Body:
     * {
     *   "nuevo_estado": "completado",
     *   "razon": "Opcional: razón del cambio"
     * }
     * 
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function cambiarEstado(Request $request, int|string $id): JsonResponse
    {
        try {
            Log::info('🔄 [PedidosController] PUT /api/pedidos/{id}/estado', ['id' => $id]);

            $validated = $request->validate([
                'nuevo_estado' => 'required|string|in:activo,pendiente,completado,cancelado',
                'razon' => 'sometimes|string|max:500',
            ]);

            $pedido = $this->commandBus->execute(new CambiarEstadoPedidoCommand(
                pedidoId: $id,
                nuevoEstado: $validated['nuevo_estado'],
                razon: $validated['razon'] ?? null,
            ));

            Log::info(' [PedidosController] Estado cambiadoId', [
                'pedido_id' => $pedido->id,
                'nuevo_estado' => $pedido->estado,
            ]);

            return response()->json($pedido, 200);

        } catch (\InvalidArgumentException $e) {
            Log::warning(' [PedidosController] Transición no permitida', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Transición de estado no permitida',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error(' [PedidosController] Error cambiando estado', [
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
     * POST /api/pedidos/:id/prendas
     * Agregar prenda a pedido
     * 
     * Body:
     * {
     *   "nombre_prenda": "Pantalón",
     *   "cantidad": 10,
     *   "tipo": "sin_cotizacion|reflectivo",
     *   "tipo_manga": "corta",
     *   "tipo_broche": "botones",
     *   "color_id": 1,
     *   "tela_id": 1
     * }
     * 
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function agregarPrenda(Request $request, int|string $id): JsonResponse
    {
        try {
            Log::info(' [PedidosController] POST /api/pedidos/{id}/prendas', ['id' => $id]);

            $validated = $request->validate([
                'nombre_prenda' => 'required|string|max:255',
                'cantidad' => 'required|integer|min:1',
                'tipo' => 'required|string|in:sin_cotizacion,reflectivo',
                'tipo_manga' => 'required|string|max:100',
                'tipo_broche' => 'required|string|max:100',
                'color_id' => 'required|integer|min:1',
                'tela_id' => 'required|integer|min:1',
            ]);

            $pedido = $this->commandBus->execute(new AgregarPrendaAlPedidoCommand(
                pedidoId: $id,
                prendaData: [
                    'nombre_prenda' => $validated['nombre_prenda'],
                    'cantidad' => $validated['cantidad'],
                    'tipo_manga' => $validated['tipo_manga'],
                    'tipo_broche' => $validated['tipo_broche'],
                    'color_id' => $validated['color_id'],
                    'tela_id' => $validated['tela_id'],
                ],
                tipo: $validated['tipo'],
            ));

            Log::info(' [PedidosController] Prenda agregada', [
                'pedido_id' => $pedido->id,
                'cantidad_total' => $pedido->cantidad_total,
            ]);

            return response()->json($pedido, 201);

        } catch (\InvalidArgumentException $e) {
            Log::warning(' [PedidosController] Validación de prenda fallida', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Validación de prenda fallida',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error(' [PedidosController] Error agregando prenda', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Error agregando prenda',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/pedidos/:id
     * Eliminar pedido (soft delete)
     * 
     * Query Parameters:
     * - razon: string (razón de eliminación)
     * 
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        try {
            Log::info(' [PedidosController] DELETE /api/pedidos/{id}', ['id' => $id]);

            $validated = $request->validate([
                'razon' => 'sometimes|string|max:500',
            ]);

            $this->commandBus->execute(new EliminarPedidoCommand(
                pedidoId: $id,
                razon: $validated['razon'] ?? 'Sin especificar',
            ));

            Log::info(' [PedidosController] Pedido eliminado', ['pedido_id' => $id]);

            return response()->json([], 204);

        } catch (\Exception $e) {
            Log::error(' [PedidosController] Error eliminando pedido', [
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
     * 
     * Query Parameters:
     * - estado: string (requerido: activo|pendiente|completado|cancelado)
     * - page: int (default 1)
     * - per_page: int (default 15)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function filtrarPorEstado(Request $request): JsonResponse
    {
        try {
            Log::info('🔍 [PedidosController] GET /api/pedidos/filtro/estado');

            $validated = $request->validate([
                'estado' => 'required|string|in:activo,pendiente,completado,cancelado',
                'page' => 'sometimes|integer|min:1',
                'per_page' => 'sometimes|integer|min:1|max:100',
            ]);

            $pedidos = $this->queryBus->execute(new FiltrarPedidosPorEstadoQuery(
                estado: $validated['estado'],
                page: $validated['page'] ?? 1,
                perPage: $validated['per_page'] ?? 15,
            ));

            Log::info(' [PedidosController] Filtrado por estado', [
                'estado' => $validated['estado'],
                'total' => $pedidos->total(),
            ]);

            return response()->json($pedidos, 200);

        } catch (\InvalidArgumentException $e) {
            Log::warning(' [PedidosController] Estado inválido', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Estado inválido',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error(' [PedidosController] Error filtrando por estado', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Error filtrando pedidos',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/pedidos/buscar/:numero
     * Buscar pedido por número
     * 
     * @param string $numero
     * @return JsonResponse
     */
    public function buscarPorNumero(string $numero): JsonResponse
    {
        try {
            Log::info(' [PedidosController] GET /api/pedidos/buscar/{numero}', ['numero' => $numero]);

            $pedido = $this->queryBus->execute(new BuscarPedidoPorNumeroQuery($numero));

            if (!$pedido) {
                Log::warning(' [PedidosController] Pedido no encontrado', ['numero' => $numero]);
                return response()->json([
                    'error' => 'Pedido no encontrado',
                ], 404);
            }

            Log::info(' [PedidosController] Pedido encontrado', [
                'numero' => $numero,
                'pedido_id' => $pedido->id,
            ]);

            return response()->json($pedido, 200);

        } catch (\Exception $e) {
            Log::error(' [PedidosController] Error buscando pedido', [
                'numero' => $numero,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Error buscando pedido',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/pedidos/:id/prendas
     * Obtener todas las prendas de un pedido
     * 
     * @param int|string $id
     * @return JsonResponse
     */
    public function obtenerPrendas(int|string $id): JsonResponse
    {
        try {
            Log::info(' [PedidosController] GET /api/pedidos/{id}/prendas', ['id' => $id]);

            $prendas = $this->queryBus->execute(new ObtenerPrendasPorPedidoQuery($id));

            Log::info(' [PedidosController] Prendas obtenidas', [
                'pedido_id' => $id,
                'total_prendas' => $prendas->count(),
            ]);

            return response()->json($prendas, 200);

        } catch (\Exception $e) {
            Log::error(' [PedidosController] Error obteniendo prendas', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Error obteniendo prendas',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/pedidos/render-item-card
     * Renderizar componente item-card para agregar dinámicamente
     * 
     * Body:
     * {
     *   item: { objeto con datos del item },
     *   index: número de índice
     * }
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function renderItemCard(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'item' => 'required|array',
                'index' => 'required|integer|min:0',
            ]);

            $item = $validated['item'];
            $index = $validated['index'];

            // Renderizar el componente Blade
            $html = view('asesores.pedidos.components.item-card', [
                'item' => $item,
                'index' => $index,
            ])->render();

            return response()->json([
                'success' => true,
                'html' => $html,
            ], 200);

        } catch (\Exception $e) {
            Log::error(' [PedidosController] Error renderizando item-card', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error renderizando componente',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
