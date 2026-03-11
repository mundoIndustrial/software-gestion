<?php

namespace App\Infrastructure\Http\Controllers\Asesores;

use App\Domain\Shared\CQRS\QueryBus;
use App\Domain\Shared\CQRS\CommandBus;
use App\Domain\Pedidos\Commands\EliminarPedidoCommand;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
use App\Application\Pedidos\DTOs\CambiarEstadoPedidoDTO;
use App\Application\Pedidos\DTOs\AgregarPrendaAlPedidoDTO;
use App\Application\Pedidos\DTOs\FiltrarPedidosPorEstadoDTO;
use App\Application\Pedidos\DTOs\BuscarPedidoPorNumeroDTO;
use App\Application\Pedidos\DTOs\ObtenerPrendasPedidoDTO;
use App\Application\Pedidos\DTOs\ActualizarPrendaPedidoDTO;
use App\Application\Pedidos\DTOs\AgregarPrendaCompletaDTO;
use App\Application\Pedidos\DTOs\ActualizarPrendaCompletaDTO;
use App\Application\Pedidos\DTOs\RenderItemCardDTO;
use App\Models\PrendaFotoPedido;
use App\Models\PrendaFotoTelaPedido;
use App\Models\PedidosProcesoImagenes;
use App\Models\ConsecutivoReciboPedido;
use App\Models\PedidosProcesosPrendaTallaColor;
use Illuminate\Support\Facades\Storage;

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
                'asignaciones_colores' => 'nullable|json',  //  NUEVO: colores por talla
                'procesos' => 'nullable|json',
                'variantes' => 'nullable|json',
                'novedad' => 'required|string|max:500',
                'imagenes' => 'nullable|array',
                'imagenes.*' => 'nullable|image|max:5120',
                'imagenes_existentes' => 'nullable|json', // Imágenes existentes de BD a preservar
                'telas' => 'nullable|json',
            ]);

            // Procesar imágenes de prenda directamente en carpeta final
            $imagenesGuardadas = [];
            
            if ($request->hasFile('imagenes')) {
                $prendaFotoService = new \App\Domain\Pedidos\Services\PrendaFotoService();
                
                foreach ($request->file('imagenes') as $imagen) {
                    // Guardar directamente en pedidos/{id}/prenda/ (no en temp/)
                    $rutas = $prendaFotoService->procesarFoto($imagen, (int)$id);
                    $imagenesGuardadas[] = $rutas['ruta_webp'] ?? $rutas['ruta_original'];
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

            // Procesar imágenes de colores: $request->file('fotos_color') retorna array de UploadedFile
            $fotosColorFiles = $request->file('fotos_color') ?? [];
            $fotosColorMetaAll = $request->input('fotos_color_meta') ?? [];
            $colorFotoServiceCrear = new \App\Domain\Pedidos\Services\TelaFotoService();
            $fotosColorMeta = [];

            foreach ($fotosColorFiles as $indice => $archivo) {
                if ($archivo && $archivo->isValid()) {
                    try {
                        $rutas = $colorFotoServiceCrear->procesarFoto($archivo, (int)$id, true);
                        $metaRaw = $fotosColorMetaAll[$indice] ?? null;
                        $meta = is_string($metaRaw) ? json_decode($metaRaw, true) : $metaRaw;

                        $fotosColorMeta[] = [
                            'ruta_webp' => $rutas['ruta_webp'] ?? $rutas['ruta_original'],
                            'clave' => $meta['clave'] ?? '',
                            'color_nombre' => $meta['color_nombre'] ?? '',
                        ];

                        Log::info('[PedidosProduccionController] Imagen de color (crear) procesada WebP', [
                            'indice' => $indice,
                            'ruta_webp' => $fotosColorMeta[count($fotosColorMeta)-1]['ruta_webp'],
                            'clave' => $fotosColorMeta[count($fotosColorMeta)-1]['clave'],
                            'color' => $fotosColorMeta[count($fotosColorMeta)-1]['color_nombre'],
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('[PedidosProduccionController] Error procesando imagen color (crear)', [
                            'indice' => $indice, 'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            // Inyectar rutas de imagen en asignaciones_colores antes de pasar al DTO
            if (!empty($fotosColorMeta) && !empty($validated['asignaciones_colores'])) {
                $asigColoresTemp = is_string($validated['asignaciones_colores']) 
                    ? json_decode($validated['asignaciones_colores'], true) 
                    : $validated['asignaciones_colores'];
                
                foreach ($fotosColorMeta as $fotoMeta) {
                    $clave = $fotoMeta['clave'];
                    $colorNombre = strtoupper($fotoMeta['color_nombre']);
                    
                    if (isset($asigColoresTemp[$clave]) && !empty($asigColoresTemp[$clave]['colores'])) {
                        foreach ($asigColoresTemp[$clave]['colores'] as &$colorItem) {
                            if (strtoupper($colorItem['nombre'] ?? '') === $colorNombre) {
                                $colorItem['imagen_ruta'] = $fotoMeta['ruta_webp'];
                                break;
                            }
                        }
                        unset($colorItem);
                    }
                }
                
                $validated['asignaciones_colores'] = $asigColoresTemp;
            }

            // Procesar imágenes de procesos nuevos (fotosProcesoNuevo_0[], fotosProcesoNuevo_1[], etc.)
            $fotosProcesoNuevo = [];
            $procesoFotoService = new \App\Domain\Pedidos\Services\ProcesoFotoService();
            foreach ($request->allFiles() as $key => $files) {
                if (strpos($key, 'fotosProcesoNuevo_') === 0) {
                    preg_match('/fotosProcesoNuevo_(\d+)/', $key, $matches);
                    if (!isset($matches[1])) continue;
                    $indice = (int)$matches[1];
                    $archivos = is_array($files) ? $files : [$files];
                    if (!isset($fotosProcesoNuevo[$indice])) {
                        $fotosProcesoNuevo[$indice] = [];
                    }
                    foreach ($archivos as $archivo) {
                        if ($archivo && $archivo->isValid()) {
                            try {
                                $rutas = $procesoFotoService->procesarFoto($archivo, (int)$id);
                                $fotosProcesoNuevo[$indice][] = $rutas;
                            } catch (\Exception $e) {
                                Log::warning('[PedidosProduccionController] Error procesando imagen de proceso nuevo', [
                                    'key' => $key, 'error' => $e->getMessage()
                                ]);
                            }
                        }
                    }
                }
            }

            // Procesar imágenes de telas (fotos_tela[0], fotos_tela[1], etc.)
            $fotosTelaRutas = [];
            $telaFotoService = new \App\Domain\Pedidos\Services\TelaFotoService();
            $fotosTelaFiles = $request->file('fotos_tela') ?? [];
            foreach ($fotosTelaFiles as $indice => $archivo) {
                if ($archivo && $archivo->isValid()) {
                    try {
                        $rutas = $telaFotoService->procesarFoto($archivo, (int)$id, true);
                        $fotosTelaRutas[$indice] = $rutas;
                        Log::info('[PedidosProduccionController] Imagen de tela procesada', [
                            'indice' => $indice,
                            'ruta_webp' => $rutas['ruta_webp'],
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('[PedidosProduccionController] Error procesando imagen de tela', [
                            'indice' => $indice, 'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            // Usar Use Case DDD
            $dto = AgregarPrendaCompletaDTO::fromRequest($id, $validated, $imagenesGuardadas, $imagenesExistentes, $fotosProcesoNuevo, $fotosTelaRutas);
            $prenda = $this->agregarPrendaCompletaUseCase->execute($dto);

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

            //  DEBUG: Ver qué datos recibe el request
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
                'fotosTelas' => 'nullable|json', // Frontend envía en camelCase
                'procesos' => 'nullable|json',
                'fotos_procesos' => 'nullable|json',
                'novedad' => 'required|string|max:500',
                'asignaciones_colores' => 'nullable|json', // Colores por talla → prenda_pedido_talla_colores
                'imagenes' => 'nullable|array',
                'imagenes.*' => 'nullable|image|max:5120',
                'imagenes_existentes' => 'nullable|json', // Imágenes existentes de BD a preservar
                'imagenes_a_eliminar' => 'nullable|json', // IDs de imágenes a eliminar
                'procesos_a_eliminar' => 'nullable|json', // IDs de procesos a eliminar
            ]);

            // Normalizar fotosTelas: si viene en camelCase, copiar a fotos_telas (snake_case)
            if (!empty($validated['fotosTelas']) && empty($validated['fotos_telas'])) {
                $validated['fotos_telas'] = $validated['fotosTelas'];
            }

            // Decodificar imagenes_a_eliminar si viene como JSON string
            if ($request->has('imagenes_a_eliminar') && is_string($request->input('imagenes_a_eliminar'))) {
                $request->merge(['imagenes_a_eliminar' => json_decode($request->input('imagenes_a_eliminar'), true)]);
            }

            // Procesar imágenes de prenda (convertir a WebP)
            $imagenesGuardadas = [];
            if ($request->hasFile('imagenes')) {
                $prendaFotoService = new \App\Domain\Pedidos\Services\PrendaFotoService();
                foreach ($request->file('imagenes') as $imagen) {
                    $rutas = $prendaFotoService->procesarFoto($imagen, $id);
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
                                $rutas = $telaFotoService->procesarFoto($imagen, $id);
                                $telasConImagenes[] = $rutas;
                            }
                        }
                    }
                }
            }

            // NUEVO: Procesar imágenes de telas nuevas (vienen como fotos_tela[0], fotos_tela[1], etc. O fotos_tela[])
            $fotosTelasProcesadas = []; // Array de rutas procesadas, indexado por índice
            $telaFotoService = new \App\Domain\Pedidos\Services\TelaFotoService();
            
            // Opción 1: Archivos con patrón fotos_tela[N]
            foreach ($allFiles as $key => $value) {
                if (strpos($key, 'fotos_tela[') === 0 && strpos($key, ']') !== false) {
                    if ($value && $value->isValid()) {
                        try {
                            $rutas = $telaFotoService->procesarFoto($value, $id);
                            // Extraer índice: fotos_tela[0] => 0
                            preg_match('/fotos_tela\[(\d+)\]/', $key, $matches);
                            $indice = isset($matches[1]) ? (int)$matches[1] : count($fotosTelasProcesadas);
                            $fotosTelasProcesadas[$indice] = $rutas;
                            Log::info('[PedidosProduccionController] Imagen de tela procesada (patrón fotos_tela[N])', [
                                'key' => $key,
                                'indice' => $indice,
                                'archivo' => $value->getClientOriginalName(),
                                'ruta_webp' => $rutas['ruta_webp'] ?? 'N/A',
                                'ruta_original' => $rutas['ruta_original'] ?? 'N/A'
                            ]);
                        } catch (\Exception $e) {
                            Log::warning('[PedidosProduccionController] Error procesando imagen de tela', [
                                'key' => $key,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            }
            
            // Opción 2: Archivos con clave simple 'fotos_tela' (array)
            if ($request->hasFile('fotos_tela') && empty($fotosTelasProcesadas)) {
                $archivosTelaDirectos = $request->file('fotos_tela');
                if (!is_array($archivosTelaDirectos)) {
                    $archivosTelaDirectos = [$archivosTelaDirectos];
                }
                
                foreach ($archivosTelaDirectos as $indice => $archivo) {
                    if ($archivo && $archivo->isValid()) {
                        try {
                            $rutas = $telaFotoService->procesarFoto($archivo, $id);
                            $fotosTelasProcesadas[$indice] = $rutas;
                            Log::info('[PedidosProduccionController] Imagen de tela procesada (patrón fotos_tela[])', [
                                'indice' => $indice,
                                'archivo' => $archivo->getClientOriginalName(),
                                'ruta_webp' => $rutas['ruta_webp'] ?? 'N/A',
                                'ruta_original' => $rutas['ruta_original'] ?? 'N/A'
                            ]);
                        } catch (\Exception $e) {
                            Log::warning('[PedidosProduccionController] Error procesando imagen de tela (fotos_tela[])', [
                                'indice' => $indice,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            }

            // NUEVO: Procesar imágenes de procesos (vienen como files_proceso_*_*_*)
            $procesosConImagenes = [];
            $procesoFotoService = new \App\Domain\Pedidos\Services\ProcesoFotoService();
            foreach ($allFiles as $key => $value) {
                // Buscar claves como: files_proceso_prendaIdx_procesoIdx_imgIdx
                if (strpos($key, 'files_proceso_') === 0) {
                    if ($value && $value->isValid()) {
                        try {
                            $rutas = $procesoFotoService->procesarFoto($value, (int)$id);
                            $procesosConImagenes[] = $rutas;
                            Log::info('[PedidosProduccionController] Imagen de proceso procesada', [
                                'key' => $key,
                                'archivo' => $value->getClientOriginalName(),
                                'ruta_webp' => $rutas['ruta_webp'] ?? 'N/A'
                            ]);
                        } catch (\Exception $e) {
                            Log::warning('[PedidosProduccionController] Error procesando imagen de proceso', [
                                'key' => $key,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            }

            // NUEVO: Procesar imágenes de procesos NUEVOS (vienen como fotosProcesoNuevo_0[], fotosProcesoNuevo_1[], etc.)
            // 🔴 CRÍTICO: Cada fotosProcesoNuevo_{idx} corresponde al proceso en posición {idx} del array de procesos
            $fotosProcesoNuevo = [];
            
            // Obtener dinámicamente todos los fotosProcesoNuevo_* del request
            foreach ($request->allFiles() as $key => $files) {
                if (strpos($key, 'fotosProcesoNuevo_') === 0) {
                    // Extraer el índice del nombre de la clave
                    preg_match('/fotosProcesoNuevo_(\d+)/', $key, $matches);
                    if (!isset($matches[1])) continue;
                    
                    $indice = (int)$matches[1];
                    
                    // Soportar tanto un solo archivo como array de archivos
                    $archivos = is_array($files) ? $files : [$files];
                    
                    if (!isset($fotosProcesoNuevo[$indice])) {
                        $fotosProcesoNuevo[$indice] = [];
                    }
                    
                    foreach ($archivos as $archivo) {
                        if ($archivo && $archivo->isValid()) {
                            try {
                                $rutas = $procesoFotoService->procesarFoto($archivo, (int)$id);
                                $fotosProcesoNuevo[$indice][] = $rutas;
                                Log::info('[PedidosProduccionController] Imagen de proceso procesada', [
                                    'key' => $key,
                                    'indice' => $indice,
                                    'archivo' => $archivo->getClientOriginalName(),
                                    'ruta_webp' => $rutas['ruta_webp'] ?? 'N/A'
                                ]);
                            } catch (\Exception $e) {
                                Log::warning('[PedidosProduccionController] Error procesando imagen de proceso', [
                                    'key' => $key,
                                    'indice' => $indice,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                    }
                }
            }

            // 🔴 NUEVO: Procesar imágenes de procesos POR TALLA (vienen como fotosProcesoTallasNuevo_{procesoIdx}_{genero}_{talla}[])
            // Formato: fotosProcesoTallasNuevo_0_dama_M[], fotosProcesoTallasNuevo_1_caballero_L[], etc.
            $fotosProcesoTallasNuevo = [];
            
            foreach ($request->allFiles() as $key => $files) {
                if (strpos($key, 'fotosProcesoTallasNuevo_') === 0) {
                    // Extraer params: fotosProcesoTallasNuevo_{procesoIdx}_{genero}_{talla}
                    // Usar [a-zA-Z]+ para genero (dama/caballero/sobremedida/unisex) para evitar match greedy
                    // cuando el nombre del color contiene guiones bajos (ej: AZUL_OSCURO)
                    preg_match('/fotosProcesoTallasNuevo_(\d+)_([a-zA-Z]+)_(.+)/', $key, $matches);
                    if (!isset($matches[1]) || !isset($matches[2]) || !isset($matches[3])) continue;
                    
                    $procesoIdx = (int)$matches[1];
                    $genero = strtolower($matches[2]);
                    $talla = $matches[3];
                    $keyTalla = "{$procesoIdx}_{$genero}_{$talla}";
                    
                    // Soportar tanto un solo archivo como array de archivos
                    $archivos = is_array($files) ? $files : [$files];
                    
                    if (!isset($fotosProcesoTallasNuevo[$keyTalla])) {
                        $fotosProcesoTallasNuevo[$keyTalla] = [];
                    }
                    
                    foreach ($archivos as $archivo) {
                        if ($archivo && $archivo->isValid()) {
                            try {
                                $rutas = $procesoFotoService->procesarFoto($archivo, (int)$id);
                                $fotosProcesoTallasNuevo[$keyTalla][] = [
                                    'ruta_original' => $rutas['ruta_original'] ?? null,
                                    'ruta_webp' => $rutas['ruta_webp'] ?? null,
                                    'proceso_idx' => $procesoIdx,
                                    'genero' => $genero,
                                    'talla' => $talla,
                                ];
                                Log::info('[PedidosProduccionController] Imagen de proceso por talla procesada', [
                                    'key' => $key,
                                    'keyTalla' => $keyTalla,
                                    'archivo' => $archivo->getClientOriginalName(),
                                    'ruta_webp' => $rutas['ruta_webp'] ?? 'N/A'
                                ]);
                            } catch (\Exception $e) {
                                Log::warning('[PedidosProduccionController] Error procesando imagen de proceso por talla', [
                                    'key' => $key,
                                    'keyTalla' => $keyTalla,
                                    'error' => $e->getMessage()
                                ]);
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

            // 🔴 NUEVO: Procesar imágenes a eliminar
            $imagenesAEliminar = [];
            if ($request->input('imagenes_a_eliminar')) {
                try {
                    $input = $request->input('imagenes_a_eliminar');
                    
                    // 🔴 CRÍTICO: Manejar ambos casos
                    // Caso 1: Ya es un array (Laravel lo parseó automáticamente)
                    if (is_array($input)) {
                        $imagenesAEliminar = $input;
                    }
                    // Caso 2: Es una cadena JSON (necesita decodificar)
                    elseif (is_string($input)) {
                        $imagenesAEliminar = json_decode($input, true) ?? [];
                    }
                    
                    // Validar que sea un array válido
                    if (!is_array($imagenesAEliminar)) {
                        $imagenesAEliminar = [];
                    }
                } catch (\Exception $e) {
                    Log::warning('[PedidosProduccionController] Error procesando imagenes_a_eliminar', ['error' => $e->getMessage()]);
                }
                
                // 🔴 ELIMINADO: No eliminar imágenes aquí - el UseCase se encarga de la eliminación
                // Esto evita doble eliminación (Controller + UseCase)
                /*
                // Eliminar imágenes del almacenamiento y BD
                if (!empty($imagenesAEliminar)) {
                    Log::info('[PedidosProduccionController] Eliminando imágenes marcadas', [
                        'cantidad' => count($imagenesAEliminar),
                        'ids' => $imagenesAEliminar
                    ]);
                    
                    foreach ($imagenesAEliminar as $imagenId) {
                        try {
                            $imagen = PrendaFotoPedido::findOrFail($imagenId);
                            
                            // Eliminar archivos físicos
                            if ($imagen->ruta_original && Storage::disk('public')->exists($imagen->ruta_original)) {
                                Storage::disk('public')->delete($imagen->ruta_original);
                            }
                            if ($imagen->ruta_webp && $imagen->ruta_webp !== $imagen->ruta_original && Storage::disk('public')->exists($imagen->ruta_webp)) {
                                Storage::disk('public')->delete($imagen->ruta_webp);
                            }
                            
                            // Eliminar registro de BD
                            $imagen->forceDelete();
                            
                            Log::info('[PedidosProduccionController] Imagen eliminada', [
                                'id' => $imagenId,
                                'ruta_original' => $imagen->ruta_original,
                                'ruta_webp' => $imagen->ruta_webp
                            ]);
                        } catch (\Exception $e) {
                            Log::warning('[PedidosProduccionController] Error eliminando imagen', [
                                'id' => $imagenId,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
                */
                
                Log::info('[PedidosProduccionController] Imágenes a eliminar delegadas al UseCase', [
                    'cantidad' => count($imagenesAEliminar ?? []),
                    'ids' => $imagenesAEliminar ?? []
                ]);
            }

            // 🔴 NUEVO: Procesar procesos a eliminar
            $procesosAEliminar = [];
            if ($request->input('procesos_a_eliminar')) {
                try {
                    $input = $request->input('procesos_a_eliminar');
                    
                    // 🔴 CRÍTICO: Manejar ambos casos
                    // Caso 1: Ya es un array (Laravel lo parseó automáticamente)
                    if (is_array($input)) {
                        $procesosAEliminar = $input;
                    }
                    // Caso 2: Es una cadena JSON (necesita decodificar)
                    elseif (is_string($input)) {
                        $procesosAEliminar = json_decode($input, true) ?? [];
                    }
                    
                    // Validar que sea un array válido
                    if (!is_array($procesosAEliminar)) {
                        $procesosAEliminar = [];
                    }
                } catch (\Exception $e) {
                    Log::warning('[PedidosProduccionController] Error procesando procesos_a_eliminar', ['error' => $e->getMessage()]);
                }
                
                // Eliminar procesos de la BD
                if (!empty($procesosAEliminar)) {
                    Log::info('[PedidosProduccionController] Eliminando procesos marcados', [
                        'cantidad' => count($procesosAEliminar),
                        'ids' => $procesosAEliminar
                    ]);
                    
                    foreach ($procesosAEliminar as $procesoId) {
                        try {
                            $proceso = \App\Models\PedidosProcesosPrendaDetalle::findOrFail($procesoId);
                            
                            // Eliminar imágenes asociadas al proceso
                            if ($proceso->imagenes) {
                                foreach ($proceso->imagenes as $imagen) {
                                    if ($imagen->ruta_original && Storage::disk('public')->exists($imagen->ruta_original)) {
                                        Storage::disk('public')->delete($imagen->ruta_original);
                                    }
                                    if ($imagen->ruta_webp && $imagen->ruta_webp !== $imagen->ruta_original && Storage::disk('public')->exists($imagen->ruta_webp)) {
                                        Storage::disk('public')->delete($imagen->ruta_webp);
                                    }
                                    $imagen->forceDelete();
                                }
                            }
                            
                            // Eliminar tallas asociadas al proceso
                            if ($proceso->tallas) {
                                foreach ($proceso->tallas as $talla) {
                                    $talla->forceDelete();
                                }
                            }
                            
                            // Eliminar registro del proceso
                            $proceso->forceDelete();
                            
                            Log::info('[PedidosProduccionController] Proceso eliminado', [
                                'id' => $procesoId,
                                'tipo' => $proceso->tipo_recibo
                            ]);
                        } catch (\Exception $e) {
                            Log::warning('[PedidosProduccionController] Error eliminando proceso', [
                                'id' => $procesoId,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            }

            // Procesar imágenes de colores: $request->file('fotos_color') retorna array de UploadedFile
            $fotosColorProcesadas = [];
            $colorFotoService = new \App\Domain\Pedidos\Services\TelaFotoService();
            $fotosColorFiles = $request->file('fotos_color') ?? [];
            $fotosColorMetaAll = $request->input('fotos_color_meta') ?? [];

            foreach ($fotosColorFiles as $indice => $archivo) {
                if ($archivo && $archivo->isValid()) {
                    try {
                        // soloWebp=true: siempre guardar en WebP
                        $rutas = $colorFotoService->procesarFoto($archivo, (int)$id, true);

                        // Obtener meta asociada por índice
                        $metaRaw = $fotosColorMetaAll[$indice] ?? null;
                        $meta = is_string($metaRaw) ? json_decode($metaRaw, true) : $metaRaw;

                        $fotosColorProcesadas[$indice] = [
                            'ruta_webp' => $rutas['ruta_webp'] ?? $rutas['ruta_original'],
                            'clave' => $meta['clave'] ?? '',
                            'color_nombre' => $meta['color_nombre'] ?? '',
                        ];

                        Log::info('[PedidosProduccionController] Imagen de color procesada (WebP)', [
                            'indice' => $indice,
                            'archivo' => $archivo->getClientOriginalName(),
                            'ruta_webp' => $fotosColorProcesadas[$indice]['ruta_webp'],
                            'clave' => $fotosColorProcesadas[$indice]['clave'],
                            'color_nombre' => $fotosColorProcesadas[$indice]['color_nombre'],
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('[PedidosProduccionController] Error procesando imagen de color', [
                            'indice' => $indice,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            if (!empty($fotosColorProcesadas)) {
                Log::info('[PedidosProduccionController] Total imágenes de color procesadas', [
                    'cantidad' => count($fotosColorProcesadas)
                ]);
            }

            // Usar Use Case DDD
            Log::info('[PedidosProduccionController] Datos validados para actualizar prenda', [
                'origen_recibido' => $validated['origen'] ?? 'N/A',
                'de_bodega_recibido' => $validated['de_bodega'] ?? 'N/A',
                'tallas_recibidas' => $validated['tallas'] ?? '{}',
                'variantes_recibidas' => $validated['variantes'] ?? '{}',
                'procesos' => $validated['procesos'] ?? '[]',
                'imagenes_procesadas' => count($imagenesGuardadas),
                'imagenes_existentes' => count($imagenesExistentes),
                'imagenes_a_eliminar' => count($imagenesAEliminar),
                'fotos_telas_procesadas' => count($fotosTelasProcesadas),
                'fotos_telas_detalles' => $fotosTelasProcesadas,
                'fotos_proceso_nuevo_count' => count($fotosProcesoNuevo),
                'fotos_color_procesadas' => count($fotosColorProcesadas),
                'novedad_recibida' => $validated['novedad'] ?? 'SIN NOVEDAD',
            ]);
            
            // 🔴 NUEVO: Extraer asignaciones_colores del FormData
            // El frontend envía formData.append('asignaciones_colores', JSON.stringify(...))
            if ($request->has('asignaciones_colores')) {
                $asignacionesInput = $request->input('asignaciones_colores');
                if (is_string($asignacionesInput)) {
                    $validated['asignaciones_colores'] = json_decode($asignacionesInput, true);
                } else {
                    $validated['asignaciones_colores'] = $asignacionesInput;
                }
                // Si json_decode devuelve null, usar array vacío (señal de eliminar todo)
                if (is_null($validated['asignaciones_colores'])) {
                    $validated['asignaciones_colores'] = [];
                }
                \Log::info('[PedidosProduccionController] asignaciones_colores extraído del FormData', [
                    'asignaciones_colores' => $validated['asignaciones_colores'],
                    'es_vacio' => empty($validated['asignaciones_colores'])
                ]);
            }
            
            // IMPORTANTE: Usar $validated['prenda_id'], NO $id (que es pedido_id)
            $dto = ActualizarPrendaCompletaDTO::fromRequest($validated['prenda_id'], $validated, $imagenesGuardadas, $imagenesExistentes, $fotosTelasProcesadas, $fotosProcesoNuevo, $fotosColorProcesadas, $fotosProcesoTallasNuevo);
            $prenda = $this->actualizarPrendaCompletaUseCase->ejecutar($dto);

            Log::info('[PedidosProduccionController] Prenda completa actualizada exitosamente', [
                'pedido_id' => $id,
                'prenda_id' => $prenda->id,
            ]);

            //  FIX CRÍTICO: Recargar fotos y relaciones después de actualizar
            // Esto evita que Eloquent devuelva fotos cacheadas que fueron eliminadas
            $prenda = $prenda->fresh(['fotos', 'coloresTelas', 'fotosTelas', 'variantes', 'procesos', 'tallas']);

            //  FIX CRÍTICO: Transformar procesos para asegurar que incluyan el ID
            // Cuando se retorna toArray(), algunos procesos podrían no tener su ID
            $prendaArray = $prenda->toArray();
            
            // Reemplazar procesos con estructura que incluya SIEMPRE el ID
            if (!empty($prenda->procesos)) {
                $prendaArray['procesos'] = $prenda->procesos->map(function($proceso) {
                    return [
                        'id' => $proceso->id,
                        'tipo_proceso_id' => $proceso->tipo_proceso_id,
                        'tipo_proceso' => $proceso->tipoProceso ? $proceso->tipoProceso->nombre : null,
                        'slug' => $proceso->tipoProceso ? $proceso->tipoProceso->slug : null,
                        'ubicaciones' => $proceso->ubicaciones ? json_decode($proceso->ubicaciones, true) : [],
                        'observaciones' => $proceso->observaciones,
                        'estado' => $proceso->estado,
                        'imagenes' => $proceso->imagenes ? $proceso->imagenes->map(fn($img) => [
                            'id' => $img->id,
                            'ruta_original' => $img->ruta_original,
                            'ruta_webp' => $img->ruta_webp,
                            'orden' => $img->orden,
                            'es_principal' => $img->es_principal,
                        ])->toArray() : [],
                        'tallas' => $proceso->tallas ? $proceso->tallas->map(fn($t) => [
                            'genero' => $t->genero,
                            'talla' => $t->talla,
                            'cantidad' => $t->cantidad,
                        ])->toArray() : [],
                    ];
                })->toArray();
            }

            Log::info('[PedidosProduccionController] Procesos retornados al frontend:', [
                'count' => count($prendaArray['procesos'] ?? []),
                'procesos' => array_map(fn($p) => [
                    'id' => $p['id'] ?? 'N/A',
                    'tipo' => $p['tipo_proceso'] ?? 'N/A',
                    'tiene_id' => !empty($p['id']),
                ], $prendaArray['procesos'] ?? [])
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Prenda actualizada correctamente en la base de datos',
                'prenda' => $prendaArray,
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

            // Retornar solo el array de procesos, no todo el objeto
            return response()->json($resultado['procesos'] ?? [], 200);
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
                'DAMA' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'],
                'CABALLERO' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL'],
                'NUMEROS_DAMA' => ['6', '8', '10', '12', '14', '16', '18', '28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'],
                'NUMEROS_CABALLERO' => ['6', '8', '10', '12', '14', '16', '18', '20', '22', '24', '26', '28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50'],
                'UNISEX' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL']
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
            Log::info(' [PRENDA-DATOS-INICIO] Endpoint llamado', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'timestamp' => now()
            ]);

            $service = app(\App\Application\Services\Asesores\ObtenerPedidoDetalleService::class);
            
            Log::info('📡 [PRENDA-DATOS] Llamando al servicio...');
            $prendaData = $service->obtenerPrendaConProcesos((int)$pedidoId, (int)$prendaId);

            Log::info(' [PRENDA-DATOS-RECIBIDOS] Datos obtenidos del servicio', [
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

    /**
     * DELETE /asesores/pedidos/{pedidoId}/imagen/{tipo}/{id}
     * Eliminar imagen de prenda, tela o proceso
     * 
     * @param int $pedidoId - ID del pedido
     * @param string $tipo - Tipo de imagen: 'prenda', 'tela', 'proceso'
     * @param int $id - ID del registro de imagen
     */
    public function eliminarImagen(int $pedidoId, string $tipo, int $id): JsonResponse
    {
        try {
            Log::info('[PedidosProduccionController] DELETE imagen', [
                'pedido_id' => $pedidoId,
                'tipo' => $tipo,
                'id' => $id
            ]);

            // Determinar modelo según tipo
            $modelClass = match($tipo) {
                'prenda' => PrendaFotoPedido::class,
                'tela' => PrendaFotoTelaPedido::class,
                'proceso' => PedidosProcesoImagenes::class,
                default => null
            };

            if (!$modelClass) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de imagen no válido'
                ], 400);
            }

            // Obtener imagen
            $imagen = $modelClass::findOrFail($id);

            Log::info('[PedidosProduccionController] Eliminando imagen', [
                'tipo' => $tipo,
                'id' => $id,
                'ruta_original' => $imagen->ruta_original ?? 'N/A',
                'ruta_webp' => $imagen->ruta_webp ?? 'N/A'
            ]);

            // Eliminar archivos físicos
            $archivosEliminados = [];

            // Eliminar archivo original
            if ($imagen->ruta_original && Storage::disk('public')->exists($imagen->ruta_original)) {
                Storage::disk('public')->delete($imagen->ruta_original);
                $archivosEliminados[] = $imagen->ruta_original;
                Log::info('[PedidosProduccionController] Archivo original eliminado', [
                    'ruta' => $imagen->ruta_original
                ]);
            }

            // Eliminar archivo WebP si es diferente
            if ($imagen->ruta_webp && $imagen->ruta_webp !== $imagen->ruta_original && Storage::disk('public')->exists($imagen->ruta_webp)) {
                Storage::disk('public')->delete($imagen->ruta_webp);
                $archivosEliminados[] = $imagen->ruta_webp;
                Log::info('[PedidosProduccionController] Archivo WebP eliminado', [
                    'ruta' => $imagen->ruta_webp
                ]);
            }

            // Eliminar registro de BD (forceDelete para SoftDeletes)
            $imagen->forceDelete();

            Log::info('[PedidosProduccionController] Imagen eliminada completamente', [
                'tipo' => $tipo,
                'id' => $id,
                'archivos_eliminados' => $archivosEliminados
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Imagen eliminada correctamente',
                'archivos_eliminados' => $archivosEliminados
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('[PedidosProduccionController] Imagen no encontrada', [
                'tipo' => $tipo,
                'id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Imagen no encontrada'
            ], 404);

        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error eliminando imagen', [
                'tipo' => $tipo,
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar imagen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /asesores/pedidos/{id}/eliminar-prenda
     * Eliminar una prenda de un pedido y registrar el motivo en novedades
     * 
     * Body:
     * {
     *   "prenda_id": 123,
     *   "motivo": "Prenda no requerida, cambio en especificaciones"
     * }
     * 
     * Response:
     * {
     *   "success": true,
     *   "message": "Prenda eliminada correctamente",
     *   "prenda_id": 123,
     *   "motivo_registrado": "Prenda no requerida, cambio en especificaciones"
     * }
     * 
     * @param Request $request
     * @param int|string $id - ID del pedido
     * @return JsonResponse
     */
    public function eliminarPrenda(Request $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[PedidosProduccionController] POST /asesores/pedidos/{id}/eliminar-prenda', [
                'pedido_id' => $id,
            ]);

            // Validar datos
            $validated = $request->validate([
                'prenda_id' => 'required|numeric|min:1',
                'motivo' => 'required|string|min:5|max:1000',
            ]);

            $prendaId = (int)$validated['prenda_id'];
            $motivo = $validated['motivo'];

            // Obtener la prenda para conocer su nombre
            $prenda = PrendaPedido::findOrFail($prendaId);
            $nombrePrenda = $prenda->nombre_prenda ?? $prenda->nombre ?? 'Sin nombre';

            // Obtener el pedido
            $pedido = PedidoProduccion::findOrFail($id);

            Log::info('[PedidosProduccionController] Prenda encontrada', [
                'prenda_id' => $prendaId,
                'nombre' => $nombrePrenda,
                'pedido_id' => $id,
            ]);

            // Usar transacción para garantizar eliminación atómica
            DB::beginTransaction();

            // Construir mensaje de eliminación
            $mensajeEliminacion = "[ELIMINADA PRENDA] {$nombrePrenda} - Motivo: {$motivo}";

            // Actualizar novedades del pedido
            if ($pedido->novedades) {
                $pedido->novedades .= "\n\n" . $mensajeEliminacion;
            } else {
                $pedido->novedades = $mensajeEliminacion;
            }
            $pedido->save();

            Log::info('[PedidosProduccionController] Novedades actualizadas', [
                'pedido_id' => $id,
                'novedades_length' => strlen($pedido->novedades),
            ]);

            // Eliminar imágenes asociadas a la prenda
            $prendaFotos = PrendaFotoPedido::where('prenda_pedido_id', $prendaId)->get();
            foreach ($prendaFotos as $foto) {
                // Eliminar archivos físicos
                if ($foto->ruta_original && Storage::disk('public')->exists($foto->ruta_original)) {
                    Storage::disk('public')->delete($foto->ruta_original);
                }
                if ($foto->ruta_webp && $foto->ruta_webp !== $foto->ruta_original && Storage::disk('public')->exists($foto->ruta_webp)) {
                    Storage::disk('public')->delete($foto->ruta_webp);
                }
                // Marcar como eliminado (soft delete)
                $foto->delete();
            }

            Log::info('[PedidosProduccionController] Imágenes de prenda eliminadas', [
                'cantidad' => $prendaFotos->count(),
                'prenda_id' => $prendaId,
            ]);

            // Eliminar fotos de telas/colores (prenda_fotos_tela_pedido)
            // Obtener IDs de prenda_pedido_colores_telas para esta prenda
            $colorTelasIds = DB::table('prenda_pedido_colores_telas')
                ->where('prenda_pedido_id', $prendaId)
                ->pluck('id');
            
            // Eliminar fotos asociadas a los colores/telas
            if ($colorTelasIds->count() > 0) {
                $telasFotos = PrendaFotoTelaPedido::whereIn('prenda_pedido_colores_telas_id', $colorTelasIds)->get();
                foreach ($telasFotos as $foto) {
                    if ($foto->ruta_original && Storage::disk('public')->exists($foto->ruta_original)) {
                        Storage::disk('public')->delete($foto->ruta_original);
                    }
                    if ($foto->ruta_webp && $foto->ruta_webp !== $foto->ruta_original && Storage::disk('public')->exists($foto->ruta_webp)) {
                        Storage::disk('public')->delete($foto->ruta_webp);
                    }
                    $foto->delete();
                }
            }

            // Eliminar telas/colores asociados (prenda_pedido_colores_telas)
            DB::table('prenda_pedido_colores_telas')
                ->where('prenda_pedido_id', $prendaId)
                ->delete();

            // Eliminar tallas/colores asociados (prenda_pedido_talla_colores)
            // Primero obtener los IDs de prenda_pedido_talla para esta prenda
            $tallasIds = DB::table('prenda_pedido_tallas')
                ->where('prenda_pedido_id', $prendaId)
                ->pluck('id');
            
            // Eliminar prenda_pedido_talla_colores (basado en las tallas de la prenda)
            DB::table('prenda_pedido_talla_colores')
                ->whereIn('prenda_pedido_talla_id', $tallasIds)
                ->delete();

            DB::table('prenda_pedido_tallas')
                ->where('prenda_pedido_id', $prendaId)
                ->delete();

            DB::table('prenda_pedido_variantes')
                ->where('prenda_pedido_id', $prendaId)
                ->delete();

            // =====================================================
            // Recopilar IDs de talla_color ANTES de eliminar procesos
            // (necesarios para limpiar bodega_notas)
            // =====================================================
            $tallaColorIds = DB::table('pedidos_procesos_prenda_talla_colores as tc')
                ->join('pedidos_procesos_prenda_tallas as t', 't.id', '=', 'tc.pedidos_procesos_prenda_talla_id')
                ->join('pedidos_procesos_prenda_detalles as d', 'd.id', '=', 't.proceso_prenda_detalle_id')
                ->where('d.prenda_pedido_id', $prendaId)
                ->pluck('tc.id');

            // Eliminar procesos y sus imágenes
            $procesos = \App\Models\PedidosProcesosPrendaDetalle::where('prenda_pedido_id', $prendaId)->get();
            foreach ($procesos as $proceso) {
                // Eliminar imágenes del proceso
                if ($proceso->imagenes) {
                    foreach ($proceso->imagenes as $imagen) {
                        if ($imagen->ruta_original && Storage::disk('public')->exists($imagen->ruta_original)) {
                            Storage::disk('public')->delete($imagen->ruta_original);
                        }
                        if ($imagen->ruta_webp && $imagen->ruta_webp !== $imagen->ruta_original && Storage::disk('public')->exists($imagen->ruta_webp)) {
                            Storage::disk('public')->delete($imagen->ruta_webp);
                        }
                        $imagen->delete();
                    }
                }
                // Eliminar colores de tallas del proceso
                if ($proceso->tallas) {
                    foreach ($proceso->tallas as $talla) {
                        // Eliminar colores asociados a cada talla del proceso
                        PedidosProcesosPrendaTallaColor::where('pedidos_procesos_prenda_talla_id', $talla->id)->delete();
                        $talla->delete();
                    }
                }
                // Marcar como eliminado (soft delete)
                $proceso->delete();
            }

            Log::info('[PedidosProduccionController] Procesos eliminados', [
                'cantidad' => $procesos->count(),
                'prenda_id' => $prendaId,
            ]);

            // =====================================================
            // Eliminar consecutivos de recibos asociados a la prenda
            // =====================================================
            $consecutivosEliminados = ConsecutivoReciboPedido::where('prenda_id', $prendaId)
                ->where('pedido_produccion_id', $id)
                ->delete();

            Log::info('[PedidosProduccionController] Consecutivos de recibos eliminados', [
                'cantidad' => $consecutivosEliminados,
                'prenda_id' => $prendaId,
                'pedido_id' => $id,
            ]);

            // =====================================================
            // Eliminar detalles de bodega asociados a la prenda (soft delete)
            // =====================================================
            $bodegaDetallesEliminados = DB::table('bodega_detalles_talla')
                ->where('prenda_id', $prendaId)
                ->where('pedido_produccion_id', $id)
                ->whereNull('deleted_at')
                ->update(['deleted_at' => now()]);

            Log::info('[PedidosProduccionController] Detalles de bodega eliminados', [
                'cantidad' => $bodegaDetallesEliminados,
                'prenda_id' => $prendaId,
                'pedido_id' => $id,
            ]);

            // =====================================================
            // Eliminar notas de bodega vinculadas a los talla_color_id
            // =====================================================
            if ($tallaColorIds->isNotEmpty()) {
                $bodegaNotasEliminadas = DB::table('bodega_notas')
                    ->whereIn('talla_color_id', $tallaColorIds)
                    ->delete();
                Log::info('[PedidosProduccionController] Notas de bodega eliminadas', [
                    'cantidad' => $bodegaNotasEliminadas,
                    'prenda_id' => $prendaId,
                ]);
            }

            // Marcar prenda como eliminada (soft delete)
            $prenda->delete();

            Log::info('[PedidosProduccionController] Prenda eliminada exitosamente', [
                'prenda_id' => $prendaId,
                'nombre' => $nombrePrenda,
                'pedido_id' => $id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Prenda eliminada correctamente',
                'prenda_id' => $prendaId,
                'prenda_nombre' => $nombrePrenda,
                'motivo_registrado' => $motivo,
                'pedido_id' => $id,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            Log::warning('[PedidosProduccionController] Prenda o pedido no encontrado', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Prenda o pedido no encontrado',
            ], 404);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::warning('[PedidosProduccionController] Validación fallida al eliminar prenda', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('[PedidosProduccionController] Error eliminando prenda', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar prenda: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Homologar EPP: Marcar como eliminado y crear uno nuevo con los datos editados
     * POST /asesores/pedidos/{id}/homologar-epp
     */
    public function homologarEpp(Request $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[PedidosProduccionController] POST /asesores/pedidos/{id}/homologar-epp', [
                'pedido_id' => $id,
            ]);

            // Validar datos
            $validated = $request->validate([
                'pedido_epp_id' => 'required|numeric|min:1',
                'motivo' => 'required|string|min:5|max:1000',
                'cantidad' => 'required|numeric|min:1',
                'observaciones' => 'nullable|string',
                'epp_id' => 'nullable|numeric',
            ]);

            $pedidoEppIdAnterior = (int)$validated['pedido_epp_id'];
            $motivo = $validated['motivo'];
            $cantidadNueva = (int)$validated['cantidad'];
            $observacionesNuevas = $validated['observaciones'] ?? '';
            $eppIdNuevo = isset($validated['epp_id']) ? (int)$validated['epp_id'] : null;

            // Obtener el EPP actual del pedido
            $pedidoEppAnterior = \App\Models\PedidoEpp::findOrFail($pedidoEppIdAnterior);
            
            // Obtener datos del EPP anterior
            $epp = $pedidoEppAnterior->epp;
            $nombreEpp = $epp->nombre_completo ?? $epp->nombre ?? 'EPP Sin nombre';

            // Obtener el pedido
            $pedido = PedidoProduccion::findOrFail($id);

            Log::info('[PedidosProduccionController] EPP encontrado para homologar', [
                'epp_id_anterior' => $pedidoEppIdAnterior,
                'nombre' => $nombreEpp,
                'pedido_id' => $id,
                'datos_nuevos' => [
                    'cantidad' => $cantidadNueva,
                    'observaciones' => $observacionesNuevas,
                    'epp_id_nuevo' => $eppIdNuevo
                ]
            ]);

            // Construir mensaje de homologación
            $mensajeHomologacion = "[HOMOLOGADO EPP] {$nombreEpp} (Cantidad anterior: {$pedidoEppAnterior->cantidad} → Nueva: {$cantidadNueva}) - Motivo: {$motivo}";

            // Actualizar novedades del pedido
            if ($pedido->novedades) {
                $pedido->novedades .= "\n\n" . $mensajeHomologacion;
            } else {
                $pedido->novedades = $mensajeHomologacion;
            }
            $pedido->save();

            Log::info('[PedidosProduccionController] Novedades actualizadas', [
                'pedido_id' => $id,
            ]);

            // Marcar el EPP anterior como eliminado (soft delete)
            $pedidoEppAnterior->delete();

            Log::info('[PedidosProduccionController] EPP anterior marcado como eliminado', [
                'epp_id' => $pedidoEppIdAnterior,
            ]);

            // Crear el nuevo EPP con los datos editados
            $eppNuevo = new \App\Models\PedidoEpp();
            $eppNuevo->pedido_produccion_id = $pedidoEppAnterior->pedido_produccion_id;
            $eppNuevo->epp_id = $eppIdNuevo ?? $pedidoEppAnterior->epp_id;
            $eppNuevo->cantidad = $cantidadNueva;
            $eppNuevo->observaciones = $observacionesNuevas;
            $eppNuevo->homologado_de = $pedidoEppIdAnterior; // Guardar referencia al EPP anterior
            $eppNuevo->save();

            Log::info('[PedidosProduccionController] EPP duplicado con nuevos datos', [
                'epp_id_nuevo' => $eppNuevo->id,
                'epp_id_anterior' => $pedidoEppIdAnterior,
                'cantidad_nueva' => $cantidadNueva,
            ]);

            // Duplicar imágenes del EPP anterior (si existen)
            $imagenesAntiguas = \App\Models\PedidoEppImagen::where('pedido_epp_id', $pedidoEppIdAnterior)->get();
            foreach ($imagenesAntiguas as $imagenAntigua) {
                // Crear nueva imagen con los mismos datos
                $imagenNueva = new \App\Models\PedidoEppImagen();
                $imagenNueva->pedido_epp_id = $eppNuevo->id;
                $imagenNueva->ruta_original = $imagenAntigua->ruta_original;
                $imagenNueva->ruta_web = $imagenAntigua->ruta_web;
                $imagenNueva->principal = $imagenAntigua->principal;
                $imagenNueva->orden = $imagenAntigua->orden;
                $imagenNueva->save();
            }

            Log::info('[PedidosProduccionController] Imágenes duplicadas', [
                'cantidad' => $imagenesAntiguas->count(),
                'epp_id_nuevo' => $eppNuevo->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'EPP homologado correctamente',
                'epp_id_anterior' => $pedidoEppIdAnterior,
                'epp_id_nuevo' => $eppNuevo->id,
                'epp_nombre' => $nombreEpp,
                'motivo_registrado' => $motivo,
                'pedido_id' => $id,
                'cambios' => [
                    'cantidad_anterior' => $pedidoEppAnterior->cantidad,
                    'cantidad_nueva' => $cantidadNueva,
                    'epp_id_nuevo' => $eppIdNuevo,
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('[PedidosProduccionController] EPP o pedido no encontrado', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'EPP o pedido no encontrado',
            ], 404);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[PedidosProduccionController] Validación fallida al homologar EPP', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error homologando EPP', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al homologar EPP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar EPP de un pedido
     * POST /asesores/pedidos/{id}/eliminar-epp
     */
    public function eliminarEpp(Request $request, int|string $id): JsonResponse
    {
        try {
            Log::info('[PedidosProduccionController] POST /asesores/pedidos/{id}/eliminar-epp', [
                'pedido_id' => $id,
            ]);

            // Validar datos
            $validated = $request->validate([
                'epp_id' => 'required|numeric|min:1',
                'motivo' => 'required|string|min:5|max:1000',
            ]);

            $eppId = (int)$validated['epp_id'];
            $motivo = $validated['motivo'];

            // Obtener el EPP del pedido
            $pedidoEpp = \App\Models\PedidoEpp::findOrFail($eppId);
            
            // Obtener datos del EPP
            $epp = $pedidoEpp->epp; // Relación con tabla epp
            $nombreEpp = $epp->nombre_completo ?? $epp->nombre ?? 'EPP Sin nombre';

            // Obtener el pedido
            $pedido = PedidoProduccion::findOrFail($id);

            Log::info('[PedidosProduccionController] EPP encontrado', [
                'epp_id' => $eppId,
                'nombre' => $nombreEpp,
                'pedido_id' => $id,
            ]);

            // Construir mensaje de eliminación
            $mensajeEliminacion = "[ELIMINADO EPP] {$nombreEpp} (Cantidad: {$pedidoEpp->cantidad}) - Motivo: {$motivo}";

            // Actualizar novedades del pedido
            if ($pedido->novedades) {
                $pedido->novedades .= "\n\n" . $mensajeEliminacion;
            } else {
                $pedido->novedades = $mensajeEliminacion;
            }
            $pedido->save();

            Log::info('[PedidosProduccionController] Novedades actualizadas', [
                'pedido_id' => $id,
                'novedades_length' => strlen($pedido->novedades),
            ]);

            // Eliminar imágenes del EPP
            $imagenes = \App\Models\PedidoEppImagen::where('pedido_epp_id', $eppId)->get();
            foreach ($imagenes as $imagen) {
                // Eliminar archivos físicos
                if ($imagen->ruta_original && Storage::disk('public')->exists($imagen->ruta_original)) {
                    Storage::disk('public')->delete($imagen->ruta_original);
                }
                if ($imagen->ruta_web && $imagen->ruta_web !== $imagen->ruta_original && Storage::disk('public')->exists($imagen->ruta_web)) {
                    Storage::disk('public')->delete($imagen->ruta_web);
                }
                // Marcar como eliminado (soft delete)
                $imagen->delete();
            }

            Log::info('[PedidosProduccionController] Imágenes de EPP eliminadas', [
                'cantidad' => $imagenes->count(),
                'epp_id' => $eppId,
            ]);

            // Marcar EPP como eliminado (soft delete)
            $pedidoEpp->delete();

            Log::info('[PedidosProduccionController] EPP eliminado exitosamente', [
                'epp_id' => $eppId,
                'nombre' => $nombreEpp,
                'pedido_id' => $id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'EPP eliminado correctamente',
                'epp_id' => $eppId,
                'epp_nombre' => $nombreEpp,
                'motivo_registrado' => $motivo,
                'pedido_id' => $id,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('[PedidosProduccionController] EPP o pedido no encontrado', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'EPP o pedido no encontrado',
            ], 404);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[PedidosProduccionController] Validación fallida al eliminar EPP', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('[PedidosProduccionController] Error eliminando EPP', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar EPP: ' . $e->getMessage(),
            ], 500);
        }
    }

}

