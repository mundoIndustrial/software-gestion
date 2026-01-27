<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Domain\Shared\CQRS\QueryBus;
use App\Domain\Shared\CQRS\CommandBus;
use App\Domain\Pedidos\Queries\ObtenerPedidoQuery;
use App\Domain\Pedidos\Queries\ListarPedidosQuery;
use App\Domain\Pedidos\Queries\FiltrarPedidosPorEstadoQuery;
use App\Domain\Pedidos\Queries\BuscarPedidoPorNumeroQuery;
use App\Domain\Pedidos\Queries\ObtenerPrendasPorPedidoQuery;
use App\Domain\Pedidos\Commands\CrearPedidoCommand;
use App\Domain\Pedidos\Commands\ActualizarPedidoCommand;
use App\Domain\Pedidos\Commands\CambiarEstadoPedidoCommand;
use App\Domain\Pedidos\Commands\AgregarPrendaAlPedidoCommand;
use App\Domain\Pedidos\Commands\EliminarPedidoCommand;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use App\Models\Cotizacion;
use App\Models\TipoProceso;
use App\Models\Festivo;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
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
use App\Application\Pedidos\UseCases\ObtenerProcesosPorPedidoUseCase;
use App\Application\Pedidos\UseCases\EditarProcesoUseCase;
use App\Application\Pedidos\UseCases\EliminarProcesoUseCase;
use App\Application\Pedidos\UseCases\CrearProcesoUseCase;
use App\Application\Pedidos\UseCases\ObtenerHistorialProcesosUseCase;
use App\Application\Pedidos\UseCases\ActualizarVariantePrendaUseCase;
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
        private ObtenerProcesosPorPedidoUseCase $obtenerProcesosPedidoUseCase,
        private EditarProcesoUseCase $editarProcesoUseCase,
        private EliminarProcesoUseCase $eliminarProcesoUseCase,
        private CrearProcesoUseCase $crearProcesoUseCase,
        private ObtenerHistorialProcesosUseCase $obtenerHistorialProcesosUseCase,
        private ActualizarVariantePrendaUseCase $actualizarVariantePrendaUseCase,
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
                'epps' => 'sometimes|array',
                'epps.*.epp_id' => 'required_with:epps|integer|min:1',
                'epps.*.cantidad' => 'sometimes|integer|min:1',
                'epps.*.observaciones' => 'sometimes|nullable|string|max:1000',
            ]);

            // Usar Use Case DDD
            $dto = CrearProduccionPedidoDTO::fromRequest($validated);
            $pedido = $this->crearPedidoUseCase->ejecutar($dto);

            Log::info('[PedidosProduccionController] Pedido creado', [
                'pedido_id' => is_array($pedido) ? ($pedido['id'] ?? null) : (method_exists($pedido, 'getId') ? $pedido->getId() : null),
                'epps_procesados' => count($validated['epps'] ?? []),
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

            // Usar Command Bus para eliminar el pedido
            $command = new EliminarPedidoCommand(
                (int)$id,
                $validated['razon'] ?? 'Sin especificar'
            );
            $this->commandBus->dispatch($command);

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
                'imagenes_existentes' => 'nullable|json', // Imágenes existentes de BD a preservar
                'telas' => 'nullable|array',
            ]);

            // Procesar imágenes de prenda con sistema centralizado
            $imagenesGuardadas = [];
            $tempUuid = \Illuminate\Support\Str::uuid()->toString();
            
            if ($request->hasFile('imagenes')) {
                $imageUploadService = app(\App\Application\Services\ImageUploadService::class);
                
                foreach ($request->file('imagenes') as $imagen) {
                    // Usar ImageUploadService para guardar en temp/{uuid}/prendas/
                    $rutas = $imageUploadService->processAndSaveImage($imagen, 'prendas', $tempUuid);
                    // Guardar ruta WebP para relocalizar después
                    $imagenesGuardadas[] = $rutas['webp'] ?? $rutas[0];
                }
            }

            // Procesar imágenes existentes que deben preservarse
            $imagenesExistentes = [];
            if ($request->input('imagenes_existentes')) {
                try {
                    $imagenesExistentes = json_decode($request->input('imagenes_existentes'), true) ?? [];
                } catch (\Exception $e) {
                    Log::warning('[PedidosProduccionController] Error decodificando imagenes_existentes', ['error' => $e->getMessage()]);
                }
            }

            // Usar Use Case DDD
            $dto = AgregarPrendaCompletaDTO::fromRequest($id, $validated, $imagenesGuardadas, $imagenesExistentes);
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

            // 🔍 DEBUG: Ver qué datos recibe el request
            Log::info('[PedidosProduccionController] Request raw data', [
                'origen' => $request->input('origen'),
                'de_bodega' => $request->input('de_bodega'),
                'all_inputs' => $request->all()
            ]);

            // Validar datos básicos
            $validated = $request->validate([
                'prenda_id' => 'required|numeric|min:1',
                'nombre_prenda' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'origen' => 'nullable|string|in:bodega,confeccion',
                'de_bodega' => 'nullable|in:0,1',
                'tallas' => 'nullable|json',
                'variantes' => 'nullable|json',
                'colores_telas' => 'nullable|json',
                'fotos_telas' => 'nullable|json',
                'procesos' => 'nullable|json',
                'fotos_procesos' => 'nullable|json',
                'novedad' => 'required|string|max:500',
                'imagenes' => 'nullable|array',
                'imagenes.*' => 'nullable|image|max:5120',
                'imagenes_existentes' => 'nullable|json', // Imágenes existentes de BD a preservar
            ]);

            // Procesar imágenes de prenda (convertir a WebP)
            $imagenesGuardadas = [];
            if ($request->hasFile('imagenes')) {
                $prendaFotoService = new \App\Domain\Pedidos\Services\PrendaFotoService();
                foreach ($request->file('imagenes') as $imagen) {
                    $rutas = $prendaFotoService->procesarFoto($imagen);
                    $imagenesGuardadas[] = $rutas;
                }
            }

            // NUEVO: Procesar imágenes de telas (anidadas en FormData)
            $telasConImagenes = [];
            $allFiles = $request->files->all();
            foreach ($allFiles as $key => $value) {
                if (strpos($key, 'telas[') === 0 && strpos($key, '][imagenes]') !== false) {
                    if (is_array($value) && !empty($value)) {
                        $telaFotoService = new \App\Domain\Pedidos\Services\TelaFotoService();
                        foreach ($value as $imagen) {
                            if ($imagen && $imagen->isValid()) {
                                $rutas = $telaFotoService->procesarFoto($imagen);
                                $telasConImagenes[] = $rutas;
                            }
                        }
                    }
                }
            }

            // NUEVO: Procesar imágenes de procesos (anidadas en FormData)
            $procesosConImagenes = [];
            foreach ($allFiles as $key => $value) {
                if (strpos($key, 'procesos[') === 0 && strpos($key, '][imagenes]') !== false) {
                    if (is_array($value) && !empty($value)) {
                        $procesoFotoService = new \App\Domain\Pedidos\Services\ProcesoFotoService();
                        foreach ($value as $imagen) {
                            if ($imagen && $imagen->isValid()) {
                                $rutas = $procesoFotoService->procesarFoto($imagen);
                                $procesosConImagenes[] = $rutas;
                            }
                        }
                    }
                }
            }

            // Procesar imágenes existentes que deben preservarse
            $imagenesExistentes = [];
            if ($request->input('imagenes_existentes')) {
                try {
                    $imagenesExistentes = json_decode($request->input('imagenes_existentes'), true) ?? [];
                } catch (\Exception $e) {
                    Log::warning('[PedidosProduccionController] Error decodificando imagenes_existentes', ['error' => $e->getMessage()]);
                }
            }

            // Usar Use Case DDD
            Log::info('[PedidosProduccionController] Datos validados para actualizar prenda', [
                'origen_recibido' => $validated['origen'] ?? 'NO ENVIADO',
                'de_bodega_recibido' => $validated['de_bodega'] ?? 'NO ENVIADO',
                'tallas_recibidas' => $validated['tallas'] ?? 'NO ENVIADAS',
                'variantes_recibidas' => $validated['variantes'] ?? 'NO ENVIADAS',
                'procesos' => $validated['procesos'] ?? 'NO ENVIADOS',
                'imagenes_procesadas' => count($imagenesGuardadas),
                'imagenes_existentes' => count($imagenesExistentes),
                'novedad_recibida' => $validated['novedad'] ?? 'SIN NOVEDAD',
            ]);
            
            // IMPORTANTE: Usar $validated['prenda_id'], NO $id (que es pedido_id)
            $dto = ActualizarPrendaCompletaDTO::fromRequest($validated['prenda_id'], $validated, $imagenesGuardadas, $imagenesExistentes);
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

    /**
     * GET /api/pedidos/{id}/procesos
     * Obtener procesos de un pedido con cálculo de días hábiles - DELEGADO A USE CASE
     */
    public function getProcesos($id): JsonResponse
    {
        try {
            Log::info('[PedidosProduccionController] GET /procesos', ['id' => $id]);

            $resultado = $this->obtenerProcesosPedidoUseCase->ejecutar($id);

            return response()->json($resultado, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('[PedidosProduccionController] Pedido no encontrado', ['id' => $id]);

            return response()->json([
                'error' => 'No se encontró la orden o no tiene permiso para verla'
            ], 404);
        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error en getProcesos', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Error al obtener procesos'
            ], 500);
        }
    }

    /**
     * POST /api/pedidos/{id}/procesos
     * Crear un nuevo proceso - DELEGADO A USE CASE
     */
    public function crearProceso(Request $request): JsonResponse
    {
        try {
            Log::info('[PedidosProduccionController] POST /procesos', ['data' => $request->all()]);

            $validated = $request->validate([
                'numero_pedido' => 'required|integer',
                'proceso' => 'required|string|max:255',
                'fecha_inicio' => 'required|date',
                'encargado' => 'nullable|string|max:255',
                'estado_proceso' => 'required|in:Pendiente,En Progreso,Completado,Pausado',
            ]);

            $resultado = $this->crearProcesoUseCase->ejecutar($validated);

            return response()->json($resultado, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[PedidosProduccionController] Validación fallida en crear proceso', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error creando proceso', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el proceso: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PATCH /api/pedidos/{id}/procesos/{procesoId}
     * Editar un proceso existente - DELEGADO A USE CASE
     */
    public function editarProceso(Request $request, $id): JsonResponse
    {
        try {
            Log::info('[PedidosProduccionController] PATCH /procesos/{id}', ['id' => $id]);

            $validated = $request->validate([
                'numero_pedido' => 'required|integer',
                'proceso' => 'required|string|max:255',
                'fecha_inicio' => 'required|date',
                'encargado' => 'nullable|string|max:255',
                'estado_proceso' => 'required|in:Pendiente,En Progreso,Completado,Pausado',
                'observaciones' => 'nullable|string',
            ]);

            $resultado = $this->editarProcesoUseCase->ejecutar((int)$id, $validated);

            return response()->json($resultado, 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[PedidosProduccionController] Validación fallida en editar proceso', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors()
            ], 422);
        } catch (\DomainException $e) {
            Log::warning('[PedidosProduccionController] Error de dominio en editar proceso', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error editando proceso', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al editar proceso: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/pedidos/{id}/procesos/{procesoId}
     * Eliminar un proceso - DELEGADO A USE CASE
     */
    public function eliminarProceso(Request $request, $id): JsonResponse
    {
        try {
            Log::info('[PedidosProduccionController] DELETE /procesos/{id}', ['id' => $id]);

            $validated = $request->validate([
                'numero_pedido' => 'required|integer',
            ]);

            $resultado = $this->eliminarProcesoUseCase->ejecutar((int)$id, $validated['numero_pedido']);

            return response()->json($resultado, 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[PedidosProduccionController] Validación fallida en eliminar proceso', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors()
            ], 422);
        } catch (\DomainException $e) {
            Log::warning('[PedidosProduccionController] Error de dominio en eliminar proceso', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error eliminando proceso', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar proceso: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/pedidos/{numeroPedido}/procesos/historial
     * Obtener historial de procesos - DELEGADO A USE CASE
     */
    public function obtenerHistorial($numeroPedido): JsonResponse
    {
        try {
            Log::info('[PedidosProduccionController] GET /procesos/historial', ['numero_pedido' => $numeroPedido]);

            $resultado = $this->obtenerHistorialProcesosUseCase->ejecutar((int)$numeroPedido);

            return response()->json($resultado, 200);
        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error al obtener historial', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial'
            ], 500);
        }
    }

    /**
     * GET /api/tallas-disponibles
     * Obtener catálogo de tallas disponibles por género
     * 
     * Parámetros opcionales:
     * - ?genero=DAMA (retorna solo ese género)
     * - ?prendaId=123 (retorna tallas CON CANTIDADES de esa prenda)
     * 
     * Retorna: 
     *   - Con prendaId: { DAMA: {S: 10, M: 15}, CABALLERO: {...} }
     *   - Sin prendaId: { DAMA: ['XS', 'S', 'M', 'L'], CABALLERO: [...] }
     */
    public function obtenerTallasDisponibles(Request $request): JsonResponse
    {
        try {
            Log::info('[PedidosProduccionController] GET /api/tallas-disponibles', [
                'params' => $request->all()
            ]);

            $genero = $request->query('genero');
            $prendaId = $request->query('prendaId');

            // Si pide tallas de una prenda ESPECÍFICA (con cantidades)
            if ($prendaId) {
                return $this->obtenerTallasPrenda((int)$prendaId);
            }

            // CATÁLOGO GENERAL: Constantes de tallas por género
            $tallasPorGenero = [
                'DAMA' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'],
                'CABALLERO' => ['28', '30', '32', '34', '36', '38', '40', '42', '44', '46'],
                'UNISEX' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL']
            ];

            // Si pide género específico, retornar solo ese
            if ($genero && isset($tallasPorGenero[strtoupper($genero)])) {
                $resultado = [
                    strtoupper($genero) => $tallasPorGenero[strtoupper($genero)]
                ];
            } else {
                // Retornar todas las tallas por género
                $resultado = $tallasPorGenero;
            }

            Log::info('[PedidosProduccionController] Tallas retornadas', [
                'count' => count($resultado),
                'generos' => array_keys($resultado)
            ]);

            return response()->json([
                'success' => true,
                'data' => $resultado,
                'mensaje' => 'Catálogo de tallas cargado exitosamente'
            ], 200);

        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error al obtener tallas', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el catálogo de tallas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/prenda-pedido/{prendaId}/tallas
     * Obtener tallas específicas de una prenda (si está guardada)
     * 
     * Retorna: { DAMA: { S: 10, M: 15 }, CABALLERO: { 32: 20 } }
     */
    public function obtenerTallasPrenda(int $prendaId): JsonResponse
    {
        try {
            Log::info('[PedidosProduccionController] GET /api/prenda-pedido/{prendaId}/tallas', [
                'prenda_id' => $prendaId
            ]);

            // Obtener tallas desde BD - tabla prenda_pedido_tallas
            $tallas = DB::table('prenda_pedido_tallas')
                ->where('prenda_pedido_id', $prendaId)
                ->select('genero', 'talla', 'cantidad')
                ->get();

            if ($tallas->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => ['DAMA' => [], 'CABALLERO' => []],
                    'mensaje' => 'Prenda sin tallas asignadas'
                ], 200);
            }

            // Agrupar tallas por género: { DAMA: { S: 10, M: 15 }, ... }
            $tallasPorGenero = [];
            foreach ($tallas as $talla) {
                $genero = $talla->genero;
                if (!isset($tallasPorGenero[$genero])) {
                    $tallasPorGenero[$genero] = [];
                }
                $tallasPorGenero[$genero][$talla->talla] = (int)$talla->cantidad;
            }

            return response()->json([
                'success' => true,
                'data' => $tallasPorGenero,
                'mensaje' => 'Tallas de prenda cargadas exitosamente'
            ], 200);

        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error al obtener tallas de prenda', [
                'error' => $e->getMessage(),
                'prenda_id' => $prendaId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las tallas de la prenda: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/prenda-pedido/{prendaId}/variantes
     * Obtener variantes de prenda (manga, broche, bolsillos)
     */
    public function obtenerVariantesPrenda(int $prendaId): JsonResponse
    {
        try {
            Log::info('[PedidosProduccionController] GET /api/prenda-pedido/{prendaId}/variantes', [
                'prenda_id' => $prendaId
            ]);

            $variantes = DB::table('prenda_pedido_variantes')
                ->leftJoin('tipos_manga', 'prenda_pedido_variantes.tipo_manga_id', '=', 'tipos_manga.id')
                ->leftJoin('tipos_broche_boton', 'prenda_pedido_variantes.tipo_broche_boton_id', '=', 'tipos_broche_boton.id')
                ->where('prenda_pedido_variantes.prenda_pedido_id', $prendaId)
                ->select(
                    'prenda_pedido_variantes.*',
                    'tipos_manga.nombre as nombre_manga',
                    'tipos_broche_boton.nombre as nombre_broche'
                )
                ->get();

            return response()->json([
                'success' => true,
                'data' => $variantes,
                'mensaje' => 'Variantes de prenda cargadas exitosamente'
            ], 200);

        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error al obtener variantes de prenda', [
                'error' => $e->getMessage(),
                'prenda_id' => $prendaId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las variantes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/prenda-pedido/{prendaId}/colores-telas
     * Obtener colores y telas seleccionados para una prenda
     */
    public function obtenerColoresTelasPrenda(int $prendaId): JsonResponse
    {
        try {
            Log::info('[PedidosProduccionController] GET /api/prenda-pedido/{prendaId}/colores-telas', [
                'prenda_id' => $prendaId
            ]);
            $coloresTelas = DB::table('prenda_pedido_colores_telas')
                ->leftJoin('colores_prenda', 'prenda_pedido_colores_telas.color_id', '=', 'colores_prenda.id')
                ->leftJoin('telas_prenda', 'prenda_pedido_colores_telas.tela_id', '=', 'telas_prenda.id')
                ->where('prenda_pedido_colores_telas.prenda_pedido_id', $prendaId)
                ->select(
                    'prenda_pedido_colores_telas.id',
                    'colores_prenda.nombre as color',
                    'colores_prenda.codigo as codigo_color',
                    'telas_prenda.nombre as tela',
                    'prenda_pedido_colores_telas.referencia as referencia_tela'
                )
                ->get();

            return response()->json([
                'success' => true,
                'data' => $coloresTelas,
                'mensaje' => 'Colores y telas cargados exitosamente'
            ], 200);

        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error al obtener colores y telas', [
                'error' => $e->getMessage(),
                'prenda_id' => $prendaId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los colores y telas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/pedidos-produccion/{pedidoId}/prenda/{prendaId}/datos
     * Obtener datos completos de una prenda con procesos para edición en modal
     */
    public function obtenerDatosPrendaEdicion(int|string $pedidoId, int|string $prendaId): JsonResponse
    {
        try {
            Log::info('🔥 [PRENDA-DATOS-INICIO] Endpoint llamado', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'timestamp' => now()
            ]);

            $service = app(\App\Application\Services\Asesores\ObtenerPedidoDetalleService::class);
            
            Log::info('📡 [PRENDA-DATOS] Llamando al servicio...');
            $prendaData = $service->obtenerPrendaConProcesos((int)$pedidoId, (int)$prendaId);

            Log::info('✅ [PRENDA-DATOS-RECIBIDOS] Datos obtenidos del servicio', [
                'procesos_count' => count($prendaData['procesos'] ?? []),
                'tallas_dama_count' => count($prendaData['tallas_dama'] ?? []),
                'tallas_caballero_count' => count($prendaData['tallas_caballero'] ?? []),
                'variantes_count' => count($prendaData['variantes'] ?? []),
                'colores_telas_count' => count($prendaData['colores_telas'] ?? []),
                'imagenes_count' => count($prendaData['imagenes'] ?? []),
                'prenda_keys' => array_keys($prendaData)
            ]);

            // Validar que los datos no estén vacíos
            if (empty($prendaData)) {
                Log::warning(' [PRENDA-DATOS-VACIA] La prenda retornó datos vacíos');
            }

            // Obtener también datos del pedido para la factura de edición
            $pedido = \App\Models\PedidoProduccion::find((int)$pedidoId);
            $pedidoData = [];
            if ($pedido) {
                $pedidoData = [
                    'id' => $pedido->id,
                    'numero' => $pedido->numero_pedido,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente' => $pedido->cliente,
                    'cliente_nombre' => $pedido->cliente,
                    'asesor_nombre' => $pedido->asesor?->name ?? 'Sin asesor',
                    'estado' => $pedido->estado,
                    'fecha_creacion' => $pedido->created_at?->format('d/m/Y') ?? '',
                ];
            }

            return response()->json([
                'success' => true,
                'prenda' => $prendaData,
                'pedido' => $pedidoData
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning(' [PRENDA-DATOS] Prenda no encontrada', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Prenda no encontrada'
            ], 404);

        } catch (\Exception $e) {
            Log::error(' [PRENDA-DATOS] Error obteniendo datos de prenda', [
                'error' => $e->getMessage(),
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos de prenda: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/pedidos-produccion/{pedidoId}/datos-edicion
     * Obtener datos del pedido para edición general (sin prenda específica)
     * 
     * Response:
     * {
     *   "success": true,
     *   "pedido_id": 2765,
     *   "numero_pedido": "100034",
     *   "cliente": "Cliente Name",
     *   "prendas_count": 1,
     *   "data": { ... }
     * }
     * 
     * @param int $pedidoId
     * @return JsonResponse
     */
    public function obtenerDatosEdicion(int $pedidoId): JsonResponse
    {
        try {
            Log::info('[PedidosProduccionController] GET /pedidos-produccion/{pedidoId}/datos-edicion', [
                'pedido_id' => $pedidoId,
            ]);

            // Obtener el pedido completo
            $dto = ObtenerProduccionPedidoDTO::fromRequest($pedidoId);
            $pedido = $this->obtenerPedidoUseCase->ejecutar($dto);

            if (!$pedido) {
                Log::warning('[PedidosProduccionController] Pedido no encontrado para edición', [
                    'pedido_id' => $pedidoId,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado',
                ], 404);
            }

            Log::info('[PedidosProduccionController] Datos de edición obtenidos', [
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido['numero_pedido'] ?? null,
                'prendas_count' => count($pedido['prendas'] ?? []),
            ]);

            return response()->json([
                'success' => true,
                'pedido_id' => $pedidoId,
                'numero_pedido' => $pedido['numero_pedido'] ?? null,
                'cliente' => $pedido['cliente'] ?? null,
                'prendas_count' => count($pedido['prendas'] ?? []),
                'data' => $pedido,
            ], 200);

        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error obteniendo datos de edición', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos de edición: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /asesores/pedidos/{pedidoId}/prendas/{prendaId}/variante
     * Actualizar SOLO la variante de una prenda (manga, broche, bolsillos)
     * 
     * IMPORTANTE: Realiza MERGE de datos - solo actualiza campos enviados, preserva el resto
     * 
     * Body:
     * {
     *   "tipo_manga_id": 2,
     *   "manga_obs": "Observación de manga",
     *   "tipo_broche_boton_id": 1,
     *   "broche_boton_obs": "Observación de broche",
     *   "tiene_bolsillos": true,
     *   "bolsillos_obs": "Con bolsillos laterales"
     * }
     * 
     * Response exitosa:
     * {
     *   "success": true,
     *   "data": {
     *     "id": 7438,
     *     "prenda_pedido_id": 3477,
     *     "tipo_manga_id": 2,
     *     "tipo_manga_nombre": "Corta",
     *     "manga_obs": "Observación de manga",
     *     ...
     *   },
     *   "message": "Variante actualizada correctamente"
     * }
     * 
     * @param Request $request
     * @param int $pedidoId - ID del pedido
     * @param int $prendaId - ID de la prenda
     * @return JsonResponse
     */
    public function actualizarVariantePrend(Request $request, int $pedidoId, int $prendaId): JsonResponse
    {
        try {
            Log::info('[PedidosProduccionController] PUT /pedidos/{pedidoId}/prendas/{prendaId}/variante', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'has_body' => $request->getContent() !== '',
            ]);

            // Validación básica de HTTP
            $validated = $request->validate([
                'tipo_manga_id' => 'sometimes|nullable|integer|min:1',
                'manga_obs' => 'sometimes|nullable|string|max:500',
                'tipo_broche_boton_id' => 'sometimes|nullable|integer|min:1',
                'broche_boton_obs' => 'sometimes|nullable|string|max:500',
                'tiene_bolsillos' => 'sometimes|nullable|boolean',
                'bolsillos_obs' => 'sometimes|nullable|string|max:500',
            ]);

            // Crear DTO con datos del request
            $data = array_merge($validated, [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
            ]);
            $dto = \App\Application\Pedidos\DTOs\ActualizarVariantePrendaDTO::fromRequest($data);

            // Ejecutar UseCase que orquesta el comando
            $resultado = $this->actualizarVariantePrendaUseCase->ejecutar($dto);

            Log::info('[PedidosProduccionController] Variante actualizada exitosamente', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'variante_id' => $resultado['id'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'data' => $resultado,
                'message' => 'Variante actualizada correctamente',
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[PedidosProduccionController] Validación HTTP fallida', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validación de datos fallida',
                'errors' => $e->errors(),
            ], 422);

        } catch (\InvalidArgumentException $e) {
            Log::warning('[PedidosProduccionController] Validación de negocio fallida', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error actualizando variante de prenda', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar variante: ' . $e->getMessage(),
            ], 500);
        }
    }
}

