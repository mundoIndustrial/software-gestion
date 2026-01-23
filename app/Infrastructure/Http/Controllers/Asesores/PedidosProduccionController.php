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
use App\Application\Pedidos\DTOs\ListarProduccionPedidosDTO;
use App\Application\Pedidos\DTOs\ObtenerProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\CrearProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\ActualizarProduccionPedidoDTO;
use App\Application\Pedidos\DTOs\AnularProduccionPedidoDTO;

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
            Log::info(' [PedidosController] GET /api/pedidos/filtro/estado');

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

    /**
     * POST /api/pedidos-produccion/actualizar-prenda
     * Actualizar datos de una prenda específica dentro de un pedido
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

            $pedidoId = (int) $validated['pedidoId'];
            $prendasIndex = (int) $validated['prendasIndex'];

            Log::info(' Actualizando prenda', [
                'pedido_id' => $pedidoId,
                'prenda_index' => $prendasIndex,
            ]);

            // Obtener el pedido directamente por ID
            $pedido = \App\Models\PedidoProduccion::find($pedidoId);

            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado',
                ], 404);
            }

            // Validar que el índice de prenda existe
            $prendas = $pedido->prendas()->get()->toArray();
            if (!isset($prendas[$prendasIndex])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Prenda no encontrada en este pedido',
                ], 404);
            }

            // Obtener la prenda como modelo (no array) para poder guardar
            $prenda = $pedido->prendas()->get()[$prendasIndex];

            // Actualizar campos simples
            if (isset($validated['nombre'])) {
                $prenda->nombre_prenda = $validated['nombre'];
            }
            if (isset($validated['descripcion'])) {
                $prenda->descripcion = $validated['descripcion'];
            }

            // Actualizar tallas (guardadas en cantidad_talla como JSON)
            if (isset($validated['tallas'])) {
                $prenda->cantidad_talla = $validated['tallas'];
            }

            // Guardar cambios
            $prenda->save();

            Log::info(' Prenda actualizada correctamente', [
                'pedido_id' => $pedidoId,
                'prenda_index' => $prendasIndex,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Prenda actualizada correctamente',
                'prenda' => $prenda,
            ], 200);

        } catch (\Exception $e) {
            Log::error(' Error actualizando prenda', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar prenda: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Agregar prenda completa (con telas e imágenes) al pedido en edición
     */
    public function agregarPrendaCompleta(Request $request, int|string $id): JsonResponse
    {
        try {
            Log::info(' [PedidosController] POST /asesores/pedidos/{id}/agregar-prenda', ['id' => $id]);

            // Validar datos básicos
            $validated = $request->validate([
                'nombre_prenda' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'origen' => 'required|string|in:bodega,confeccion',
                'cantidad_talla' => 'nullable|json',
                'procesos' => 'nullable|json',
                'novedad' => 'required|string|max:500',  // NOVEDAD OBLIGATORIA
                'imagenes' => 'nullable|array',
                'imagenes.*' => 'nullable|image|max:5120',
                'telas' => 'nullable|array',
            ]);

            Log::info(' [PedidosController] Datos validados', $validated);

            // Procesar imágenes de prenda
            $imagenesGuardadas = [];
            if ($request->hasFile('imagenes')) {
                foreach ($request->file('imagenes') as $imagen) {
                    $path = $imagen->store('prendas', 'public');
                    $imagenesGuardadas[] = $path;
                    Log::info(' Imagen de prenda guardada', ['path' => $path]);
                }
            }

            // Procesar telas con imágenes
            $telasGuardadas = [];
            if ($request->has('telas')) {
                $telas = $request->input('telas');
                foreach ($telas as $telaIdx => $tela) {
                    $telaData = [
                        'tela' => $tela['tela'] ?? '',
                        'color' => $tela['color'] ?? '',
                        'referencia' => $tela['referencia'] ?? '',
                        'imagenes' => []
                    ];

                    // Procesar imágenes de tela
                    if ($request->hasFile("telas.$telaIdx.imagenes")) {
                        foreach ($request->file("telas.$telaIdx.imagenes") as $imagen) {
                            $path = $imagen->store('telas', 'public');
                            $telaData['imagenes'][] = $path;
                            Log::info(' Imagen de tela guardada', ['path' => $path]);
                        }
                    }

                    $telasGuardadas[] = $telaData;
                }
            }

            // Construir datos de la prenda para el comando
            $prendaData = [
                'nombre_prenda' => $validated['nombre_prenda'],
                'descripcion' => $validated['descripcion'] ?? '',
                'origen' => $validated['origen'],
                'imagenes' => $imagenesGuardadas,
                'telas' => $telasGuardadas,
                'procesos' => $validated['procesos'] ? json_decode($validated['procesos'], true) : [],
                'novedad' => $validated['novedad'],
                'cantidad' => 1,
                'tipo_manga' => null,
                'tipo_broche' => null,
                'color_id' => null,
                'tela_id' => null,
            ];

            Log::info(' Datos de prenda preparados', $prendaData);

            // Guardar novedad en el pedido
            if (!empty($validated['novedad'])) {
                $pedido = PedidoProduccion::find($id);
                if ($pedido) {
                    // Agregar nueva novedad a las existentes
                    $novedadesActuales = !empty($pedido->novedades) ? $pedido->novedades . "\n" : '';
                    $timestamp = now()->format('Y-m-d H:i:s');
                    $usuario = auth()->user()->name ?? 'Sistema';
                    $novedadesNuevas = $novedadesActuales . "[{$timestamp}] {$usuario}: {$validated['novedad']}";
                    
                    $pedido->update(['novedades' => $novedadesNuevas]);
                    Log::info(' Novedad guardada en pedido', [
                        'pedido_id' => $id,
                        'novedad' => $validated['novedad']
                    ]);
                }
            }

            // Guardar en la base de datos usando el comando existente
            $prendaGuardada = $this->commandBus->execute(new AgregarPrendaAlPedidoCommand(
                pedidoId: $id,
                prendaData: $prendaData,
                tipo: 'sin_cotizacion'
            ));

            Log::info(' Prenda guardada en BD', [
                'pedido_id' => $id,
                'prenda_data' => $prendaData,
            ]);

            // Guardar tallas SOLO en tabla relacional prenda_pedido_tallas
            if ($prendaGuardada && !empty($validated['cantidad_talla'])) {
                $this->prendaPedidoRepository->guardarTallasDesdeJson(
                    $prendaGuardada->id,
                    $validated['cantidad_talla']
                );
                Log::info(' Tallas guardadas en prenda_pedido_tallas', ['prenda_id' => $prendaGuardada->id]);
            }

            // Guardar imágenes en prenda_fotos_pedido
            if ($prendaGuardada) {
                Log::info(' Guardando imágenes en prenda_fotos_pedido para prenda ' . $prendaGuardada->id);
                try {
                    foreach ($imagenesGuardadas as $orden => $rutaImagen) {
                        if (!empty($rutaImagen)) {
                            \DB::table('prenda_fotos_pedido')->insert([
                                'prenda_pedido_id' => $prendaGuardada->id,
                                'ruta_webp' => $rutaImagen,
                                'ruta_original' => $rutaImagen,
                                'orden' => $orden + 1,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            Log::debug('   Foto insertada: ' . $rutaImagen);
                        }
                    }
                    
                    Log::info('  Total de fotos guardadas: ' . count($imagenesGuardadas));
                } catch (\Exception $e) {
                    Log::error('Error guardando fotos en prenda_fotos_pedido: ' . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Prenda agregada correctamente a la base de datos',
                'prenda' => $prendaGuardada ? $prendaGuardada->toArray() : $prendaData,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning(' Validación fallida', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error(' Error agregando prenda completa', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al agregar prenda: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /asesores/pedidos/{id}/actualizar-prenda
     * Actualizar una prenda existente en un pedido
     * 
     * Similar a agregarPrendaCompleta pero para actualizar
     */
    public function actualizarPrendaCompleta(Request $request, int|string $id): JsonResponse
    {
        try {
            Log::info(' [PedidosController] POST /asesores/pedidos/{id}/actualizar-prenda', ['id' => $id]);

            // Validar datos básicos
            $validated = $request->validate([
                'prenda_id' => 'required|numeric|min:1',  // ID de la prenda a actualizar
                'nombre_prenda' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'origen' => 'required|string|in:bodega,confeccion',
                'cantidad_talla' => 'nullable|json',
                'procesos' => 'nullable|json',
                'novedad' => 'required|string|max:500',  // NOVEDAD OBLIGATORIA
                'imagenes' => 'nullable|array',
                'imagenes.*' => 'nullable|image|max:5120',
                'telas' => 'nullable|array',
            ]);
            
            // Convertir prenda_id a integer
            $validated['prenda_id'] = (int) $validated['prenda_id'];

            Log::info(' [PedidosController] Datos validados para actualización', $validated);

            // Obtener la prenda existente
            $prenda = PrendaPedido::find($validated['prenda_id']);
            if (!$prenda) {
                return response()->json([
                    'success' => false,
                    'message' => 'Prenda no encontrada',
                ], 404);
            }

            // Procesar imágenes de prenda
            $imagenesGuardadas = [];
            if ($request->hasFile('imagenes')) {
                foreach ($request->file('imagenes') as $imagen) {
                    $path = $imagen->store('prendas', 'public');
                    $imagenesGuardadas[] = $path;
                    Log::info(' Imagen de prenda guardada', ['path' => $path]);
                }
            }

            // Actualizar SOLO campos reales de prendas_pedido
            // NO incluir cantidad_talla aquí - se maneja SOLO en prenda_pedido_tallas
            $prenda->nombre_prenda = $validated['nombre_prenda'];
            $prenda->descripcion = $validated['descripcion'] ?? '';
            $prenda->save();

            // Guardar tallas SOLO en tabla relacional prenda_pedido_tallas
            if (!empty($validated['cantidad_talla'])) {
                $this->prendaPedidoRepository->guardarTallasDesdeJson(
                    $validated['prenda_id'],
                    $validated['cantidad_talla']
                );
                Log::info(' Tallas actualizadas en prenda_pedido_tallas', ['prenda_id' => $validated['prenda_id']]);
            }

            // Guardar imágenes en prenda_fotos_pedido
            Log::info(' Guardando imágenes en prenda_fotos_pedido para prenda ' . $validated['prenda_id']);
            try {
                // Primero, eliminar las fotos antiguas
                \DB::table('prenda_fotos_pedido')
                    ->where('prenda_pedido_id', $validated['prenda_id'])
                    ->delete();
                
                Log::info('  Fotos antiguas eliminadas');
                
                // Luego, insertar las nuevas
                foreach ($imagenesGuardadas as $orden => $rutaImagen) {
                    if (!empty($rutaImagen)) {
                        \DB::table('prenda_fotos_pedido')->insert([
                            'prenda_pedido_id' => $validated['prenda_id'],
                            'ruta_webp' => $rutaImagen,
                            'ruta_original' => $rutaImagen,
                            'orden' => $orden + 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        Log::debug('   Foto insertada: ' . $rutaImagen);
                    }
                }
                
                Log::info('  Total de fotos guardadas: ' . count($imagenesGuardadas));
            } catch (\Exception $e) {
                Log::error('Error guardando fotos en prenda_fotos_pedido: ' . $e->getMessage());
            }

            Log::info(' Prenda actualizada en BD', [
                'prenda_id' => $validated['prenda_id'],
                'pedido_id' => $id,
            ]);

            // Guardar novedad en el pedido
            if (!empty($validated['novedad'])) {
                $pedido = PedidoProduccion::find($id);
                if ($pedido) {
                    // Agregar nueva novedad a las existentes
                    $novedadesActuales = !empty($pedido->novedades) ? $pedido->novedades . "\n" : '';
                    $timestamp = now()->format('Y-m-d H:i:s');
                    $usuario = auth()->user()->name ?? 'Sistema';
                    $novedadesNuevas = $novedadesActuales . "[{$timestamp}] {$usuario}: {$validated['novedad']}";
                    
                    $pedido->update(['novedades' => $novedadesNuevas]);
                    Log::info(' Novedad de actualización guardada en pedido', [
                        'pedido_id' => $id,
                        'novedad' => $validated['novedad']
                    ]);
                }
            }

            // Recargar la prenda desde BD con todas las imágenes y datos relacionados
            $prendaActualizada = PrendaPedido::find($validated['prenda_id']);
            
            // Obtener imágenes de la BD
            $fotosGuardadas = [];
            try {
                $fotos = \DB::table('prenda_fotos_pedido')
                    ->where('prenda_pedido_id', $validated['prenda_id'])
                    ->where('deleted_at', null)
                    ->orderBy('orden')
                    ->select('ruta_webp')
                    ->get();
                
                $fotosGuardadas = $fotos->map(function($foto) {
                    $ruta = str_replace('\\', '/', $foto->ruta_webp);
                    if (strpos($ruta, '/storage/') === 0) {
                        return $ruta;
                    }
                    if (strpos($ruta, 'storage/') === 0) {
                        return '/' . $ruta;
                    }
                    if (strpos($ruta, '/') !== 0) {
                        return '/storage/' . $ruta;
                    }
                    return $ruta;
                })->toArray();
            } catch (\Exception $e) {
                Log::debug('Error obteniendo imágenes de prenda actualizada: ' . $e->getMessage());
                $fotosGuardadas = [];
            }

            return response()->json([
                'success' => true,
                'message' => 'Prenda actualizada correctamente en la base de datos',
                'prenda' => array_merge($prendaActualizada->toArray(), [
                    'imagenes' => $fotosGuardadas,
                    'telasAgregadas' => []  // Se recargará desde la vista cuando el frontend recarga /datos-edicion
                ]),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning(' Validación fallida en actualización', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error(' Error actualizando prenda completa', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar prenda: ' . $e->getMessage(),
            ], 500);
        }
    }
}
