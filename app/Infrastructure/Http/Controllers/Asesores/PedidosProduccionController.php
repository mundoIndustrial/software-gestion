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
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Application\Pedidos\UseCases\ListarProduccionPedidosUseCase;
use App\Application\Pedidos\UseCases\ObtenerProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\CrearProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\ActualizarProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\AnularProduccionPedidoUseCase;
use App\Application\Pedidos\UseCases\CambiarEstadoPedidoUseCase;
use App\Application\Pedidos\UseCases\AgregarPrendaAlPedidoUseCase;
use App\Application\Pedidos\UseCases\FiltrarPedidosPorEstadoUseCase;
use App\Application\Pedidos\UseCases\BuscarPedidoPorNumeroUseCase;
use App\Application\Pedidos\UseCases\ObtenerPrendasPedidoUseCase;
use App\Application\Pedidos\UseCases\ActualizarPrendaPedidoUseCase;
use App\Application\Pedidos\UseCases\AgregarPrendaCompletaUseCase;
use App\Application\Pedidos\UseCases\ActualizarPrendaCompletaUseCase;
use App\Application\Pedidos\UseCases\RenderItemCardUseCase;
use App\Application\Pedidos\DTOs\ListarProduccionPedidosDTO;
use App\Application\Pedidos\DTOs\ObtenerProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\CrearProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\ActualizarProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\AnularProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\CambiarEstadoPedidoDTO;
use App\Application\Pedidos\DTOs\AgregarPrendaAlPedidoDTO;
use App\Application\Pedidos\DTOs\FiltrarPedidosPorEstadoDTO;
use App\Application\Pedidos\DTOs\BuscarPedidoPorNumeroDTO;
use App\Application\Pedidos\DTOs\ObtenerPrendasPedidoDTO;
use App\Application\Pedidos\DTOs\ActualizarPrendaPedidoDTO;
use App\Application\Pedidos\DTOs\AgregarPrendaCompletaDTO;
use App\Application\Pedidos\DTOs\ActualizarPrendaCompletaDTO;
use App\Application\Pedidos\DTOs\RenderItemCardDTO;

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
        private PedidoProduccionRepository $prendaPedidoRepository,
        private ListarProduccionPedidosUseCase $listarPedidosUseCase,
        private ObtenerProduccionPedidoUseCase $obtenerPedidoUseCase,
        private CrearProduccionPedidoUseCase $crearPedidoUseCase,
        private ActualizarProduccionPedidoUseCase $actualizarPedidoUseCase,
        private AnularProduccionPedidoUseCase $anularPedidoUseCase,
        private CambiarEstadoPedidoUseCase $cambiarEstadoUseCase,
        private AgregarPrendaAlPedidoUseCase $agregarPrendaUseCase,
        private FiltrarPedidosPorEstadoUseCase $filtrarEstadoUseCase,
        private BuscarPedidoPorNumeroUseCase $buscarNumeroUseCase,
        private ObtenerPrendasPedidoUseCase $obtenerPrendasUseCase,
        private ActualizarPrendaPedidoUseCase $actualizarPrendaUseCase,
        private AgregarPrendaCompletaUseCase $agregarPrendaCompletaUseCase,
        private ActualizarPrendaCompletaUseCase $actualizarPrendaCompletaUseCase,
        private RenderItemCardUseCase $renderItemCardUseCase,
    ) {}

    /**
     * GET /api/pedidos
     * Listar todos los pedidos con paginación - DELEGADO A USE CASE
     * 
     * Query Parameters:
     * - page: int (default 1)
     * - per_page: int (default 15)
     * - estado: string (optional)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            Log::info('[PedidosProduccionController] GET /api/pedidos');

            $filtros = [
                'estado' => $request->get('estado'),
                'search' => $request->get('search'),
            ];

            $dto = ListarProduccionPedidosDTO::fromRequest(null, $filtros);
            $pedidos = $this->listarPedidosUseCase->ejecutar($dto);

            Log::info('[PedidosProduccionController] Listado obtenido', [
                'total' => $pedidos->total(),
            ]);

            return response()->json($pedidos, 200);

        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error listando pedidos', [
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
     * Obtener un pedido específico - DELEGADO A USE CASE
     * 
     * @param int|string $id
     * @return JsonResponse
     */
    public function show(int|string $id): JsonResponse
    {
        try {
            Log::info('[PedidosProduccionController] GET /api/pedidos/{id}', ['id' => $id]);

            $dto = ObtenerProduccionPedidoDTO::fromRoute($id);
            $pedido = $this->obtenerPedidoUseCase->ejecutar($dto);

            if (!$pedido) {
                Log::warning('[PedidosProduccionController] Pedido no encontrado', ['id' => $id]);
                return response()->json([
                    'error' => 'Pedido no encontrado',
                ], 404);
            }

            Log::info('[PedidosProduccionController] Pedido obtenido', [
                'pedido_id' => $pedido->id,
            ]);

            return response()->json($pedido, 200);

        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error obteniendo pedido', [
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
     * Crear nuevo pedido - DELEGADO A USE CASE
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            Log::info('[PedidosProduccionController] POST /api/pedidos');

            // Validación HTTP (sintaxis/tipos)
            $validated = $request->validate([
                'numero_pedido' => 'required|string|max:50',
                'cliente' => 'required|string|max:255',
                'forma_pago' => 'required|string|in:contado,credito,transferencia,cheque',
                'asesor_id' => 'required|integer|min:1',
                'cantidad_inicial' => 'sometimes|integer|min:0|default:0',
            ]);

            // Usar Use Case DDD
            $dto = CrearProduccionPedidoDTO::fromRequest(null, $validated);
            $pedido = $this->crearPedidoUseCase->ejecutar($dto);

            Log::info('[PedidosProduccionController] Pedido creado', [
                'pedido_id' => $pedido->id,
            ]);

            return response()->json($pedido, 201);

        } catch (\InvalidArgumentException $e) {
            Log::warning('[PedidosProduccionController] Validación de negocio fallida', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Validación de negocio fallida',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error creando pedido', [
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
     * Actualizar pedido - DELEGADO A USE CASE
     * 
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function update(Request $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[PedidosProduccionController] PUT /api/pedidos/{id}', ['id' => $id]);

            $validated = $request->validate([
                'cliente' => 'sometimes|string|max:255',
                'forma_pago' => 'sometimes|string|in:contado,credito,transferencia,cheque',
            ]);

            // Usar Use Case DDD
            $dto = ActualizarProduccionPedidoDTO::fromRequest($id, $validated);
            $pedido = $this->actualizarPedidoUseCase->ejecutar($dto);

            Log::info('[PedidosProduccionController] Pedido actualizado', [
                'pedido_id' => $pedido->id,
            ]);

            return response()->json($pedido, 200);

        } catch (\InvalidArgumentException $e) {
            Log::warning('[PedidosProduccionController] Validación fallida', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Validación fallida',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error actualizando pedido', [
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
            Log::info('[PedidosProduccionController] PUT /api/pedidos/{id}/estado', ['id' => $id]);

            $validated = $request->validate([
                'nuevo_estado' => 'required|string|in:activo,pendiente,completado,cancelado',
                'razon' => 'sometimes|string|max:500',
            ]);

            // Usar Use Case DDD
            $dto = CambiarEstadoPedidoDTO::fromRequest($id, $validated);
            $pedido = $this->cambiarEstadoUseCase->ejecutar($dto);

            Log::info('[PedidosProduccionController] Estado cambiado exitosamente', [
                'pedido_id' => $pedido->id,
                'nuevo_estado' => $pedido->estado,
            ]);

            return response()->json($pedido, 200);

        } catch (\InvalidArgumentException $e) {
            Log::warning('[PedidosProduccionController] Transición no permitida', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Transición de estado no permitida',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error cambiando estado', [
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
     * Agregar prenda a pedido - DELEGADO A USE CASE
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
            Log::info('[PedidosProduccionController] POST /api/pedidos/{id}/prendas', ['id' => $id]);

            $validated = $request->validate([
                'nombre_prenda' => 'required|string|max:255',
                'cantidad' => 'required|integer|min:1',
                'tipo' => 'required|string|in:sin_cotizacion,reflectivo',
                'tipo_manga' => 'required|string|max:100',
                'tipo_broche' => 'required|string|max:100',
                'color_id' => 'required|integer|min:1',
                'tela_id' => 'required|integer|min:1',
            ]);

            // Usar Use Case DDD
            $dto = AgregarPrendaAlPedidoDTO::fromRequest($id, $validated);
            $pedido = $this->agregarPrendaUseCase->ejecutar($dto);

            Log::info('[PedidosProduccionController] Prenda agregada exitosamente', [
                'pedido_id' => $pedido->id,
            ]);

            return response()->json($pedido, 201);

        } catch (\InvalidArgumentException $e) {
            Log::warning('[PedidosProduccionController] Validación de prenda fallida', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Validación de prenda fallida',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error agregando prenda', [
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
            Log::info('[PedidosProduccionController] DELETE /api/pedidos/{id}', ['id' => $id]);

            $validated = $request->validate([
                'razon' => 'sometimes|string|max:500',
            ]);

            // Usar Use Case DDD
            $dto = AnularProduccionPedidoDTO::fromRequest((string)$id, ['razon' => $validated['razon'] ?? 'Sin especificar']);
            $this->anularPedidoUseCase->ejecutar($dto);

            Log::info('[PedidosProduccionController] Pedido eliminado', ['pedido_id' => $id]);

            return response()->json([], 204);

        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error eliminando pedido', [
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
     * Filtrar pedidos por estado - DELEGADO A USE CASE
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
            Log::info('[PedidosProduccionController] GET /api/pedidos/filtro/estado');

            $validated = $request->validate([
                'estado' => 'required|string|in:activo,pendiente,completado,cancelado',
                'page' => 'sometimes|integer|min:1',
                'per_page' => 'sometimes|integer|min:1|max:100',
            ]);

            // Usar Use Case DDD
            $dto = FiltrarPedidosPorEstadoDTO::fromRequest($validated);
            $pedidos = $this->filtrarEstadoUseCase->ejecutar($dto);

            Log::info('[PedidosProduccionController] Filtrado por estado exitosamente', [
                'estado' => $validated['estado'],
                'total' => is_object($pedidos) && method_exists($pedidos, 'total') ? $pedidos->total() : count($pedidos),
            ]);

            return response()->json($pedidos, 200);

        } catch (\InvalidArgumentException $e) {
            Log::warning('[PedidosProduccionController] Estado inválido', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Estado inválido',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error filtrando por estado', [
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
     * Buscar pedido por número - DELEGADO A USE CASE
     * 
     * @param string $numero
     * @return JsonResponse
     */
    public function buscarPorNumero(string $numero): JsonResponse
    {
        try {
            Log::info('[PedidosProduccionController] GET /api/pedidos/buscar/{numero}', ['numero' => $numero]);

            // Usar Use Case DDD
            $dto = BuscarPedidoPorNumeroDTO::fromRequest($numero);
            $pedido = $this->buscarNumeroUseCase->ejecutar($dto);

            if (!$pedido) {
                Log::warning('[PedidosProduccionController] Pedido no encontrado', ['numero' => $numero]);
                return response()->json([
                    'error' => 'Pedido no encontrado',
                ], 404);
            }

            Log::info('[PedidosProduccionController] Pedido encontrado exitosamente', [
                'numero' => $numero,
                'pedido_id' => $pedido->id,
            ]);

            return response()->json($pedido, 200);

        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error buscando pedido', [
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
     * Obtener todas las prendas de un pedido - DELEGADO A USE CASE
     * 
     * @param int|string $id
     * @return JsonResponse
     */
    public function obtenerPrendas(int|string $id): JsonResponse
    {
        try {
            Log::info('[PedidosProduccionController] GET /api/pedidos/{id}/prendas', ['id' => $id]);

            // Usar Use Case DDD
            $dto = ObtenerPrendasPedidoDTO::fromRoute($id);
            $prendas = $this->obtenerPrendasUseCase->ejecutar($dto);

            Log::info('[PedidosProduccionController] Prendas obtenidas exitosamente', [
                'pedido_id' => $id,
                'total_prendas' => $prendas->count(),
            ]);

            return response()->json($prendas, 200);

        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error obteniendo prendas', [
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
     * Renderizar componente item-card para agregar dinámicamente - DELEGADO A USE CASE
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

            // Usar Use Case DDD
            $dto = RenderItemCardDTO::fromRequest($validated);
            $html = $this->renderItemCardUseCase->ejecutar($dto);

            return response()->json([
                'success' => true,
                'html' => $html,
            ], 200);

        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error renderizando item-card', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error renderizando componente',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/pedidos-produccion/actualizar-prenda
     * Actualizar datos de una prenda específica dentro de un pedido - DELEGADO A USE CASE
     */
    public function actualizarPrenda(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'pedidoId' => 'required|numeric',
                'prendasIndex' => 'required|numeric|min:0',
                'nombre' => 'sometimes|nullable|string',
                'descripcion' => 'sometimes|nullable|string',
                'talla_referencia' => 'sometimes|nullable|string',
                'tallas' => 'sometimes|nullable|array',
                'infoTecnica' => 'sometimes|nullable|array',
                'observaciones' => 'sometimes|nullable|string',
            ]);

            // Usar Use Case DDD
            $dto = ActualizarPrendaPedidoDTO::fromRequest($validated['pedidoId'], $validated);
            $prenda = $this->actualizarPrendaUseCase->ejecutar($dto);

            Log::info('[PedidosProduccionController] Prenda actualizada exitosamente', [
                'pedido_id' => $validated['pedidoId'],
                'prenda_index' => $validated['prendasIndex'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Prenda actualizada correctamente',
                'prenda' => $prenda,
            ], 200);

        } catch (\InvalidArgumentException $e) {
            Log::warning('[PedidosProduccionController] Validación de prenda fallida', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);

        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error actualizando prenda', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar prenda: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /asesores/pedidos/{id}/agregar-prenda
     * Agregar prenda completa (con telas e imágenes) al pedido en edición - DELEGADO A USE CASE
     */
    public function agregarPrendaCompleta(Request $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[PedidosProduccionController] POST /asesores/pedidos/{id}/agregar-prenda', ['id' => $id]);

            // Validar datos básicos
            $validated = $request->validate([
                'nombre_prenda' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'origen' => 'required|string|in:bodega,confeccion',
                'cantidad_talla' => 'nullable|json',
                'procesos' => 'nullable|json',
                'novedad' => 'required|string|max:500',
                'imagenes' => 'nullable|array',
                'imagenes.*' => 'nullable|image|max:5120',
                'telas' => 'nullable|array',
            ]);

            // Procesar imágenes de prenda
            $imagenesGuardadas = [];
            if ($request->hasFile('imagenes')) {
                foreach ($request->file('imagenes') as $imagen) {
                    $path = $imagen->store('prendas', 'public');
                    $imagenesGuardadas[] = $path;
                }
            }

            // Usar Use Case DDD
            $dto = AgregarPrendaCompletaDTO::fromRequest($id, $validated, $imagenesGuardadas);
            $prenda = $this->agregarPrendaCompletaUseCase->ejecutar($dto);

            Log::info('[PedidosProduccionController] Prenda completa agregada exitosamente', [
                'pedido_id' => $id,
                'prenda_id' => $prenda->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Prenda agregada correctamente a la base de datos',
                'prenda' => $prenda->toArray(),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[PedidosProduccionController] Validación fallida', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error agregando prenda completa', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al agregar prenda: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /asesores/pedidos/{id}/actualizar-prenda
     * Actualizar una prenda existente en un pedido - DELEGADO A USE CASE
     */
    public function actualizarPrendaCompleta(Request $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[PedidosProduccionController] POST /asesores/pedidos/{id}/actualizar-prenda', ['id' => $id]);

            // Validar datos básicos
            $validated = $request->validate([
                'prenda_id' => 'required|numeric|min:1',
                'nombre_prenda' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'origen' => 'required|string|in:bodega,confeccion',
                'cantidad_talla' => 'nullable|json',
                'procesos' => 'nullable|json',
                'novedad' => 'required|string|max:500',
                'imagenes' => 'nullable|array',
                'imagenes.*' => 'nullable|image|max:5120',
                'telas' => 'nullable|array',
            ]);

            // Procesar imágenes de prenda
            $imagenesGuardadas = [];
            if ($request->hasFile('imagenes')) {
                foreach ($request->file('imagenes') as $imagen) {
                    $path = $imagen->store('prendas', 'public');
                    $imagenesGuardadas[] = $path;
                }
            }

            // Usar Use Case DDD
            $dto = ActualizarPrendaCompletaDTO::fromRequest($id, $validated, $imagenesGuardadas);
            $prenda = $this->actualizarPrendaCompletaUseCase->ejecutar($dto);

            Log::info('[PedidosProduccionController] Prenda completa actualizada exitosamente', [
                'pedido_id' => $id,
                'prenda_id' => $prenda->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Prenda actualizada correctamente en la base de datos',
                'prenda' => $prenda->toArray(),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[PedidosProduccionController] Validación fallida en actualización', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error actualizando prenda completa', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar prenda: ' . $e->getMessage(),
            ], 500);
        }
    }
}
