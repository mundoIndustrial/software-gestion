<?php

namespace App\Infrastructure\Http\Controllers\Epp;

use App\Http\Controllers\Controller;
use App\Domain\Epp\Queries\BuscarEppQuery;
use App\Domain\Epp\Queries\ObtenerEppPorIdQuery;
use App\Domain\Epp\Queries\ObtenerEppPorCategoriaQuery;
use App\Domain\Epp\Queries\ListarEppActivosQuery;
use App\Domain\Epp\Queries\ListarCategoriasEppQuery;
use App\Domain\Epp\Queries\ObtenerEppDelPedidoQuery;
use App\Domain\Epp\Commands\AgregarEppAlPedidoCommand;
use App\Domain\Epp\Commands\EliminarEppDelPedidoCommand;
use App\Application\Commands\CrearEppCommand;
use App\Domain\Shared\CQRS\QueryBus;
use App\Domain\Shared\CQRS\CommandBus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Epp;
use App\Models\EppImagen;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Storage;

/**
 * Controller: EppController
 * 
 * Infrastructure Layer - Maneja requests HTTP y dispara CQRS
 * Cumple: Separacion de responsabilidades, DDD
 */
class EppController extends Controller
{
    public function __construct(
        private QueryBus $queryBus,
        private CommandBus $commandBus,
    ) {}

    /**
     * GET /api/epp
     * 
     * Buscar EPP o listar todos los activos
     * Query parameters:
     * - q: termino de busqueda (codigo o nombre)
     * - categoria: filtrar por categoria
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $termino = $request->query('q');
            $categoria = $request->query('categoria');

            // Debug: Log de entrada
            \Log::info('[EppController] Busqueda iniciada', [
                'termino' => $termino,
                'categoria' => $categoria,
                'url' => $request->url(),
                'query_string' => $request->getQueryString(),
            ]);

            if ($termino) {
                $query = new BuscarEppQuery($termino);
            } elseif ($categoria) {
                $query = new ObtenerEppPorCategoriaQuery($categoria);
            } else {
                $query = new ListarEppActivosQuery();
            }

            $epps = $this->queryBus->execute($query);

            \Log::info('[EppController] Busqueda completada', [
                'total' => is_countable($epps) ? count($epps) : 0,
                'tipo_respuesta' => gettype($epps),
                'datos' => $epps,
            ]);

            return response()->json([
                'success' => true,
                'data' => $epps,
                'total' => is_countable($epps) ? count($epps) : 0,
            ]);
        } catch (\DomainException $e) {
            \Log::warning('  [EppController] DomainException:', [
                'message' => $e->getMessage(),
                'termino' => $termino ?? null,
                'categoria' => $categoria ?? null,
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            \Log::error(' [EppController] Error al buscar EPP:', [
                'message' => $e->getMessage(),
                'termino' => $termino ?? null,
                'categoria' => $categoria ?? null,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar EPP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/epp/{id}
     * 
     * Obtener EPP por ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $query = new ObtenerEppPorIdQuery($id);
            $epp = $this->queryBus->execute($query);

            if (!$epp) {
                return response()->json([
                    'success' => false,
                    'message' => 'EPP no encontrado',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $epp,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener EPP',
            ], 500);
        }
    }

    /**
     * GET /api/epp/gestion
     * 
     * Endpoint simplificado para vista de gestion (incluye relacion con categoria)
     */
    public function indexSimple(Request $request): JsonResponse
    {
        try {
            $termino = $request->query('q');
            $categoria = $request->query('categoria');
            $page = $request->query('page', 1);
            $perPage = $request->query('per_page', 20);

            // Query simple sin relaciones para evitar errores
            $query = Epp::query();

            // Busqueda
            if ($termino) {
                $query->where(function($q) use ($termino) {
                    $q->where('nombre_completo', 'LIKE', "%{$termino}%")
                      ->orWhere('marca', 'LIKE', "%{$termino}%");
                });
            }

            // Filtro por categoria
            if ($categoria) {
                $query->where('categoria_id', $categoria);
            }

            // Filtro por activos (por defecto)
            if (!$request->has('mostrar_inactivos')) {
                $query->where('activo', 1);
            }

            // Paginacion
            $total = $query->count();
            $epps = $query->offset(($page - 1) * $perPage)
                          ->limit($perPage)
                          ->orderBy('nombre_completo')
                          ->get();

            // Agregar informacion de asociaciones
            $eppsConAsociaciones = $epps->map(function ($epp) {
                $pedidosAsociados = \App\Models\PedidoEpp::where('epp_id', $epp->id)->count();
                return array_merge($epp->toArray(), [
                    'tiene_asociaciones' => $pedidosAsociados > 0,
                    'pedidos_asociados' => $pedidosAsociados,
                ]);
            });

            \Log::info('[EppController] indexSimple - Resultados', [
                'total' => $total,
                'epps_count' => $epps->count(),
                'page' => $page,
                'per_page' => $perPage,
                'termino' => $termino,
                'categoria' => $categoria,
                'primer_epp' => $epps->first() ? $epps->first()->toArray() : null
            ]);

            return response()->json([
                'success' => true,
                'data' => $eppsConAsociaciones,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
            ]);
        } catch (\Exception $e) {
            \Log::error('[EppController] Error en indexSimple:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar EPPs: ' . $e->getMessage(),
                'debug_info' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]
            ], 500);
        }
    }

    /**
     * POST /api/epp
     * 
     * Crear nuevo EPP
     * Solo requiere: nombre y descripcion
     * Los campos adicionales (categoria, cantidad, observaciones, imagenes) se agregan despues en la edicion
     */
    public function store(Request $request): JsonResponse
    {
        try {
            \Log::info('[EppController] === INICIANDO CREACION DE EPP ===');
            \Log::info('[EppController] Request data:', $request->all());
            
            $validated = $request->validate([
                'nombre_completo' => 'required|string|max:500',
                'marca' => 'nullable|string|max:100',
                'tipo' => 'required|in:PRODUCTO,SERVICIO',
                'talla' => 'nullable|string|max:100',
                'color' => 'nullable|string|max:100',
                'categoria_id' => 'nullable|integer|exists:epp_categorias,id',
                'descripcion' => 'nullable|string',
                'activo' => 'required|boolean',
            ]);

            \Log::info('[EppController] Validacion exitosa:', $validated);

            // Verificar si el EPP ya existe (case-insensitive)
            $eppExistente = Epp::whereRaw('LOWER(nombre_completo) = ?', [strtolower($validated['nombre_completo'])])
                ->first();

            if ($eppExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este EPP ya existe en el sistema',
                    'epp_existente' => true,
                    'epp_id' => $eppExistente->id,
                    'epp_nombre' => $eppExistente->nombre_completo,
                ], 400);
            }

            $command = new CrearEppCommand(
                nombre: $validated['nombre_completo'],
                categoria: 'General',
                codigo: null,
                descripcion: $validated['descripcion'] ?? null
            );

            \Log::info('[EppController] Comando creado:', [
                'command' => class_basename($command),
                'nombre' => $validated['nombre_completo'],
            ]);

            $epp = $this->commandBus->execute($command);

            \Log::info('[EppController] EPP creado exitosamente:', ['epp' => $epp]);

            return response()->json([
                'success' => true,
                'message' => 'EPP creado exitosamente',
                'data' => $epp,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('[EppController]  Validation error:', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validacion fallida',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('[EppController]  Error creating EPP', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al crear EPP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/epp/categorias/all
     * 
     * Obtener todas las categorias
     */
    public function crearEppSimple(Request $request): JsonResponse
    {
        try {
            \Log::info('[EppController::crearEppSimple] Iniciando creacion de EPP simplificada');
            \Log::info('[EppController::crearEppSimple] Request completa:', $request->all());
            
            // Validacion simple
            $validated = $request->validate([
                'nombre_completo' => 'required|string|max:500',
            ]);

            \Log::info('[EppController::crearEppSimple] Datos validados:', $validated);

            // Obtener otros parametros opcionales
            $categoria_id = $request->input('categoria_id', null);
            $tipo = $request->input('tipo', 'PRODUCTO');
            $activo = filter_var($request->input('activo', true), FILTER_VALIDATE_BOOLEAN);

            \Log::info('[EppController::crearEppSimple] Parametros opcionales:', [
                'categoria_id' => $categoria_id,
                'tipo' => $tipo,
                'activo' => $activo
            ]);

            // Buscar si ya existe un EPP con el mismo nombre (case-insensitive)
            $eppExistente = \App\Models\Epp::whereRaw('LOWER(nombre_completo) = ?', [strtolower($validated['nombre_completo'])])
                ->first();

            if ($eppExistente) {
                \Log::info('[EppController::crearEppSimple] EPP ya existe:', $eppExistente->toArray());
                
                return response()->json([
                    'success' => false,
                    'message' => 'Este EPP ya existe en el sistema',
                    'epp_existente' => true,
                    'epp_id' => $eppExistente->id,
                    'epp_nombre' => $eppExistente->nombre_completo,
                    'data' => $eppExistente,
                ], 400);
            }

            // Crear EPP directamente en la base de datos
            $epp = \App\Models\Epp::create([
                'nombre_completo' => $validated['nombre_completo'],
                'categoria_id' => $categoria_id,
                'tipo' => $tipo,
                'activo' => $activo,
                'marca' => null,
                'talla' => null,
                'color' => null,
                'descripcion' => null,
            ]);

            \Log::info('[EppController::crearEppSimple] EPP creado exitosamente:', $epp->toArray());

            return response()->json([
                'success' => true,
                'message' => 'EPP creado exitosamente',
                'data' => $epp,
                'epp' => $epp, // Ambas formas para compatibilidad
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('[EppController::crearEppSimple] Validation error:', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validacion fallida',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('[EppController::crearEppSimple] Error creando EPP:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al crear EPP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/epp/categorias/all
     * 
     * Obtener todas las categorias
     */
    public function categorias(): JsonResponse
    {
        try {
            $query = new ListarCategoriasEppQuery();
            $categorias = $this->queryBus->execute($query);

            return response()->json([
                'success' => true,
                'data' => $categorias,
                'total' => count($categorias),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener categorias',
            ], 500);
        }
    }

    /**
     * GET /api/epp/categorias/simple
     * 
     * Obtener todas las categorias (metodo simplificado)
     */
    public function categoriasSimple(): JsonResponse
    {
        try {
            $categorias = \App\Models\EppCategoria::where('activo', true)
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categorias,
                'total' => $categorias->count(),
            ]);
        } catch (\Exception $e) {
            \Log::error('[EppController] Error en categoriasSimple:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener categorias: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/pedidos/{pedidoId}/epp
     * 
     * Obtener EPP de un pedido
     */
    public function obtenerDelPedido(int $pedidoId): JsonResponse
    {
        try {
            $query = new ObtenerEppDelPedidoQuery($pedidoId);
            $epps = $this->queryBus->execute($query);

            return response()->json([
                'success' => true,
                'data' => $epps,
                'total' => count($epps),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener EPP del pedido',
            ], 500);
        }
    }

    /**
     * POST /api/pedidos/{pedidoId}/epp/agregar
     * 
     * Agregar EPP a un pedido con imagenes opcionales
     */
    public function agregar(int $pedidoId, Request $request): JsonResponse
    {
        try {
            // Debug: Log all request data before validation
            \Log::info('[EppController::agregar] Request recibido', [
                'pedidoId' => $pedidoId,
                'all_input' => $request->except(['imagenes']),
                'has_imagenes' => $request->hasFile('imagenes'),
                'imagenes_count' => $request->hasFile('imagenes') ? count($request->file('imagenes')) : 0,
                'content_type' => $request->header('Content-Type'),
                'method' => $request->method(),
                'all_keys' => array_keys($request->all()),
            ]);

            $validated = $request->validate([
                'epp_id' => 'required|integer|exists:epps,id',
                'cantidad' => 'required|integer|min:1',
                'observaciones' => 'nullable|string|max:1000',
                'novedad' => 'nullable|string|max:2000',
                'imagenes' => 'nullable|array|max:5',
                'imagenes.*' => 'nullable|file|image|max:5120',
            ]);

            // Procesar imagenes si existen
            $imagenes = [];
            if ($request->hasFile('imagenes')) {
                foreach ($request->file('imagenes') as $imagen) {
                    if ($imagen->isValid()) {
                        // Guardar imagen en directorio especifico del pedido
                        $ruta = $imagen->store("pedido/{$pedidoId}/epp", 'public');
                        $imagenes[] = $ruta;
                    }
                }
            }

            $command = new AgregarEppAlPedidoCommand(
                pedidoId: $pedidoId,
                eppId: $validated['epp_id'],
                cantidad: $validated['cantidad'],
                observaciones: $validated['observaciones'] ?? null,
                imagenes: $imagenes,
            );

            $resultado = $this->commandBus->execute($command);

            // Guardar novedad si se proporciono
            if (!empty($validated['novedad'])) {
                try {
                    $pedido = PedidoProduccion::with('asesora:id,name')->find($pedidoId);
                    $actor = $this->resolverActor($request, $pedido);
                    $usuario = $actor['usuario'];
                    $nombreUsuario = $actor['nombre'];
                    $rol = $actor['rol'];

                    $fechaFormato = now()->format('d/m/Y h:i A');
                    $linea_novedad = "{$rol}-{$nombreUsuario}-{$fechaFormato} - {$validated['novedad']}";

                    if ($pedido && !$this->esPedidoBorrador($pedido)) {
                        $pedido->novedades = !empty($pedido->novedades)
                            ? $pedido->novedades . "\n\n" . $linea_novedad
                            : $linea_novedad;
                        $pedido->save();
                        \Log::info('[EppController] Novedad de agregar EPP registrada', [
                            'pedidoId' => $pedidoId,
                            'novedad' => $linea_novedad,
                        ]);

                        // Crear notificacion para supervisores
                        try {
                            \App\Models\News::create([
                                'event_type' => 'epp_agregado',
                                'table_name' => 'pedido_epp',
                                'record_id' => $resultado['pedido_epp_id'] ?? 0,
                                'description' => "{$rol} {$nombreUsuario} agrego EPP al Pedido #{$pedido->numero_pedido}: {$validated['novedad']}",
                                'user_id' => $usuario?->id,
                                'pedido' => $pedido->numero_pedido,
                                'metadata' => [
                                    'tipo' => 'epp_agregado',
                                    'pedido_id' => $pedidoId,
                                    'novedad' => $validated['novedad'],
                                ],
                            ]);
                        } catch (\Exception $newsEx) {
                            \Log::warning('[EppController] Error creando News', ['error' => $newsEx->getMessage()]);
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('[EppController] Error guardando novedad al agregar EPP', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Si no hubo texto de novedad, igual crear notificacion de agregado.
            if (empty($validated['novedad'])) {
                try {
                    $pedido = PedidoProduccion::with('asesora:id,name')->find($pedidoId);
                    $actor = $this->resolverActor($request, $pedido);
                    $usuario = $actor['usuario'];
                    if ($pedido && !$this->esPedidoBorrador($pedido)) {
                        \App\Models\News::create([
                            'event_type' => 'epp_agregado',
                            'table_name' => 'pedido_epp',
                            'record_id' => $resultado['pedido_epp_id'] ?? 0,
                            'description' => ($actor['nombre'] . " agrego EPP al Pedido #{$pedido->numero_pedido}"),
                            'user_id' => $usuario?->id,
                            'pedido' => $pedido->numero_pedido,
                            'metadata' => [
                                'tipo' => 'epp_agregado',
                                'pedido_id' => $pedidoId,
                                'novedad' => null,
                            ],
                        ]);
                    }
                } catch (\Exception $newsEx) {
                    \Log::warning('[EppController] Error creando News fallback (agregar)', ['error' => $newsEx->getMessage()]);
                }
            }

            return response()->json($resultado, 201);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('[EppController::agregar] Validacion fallida al agregar EPP', [
                'pedidoId' => $pedidoId,
                'errors' => $e->errors(),
                'input' => $request->except(['imagenes']),
                'has_files' => $request->hasFile('imagenes'),
                'all_keys' => array_keys($request->all()),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Datos ivalidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('[EppController::agregar] Error inesperado al agregar EPP', [
                'pedidoId' => $pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar EPP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/pedidos/{pedidoId}/epp/{eppId}
     * 
     * Eliminar EPP de un pedido
     */
    public function eliminar(int $pedidoId, int $eppId): JsonResponse
    {
        try {
            $command = new EliminarEppDelPedidoCommand($pedidoId, $eppId);
            $resultado = $this->commandBus->execute($command);

            return response()->json($resultado);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar EPP',
            ], 500);
        }
    }

    /**
     * GET /api/pedidos/{pedidoId}/epp/{pedidoEppId}
     * 
     * Obtener un EPP especifico de un pedido con todos sus detalles
     */
    public function obtenerEppDelPedidoPorId(int $pedidoId, int $pedidoEppId): JsonResponse
    {
        try {
            // Obtener la relacion pedido_epp
            $pedidoEpp = \App\Models\PedidoEpp::where('pedido_produccion_id', $pedidoId)
                ->where('id', $pedidoEppId)
                ->with(['epp'])
                ->first();

            if (!$pedidoEpp) {
                return response()->json([
                    'success' => false,
                    'message' => 'EPP no encontrado en este pedido',
                ], 404);
            }

            // Transformar datos
            $epp = $pedidoEpp->epp;
            $datos = [
                'success' => true,
                'data' => [
                    'id' => $pedidoEpp->id,
                    'pedido_epp_id' => $pedidoEpp->id,
                    'epp_id' => $epp->id,
                    'nombre_completo' => $epp->nombre_completo ?? $epp->nombre,
                    'nombre' => $epp->nombre,
                    'epp_nombre' => $epp->nombre,
                    'codigo' => $epp->codigo,
                    'epp_codigo' => $epp->codigo,
                    'categoria' => $epp->categoria,
                    'epp_categoria' => $epp->categoria,
                    'cantidad' => $pedidoEpp->cantidad,
                    'observaciones' => $pedidoEpp->observaciones,
                ],
            ];

            return response()->json($datos);
        } catch (\Exception $e) {
            \Log::error('[EppController] Error al obtener EPP del pedido:', [
                'pedidoId' => $pedidoId,
                'pedidoEppId' => $pedidoEppId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener EPP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PATCH /api/pedidos/{pedidoId}/epp/{pedidoEppId}
     * 
     * Actualizar cantidad y observaciones de un EPP en el pedido
     */
    public function actualizarEppDelPedido(int $pedidoId, int $pedidoEppId, Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'cantidad' => 'sometimes|required|integer|min:1',
                'observaciones' => 'nullable|string',
                'novedad' => 'nullable|string',
                'epp_id' => 'sometimes|required|integer|exists:epp,id',
            ]);

            $pedidoEpp = \App\Models\PedidoEpp::where('id', $pedidoEppId)
                ->where('pedido_produccion_id', $pedidoId)
                ->first();

            if (!$pedidoEpp) {
                return response()->json([
                    'success' => false,
                    'message' => 'EPP no encontrado en este pedido',
                ], 404);
            }

            if (isset($validated['cantidad'])) {
                $pedidoEpp->cantidad = $validated['cantidad'];
            }
            if (isset($validated['observaciones'])) {
                $pedidoEpp->observaciones = $validated['observaciones'];
            }
            if (isset($validated['epp_id'])) {
                $pedidoEpp->epp_id = $validated['epp_id'];
            }

            $pedidoEpp->save();

            $pedido = PedidoProduccion::with('asesora:id,name')->find($pedidoId);
            $actor = $this->resolverActor($request, $pedido);
            $usuario = $actor['usuario'];
            $nombreUsuario = $actor['nombre'];
            $rol = $actor['rol'];
            $nombreEpp = \App\Models\Epp::find($pedidoEpp->epp_id)?->nombre_completo ?? 'Sin nombre';
            $textoNovedad = isset($validated['novedad']) ? trim((string) $validated['novedad']) : '';

            if ($pedido && !$this->esPedidoBorrador($pedido)) {
                if ($textoNovedad !== '') {
                    $fechaFormato = now()->format('d/m/Y h:i A');
                    $lineaNovedad = "{$rol}-{$nombreUsuario}-{$fechaFormato} - Modifico EPP \"{$nombreEpp}\" - {$textoNovedad}";

                    $pedido->novedades = !empty($pedido->novedades)
                        ? $pedido->novedades . "\n\n" . $lineaNovedad
                        : $lineaNovedad;
                    $pedido->save();

                    \Log::info('[EppController] Novedad registrada en pedido', [
                        'pedidoId' => $pedidoId,
                        'novedad' => $lineaNovedad,
                    ]);
                }

                try {
                    \App\Models\News::create([
                        'event_type' => 'epp_modificado',
                        'table_name' => 'pedido_epp',
                        'record_id' => $pedidoEppId,
                        'description' => "{$rol} {$nombreUsuario} modifico EPP \"{$nombreEpp}\" en Pedido #{$pedido->numero_pedido}",
                        'user_id' => $usuario?->id,
                        'pedido' => $pedido->numero_pedido,
                        'metadata' => [
                            'tipo' => 'epp_modificado',
                            'pedido_id' => $pedidoId,
                            'epp_nombre' => $nombreEpp,
                            'novedad' => $textoNovedad !== '' ? $textoNovedad : null,
                        ],
                    ]);
                } catch (\Exception $newsEx) {
                    \Log::warning('[EppController] Error creando News', [
                        'error' => $newsEx->getMessage(),
                        'pedidoId' => $pedidoId,
                        'pedidoEppId' => $pedidoEppId,
                    ]);
                }
            }

            \Log::info('[EppController] EPP actualizado correctamente:', [
                'pedidoId' => $pedidoId,
                'pedidoEppId' => $pedidoEppId,
                'cantidad' => $validated['cantidad'] ?? 'sin cambios',
                'observaciones' => array_key_exists('observaciones', $validated) ? 'actualizado' : 'sin cambios',
                'epp_id' => $validated['epp_id'] ?? 'sin cambios',
                'novedad' => $textoNovedad !== '' ? 'registrada' : 'sin registrar',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'EPP actualizado correctamente',
                'data' => [
                    'id' => $pedidoEpp->id,
                    'cantidad' => $pedidoEpp->cantidad,
                    'observaciones' => $pedidoEpp->observaciones,
                    'epp_id' => $pedidoEpp->epp_id,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos invalidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar EPP: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function subirImagen(int $eppId, Request $request): JsonResponse
    {
        try {
            // Validar EPP existe
            $epp = Epp::findOrFail($eppId);

            // Validar archivo
            $validated = $request->validate([
                'imagen' => 'required|image|max:5120',
                'principal' => 'nullable|boolean',
            ]);

            $archivo = $request->file('imagen');
            $principal = $request->boolean('principal', false);

            // Si es principal, desmarcar otras imagenes como principales
            if ($principal) {
                EppImagen::where('epp_id', $eppId)
                    ->where('principal', true)
                    ->update(['principal' => false]);
            }

            // Crear carpeta si no existe
            $carpeta = "epp/{$epp->codigo}";
            if (!Storage::disk('public')->exists($carpeta)) {
                Storage::disk('public')->makeDirectory($carpeta);
            }

            // Guardar archivo
            $nombreArchivo = time() . '_' . $archivo->getClientOriginalName();
            $ruta = $archivo->storeAs($carpeta, $nombreArchivo, 'public');

            // Obtener próximo orden
            $proximoOrden = EppImagen::where('epp_id', $eppId)->max('orden') ?? 0;
            $proximoOrden++;

            // Crear registro en BD
            $imagen = EppImagen::create([
                'epp_id' => $eppId,
                'archivo' => $nombreArchivo,
                'principal' => $principal,
                'orden' => $proximoOrden,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Imagen subida correctamente',
                'data' => [
                    'id' => $imagen->id,
                    'archivo' => $imagen->archivo,
                    'principal' => $imagen->principal,
                    'url' => "/storage/{$ruta}",
                ],
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'EPP no encontrado',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos ivalidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir imagen: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/epp/imagenes/{imagenId}
     * 
     * Eliminar imagen de PedidoEpp (tabla pedido_epp_imagenes)
     * IGNORADO: tabla epp_imagenes no existe, solo usar pedido_epp_imagenes
     */
    public function eliminarImagen(int $imagenId): JsonResponse
    {
        try {
            // Solo eliminar imagen de PedidoEpp
            $imagenPedido = \DB::table('pedido_epp_imagenes')->where('id', $imagenId)->first();
            
            if ($imagenPedido) {
                // Eliminar archivo del servidor
                if ($imagenPedido->ruta_web) {
                    $rutaArchivo = str_replace('/storage/', '', $imagenPedido->ruta_web);
                    Storage::disk('public')->delete($rutaArchivo);
                }
                
                // Eliminar registro de la base de datos
                \DB::table('pedido_epp_imagenes')->where('id', $imagenId)->delete();
                
                \Log::info(' [EppController] Imagen de PedidoEpp eliminada', [
                    'imagen_id' => $imagenId,
                    'ruta' => $imagenPedido->ruta_web
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Imagen eliminada correctamente',
                ]);
            }
            
            //  Tabla epp_imagenes no existe, no intentar cargar
            \Log::warning(' [EppController] Imagen no encontrada en pedido_epp_imagenes', [
                'imagen_id' => $imagenId,
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Imagen no encontrada',
            ], 404);
            
        } catch (\Exception $e) {
            \Log::error(' [EppController] Error eliminando imagen', [
                'imagen_id' => $imagenId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar imagen: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Subir imagen de EPP durante creación del pedido
     * POST /api/epp/imagenes/upload
     * 
     * DEPRECADO: Las imagenes se envían directamente con FormData al crear el pedido
     * No se suben por separado, se procesan junto con epps[] en crearPedido()
     */
    public function subirImagenEpp(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Este endpoint no debe usarse. Las imagenes se envían con FormData al crear el pedido.',
        ], 400);
    }

    /**
     * GET /api/epps/buscar
     * 
     * Buscar EPPs por término (nombre_completo)
     * Query parameters:
     * - q: término de búsqueda
     */
    public function buscar(Request $request): JsonResponse
    {
        try {
            $termino = $request->query('q', '');
            
            if (strlen($termino) < 2) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'Termino muy corto'
                ]);
            }
            
            // Buscar solo por nombre_completo (único campo disponible)
            $epps = Epp::query()
                ->where('activo', true)
                ->where('nombre_completo', 'LIKE', "%{$termino}%")
                ->select('id', 'nombre_completo')
                ->limit(20)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $epps->map(function ($epp) {
                    return [
                        'id' => $epp->id,
                        'nombre_completo' => $epp->nombre_completo,
                    ];
                })->toArray()
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error buscando EPPs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar EPPs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /api/epp/{id}
     * 
     * Actualizar EPP existente
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $epp = Epp::findOrFail($id);

            // Verificar si el EPP esta asociado a pedidos
            $asociacionesPedidos = \App\Models\PedidoEpp::where('epp_id', $id)->count();
            
            if ($asociacionesPedidos > 0) {
                $mensaje = "No se puede editar el EPP porque esta asociado a {$asociacionesPedidos} pedido(s).";
                
                return response()->json([
                    'success' => false,
                    'message' => $mensaje,
                    'tiene_asociaciones' => true,
                    'asociaciones' => [
                        'pedidos' => $asociacionesPedidos
                    ]
                ], 400);
            }

            $validated = $request->validate([
                'nombre_completo' => 'required|string|max:500',
                'marca' => 'nullable|string|max:100',
                'tipo' => 'required|in:PRODUCTO,SERVICIO',
                'talla' => 'nullable|string|max:100',
                'color' => 'nullable|string|max:100',
                'categoria_id' => 'nullable|integer|exists:epp_categorias,id',
                'descripcion' => 'nullable|string',
                'activo' => 'required|boolean',
            ]);

            $epp->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'EPP actualizado exitosamente',
                'data' => $epp,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'EPP no encontrado',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos ivalidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('[EppController] Error actualizando EPP:', [
                'id' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar EPP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/epp/{id}
     * 
     * Eliminar EPP (soft delete)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $epp = Epp::findOrFail($id);
            
            // Verificar si el EPP esta asociado a pedidos
            $asociacionesPedidos = \App\Models\PedidoEpp::where('epp_id', $id)->count();
            
            if ($asociacionesPedidos > 0) {
                $mensaje = "No se puede eliminar el EPP porque esta asociado a {$asociacionesPedidos} pedido(s).";
                
                return response()->json([
                    'success' => false,
                    'message' => $mensaje,
                    'tiene_asociaciones' => true,
                    'asociaciones' => [
                        'pedidos' => $asociacionesPedidos
                    ]
                ], 400);
            }
            
            // Soft delete
            $epp->delete();

            return response()->json([
                'success' => true,
                'message' => 'EPP eliminado exitosamente',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'EPP no encontrado',
            ], 404);
        } catch (\Exception $e) {
            \Log::error('[EppController] Error eliminando EPP:', [
                'id' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar EPP: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/epp/{id}/actualizar
     * 
     * Endpoint alternativo para actualizar (compatibilidad con frontend)
     */
    public function actualizarDirecto(Request $request, int $id): JsonResponse
    {
        return $this->update($request, $id);
    }

    /**
     * POST /api/epp/{id}/eliminar
     * 
     * Endpoint alternativo para eliminar (compatibilidad con frontend)
     */
    public function eliminarDirecto(int $id): JsonResponse
    {
        return $this->destroy($id);
    }

    /**
     * GET /epp
     * 
     * Vista de gestión completa de EPPs
     */
    public function vistaGestion()
    {
        try {
            \Log::info('[EppController] Accediendo a vistaGestion SIN middleware');
            
            // Probar a cargar datos para verificar que funciona
            $epps = \App\Models\Epp::limit(5)->get();
            \Log::info('[EppController] EPPs cargados:', ['count' => $epps->count()]);
            
            return view('epp.index');
        } catch (\Exception $e) {
            \Log::error('[EppController] Error en vistaGestion:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Devolver vista simple con mensaje de error
            return view('epp.index')->with('error', 'Error cargando la vista: ' . $e->getMessage());
        }
    }

    /**
     * GET /epp/test
     * 
     * metodo de prueba para depuración
     */
    public function test()
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'EPP Controller funciona correctamente',
                'epp_count' => \App\Models\Epp::count(),
                'epp_sample' => \App\Models\Epp::limit(3)->get()->toArray(),
                'routes' => [
                    'epp' => route('epp.inicio'),
                    'epp_test' => route('epp.test')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * GET /api/pedido-epp/{pedidoEppId}/imagenes
     * 
     * Obtener todas las imagenes de un EPP especifico en un pedido
     */
    public function obtenerImagenes($pedidoEppId): JsonResponse
    {
        try {
            \Log::info("[EppController] Obteniendo imagenes del pedido EPP: {$pedidoEppId}");
            
            // Buscar el pedido_epp
            $pedidoEpp = \App\Models\PedidoEpp::find($pedidoEppId);
            
            if (!$pedidoEpp) {
                \Log::warning("[EppController] Pedido EPP no encontrado: {$pedidoEppId}");
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido EPP no encontrado',
                    'data' => []
                ], 404);
            }
            
            // Obtener imagenes del pedido_epp
            $imagenes = \App\Models\PedidoEppImagen::where('pedido_epp_id', $pedidoEppId)
                ->orderBy('orden')
                ->get();
            
            \Log::info("[EppController] imagenes encontradas: {$imagenes->count()}");
            
            // Formatear las imagenes para el frontend
            $imagenesFormateadas = $imagenes->map(function ($imagen) {
                // Asegurar que ruta_web siempre incluya /storage/
                $rutaWeb = $imagen->ruta_web;
                if ($rutaWeb && !str_starts_with($rutaWeb, '/storage/')) {
                    $rutaWeb = '/storage/' . ltrim($rutaWeb, '/');
                }
                
                return [
                    'id' => $imagen->id,
                    'pedido_epp_id' => $imagen->pedido_epp_id,
                    'ruta_original' => $imagen->ruta_original,
                    'ruta_web' => $rutaWeb,
                    'principal' => $imagen->principal,
                    'orden' => $imagen->orden,
                    'nombre' => basename($imagen->ruta_original),
                    'created_at' => $imagen->created_at,
                    'updated_at' => $imagen->updated_at
                ];
            });
            
            return response()->json([
                'success' => true,
                'message' => 'imagenes obtenidas correctamente',
                'data' => $imagenesFormateadas
            ]);
            
        } catch (\Exception $e) {
            \Log::error("[EppController] Error obteniendo imagenes del pedido EPP: {$pedidoEppId}", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las imagenes: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * POST /api/pedido-epp/{pedidoEppId}/imagenes
     * 
     * Agregar nuevas imagenes a un EPP especifico en un pedido
     */
    public function agregarImagenes(Request $request, $pedidoEppId): JsonResponse
    {
        try {
            \Log::info("[EppController] Agregando imagenes al pedido EPP: {$pedidoEppId}");
            
            // Debug completo del request
            \Log::info("[EppController] Request data:", [
                'has_files' => $request->hasFile('imagenes'),
                'all_files' => $request->allFiles(),
                'request_method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'request_input' => $request->all()
            ]);
            
            // Validar que el pedido EPP exista
            $pedidoEpp = \App\Models\PedidoEpp::find($pedidoEppId);
            
            if (!$pedidoEpp) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido EPP no encontrado'
                ], 404);
            }
            
            // Debug del pedidoEpp y su relacion
            \Log::info("[EppController] PedidoEPP encontrado:", [
                'pedido_epp_id' => $pedidoEpp->id,
                'pedido_produccion_id' => $pedidoEpp->pedido_produccion_id,
                'epp_id' => $pedidoEpp->epp_id,
                'cantidad' => $pedidoEpp->cantidad,
                'pedido_relationship' => $pedidoEpp->pedidoProduccion ? [
                    'id' => $pedidoEpp->pedidoProduccion->id,
                    'numero_pedido' => $pedidoEpp->pedidoProduccion->numero_pedido
                ] : 'null'
            ]);
            
            // Determinar la ruta de guardado usando el ID del pedido correcto
            $pedidoId = $pedidoEpp->pedido_produccion_id;
            \Log::info("[EppController] Guardando imagen para pedido ID: {$pedidoId}");
            
            // Si no hay pedido_produccion_id, intentar obtenerlo de la relacion
            if (!$pedidoId && $pedidoEpp->pedidoProduccion) {
                $pedidoId = $pedidoEpp->pedidoProduccion->id;
                \Log::info("[EppController] Pedido ID obtenido de relacion: {$pedidoId}");
            }
            
            if (!$pedidoId) {
                \Log::error("[EppController] No se pudo determinar el ID del pedido para el EPP {$pedidoEppId}");
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo determinar el pedido asociado al EPP'
                ], 400);
            }
            
            // Validar que se hayan enviado archivos - intentar diferentes nombres
            $imagenes = null;
            if ($request->hasFile('imagenes')) {
                $imagenes = $request->file('imagenes');
                \Log::info("[EppController] Archivos recibidos con 'imagenes': " . (is_array($imagenes) ? count($imagenes) : 1));
            } elseif ($request->hasFile('imagenes[]')) {
                $imagenes = $request->file('imagenes[]');
                \Log::info("[EppController] Archivos recibidos con 'imagenes[]': " . (is_array($imagenes) ? count($imagenes) : 1));
            } else {
                \Log::warning("[EppController] No se encontraron archivos en el request");
                \Log::info("[EppController] Keys en request:", array_keys($request->all()));
                \Log::info("[EppController] Files en request:", array_keys($request->allFiles()));
            }
            
            if (!$imagenes || (is_array($imagenes) && empty($imagenes))) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se han enviado imagenes',
                    'debug' => [
                        'has_files' => $request->hasFile('imagenes'),
                        'has_files_array' => $request->hasFile('imagenes[]'),
                        'all_files_keys' => array_keys($request->allFiles()),
                        'request_keys' => array_keys($request->all())
                    ]
                ], 400);
            }
            
            // Asegurar que $imagenes sea un array
            if (!is_array($imagenes)) {
                $imagenes = [$imagenes];
            }
            
            $imagenesGuardadas = [];
            
            foreach ($imagenes as $index => $imagen) {
                if ($imagen->isValid()) {
                    $nombreOriginal = $imagen->getClientOriginalName();

                    // Obtener el siguiente orden
                    $ultimoOrden = \App\Models\PedidoEppImagen::where('pedido_epp_id', $pedidoEppId)
                        ->max('orden') ?? 0;
                    $orden = $ultimoOrden + 1;

                    // Estandarizar destino: pedidos/{pedidoId}/epp/epp_{epp_id}_img_{n}.webp
                    $rutaStorage = "pedidos/{$pedidoId}/epp";
                    if (!\Storage::disk('public')->exists($rutaStorage)) {
                        \Storage::disk('public')->makeDirectory($rutaStorage);
                    }

                    $nombreArchivo = "epp_{$pedidoEpp->epp_id}_img_" . ($orden - 1) . '.webp';
                    $rutaCompleta = "{$rutaStorage}/{$nombreArchivo}";
                    $rutaWebRelativa = $rutaCompleta;
                    \Log::info("[EppController] Rutas generadas:", [
                        'pedido_id' => $pedidoId,
                        'ruta_storage' => $rutaStorage,
                        'ruta_completa' => $rutaCompleta,
                        'ruta_web' => $rutaWebRelativa
                    ]);

                    // Guardar el archivo (convertido a WebP)
                    try {
                        $imageManager = \Intervention\Image\ImageManager::gd();
                        $imagenObj = $imageManager->read($imagen->getRealPath());
                    } catch (\Exception $e) {
                        try {
                            $imageManager = \Intervention\Image\ImageManager::imagick();
                            $imagenObj = $imageManager->read($imagen->getRealPath());
                        } catch (\Exception $e2) {
                            \Log::error('[EppController] Error leyendo imagen para convertir a WebP', [
                                'pedido_epp_id' => $pedidoEppId,
                                'error' => $e2->getMessage(),
                            ]);
                            continue;
                        }
                    }

                    if ($imagenObj->width() > 2000 || $imagenObj->height() > 2000) {
                        $imagenObj->scaleDown(width: 2000, height: 2000);
                    }

                    $webp = $imagenObj->toWebp(quality: 80);
                    $contenidoWebP = $webp->toString();
                    \Storage::disk('public')->put($rutaCompleta, $contenidoWebP);
                    // Verificar que el archivo se guardo correctamente
                    $rutaArchivoCompleta = storage_path("app/public/{$rutaCompleta}");
                    if (file_exists($rutaArchivoCompleta)) {
                        \Log::info("[EppController] Archivo guardado exitosamente: {$rutaArchivoCompleta}");
                    } else {
                        \Log::error("[EppController] Error: Archivo no se guardo: {$rutaArchivoCompleta}");
                    }

                    $tienePrincipal = \App\Models\PedidoEppImagen::where('pedido_epp_id', $pedidoEppId)
                        ->where('principal', 1)
                        ->exists();

                    // Guardar en la base de datos
                    $pedidoEppImagen = \App\Models\PedidoEppImagen::create([
                        'pedido_epp_id' => $pedidoEppId,
                        'ruta_original' => $rutaCompleta,
                        'ruta_web' => $rutaWebRelativa,
                        'principal' => $tienePrincipal ? 0 : 1,
                        'orden' => $orden
                    ]);
                    $imagenesGuardadas[] = [
                        'id' => $pedidoEppImagen->id,
                        'ruta_original' => $rutaCompleta,
                        'ruta_web' => '/storage/' . ltrim($rutaWebRelativa, '/'),
                        'principal' => (int)$pedidoEppImagen->principal,
                        'orden' => (int)$pedidoEppImagen->orden,
                        'nombre' => $nombreOriginal
                    ];
                    \Log::info("[EppController] Imagen guardada: {$nombreArchivo}");
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => count($imagenesGuardadas) . ' imagenes agregadas correctamente',
                'data' => $imagenesGuardadas
            ]);
            
        } catch (\Exception $e) {
            \Log::error("[EppController] Error agregando imagenes al pedido EPP: {$pedidoEppId}", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar las imagenes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/pedido-epp/imagenes/{imagenId}
     * 
     * Eliminar una imagen específica de un EPP en un pedido
     */
    public function eliminarImagenPedidoEpp($imagenId): JsonResponse
    {
        try {
            \Log::info("[EppController] Eliminando imagen de pedido EPP: {$imagenId}");
            
            // Buscar la imagen
            $imagen = \App\Models\PedidoEppImagen::find($imagenId);
            
            if (!$imagen) {
                return response()->json([
                    'success' => false,
                    'message' => 'Imagen no encontrada'
                ], 404);
            }
            
            // Eliminar el archivo físico
            $rutaArchivo = $imagen->ruta_web;
            if (is_string($rutaArchivo) && str_starts_with($rutaArchivo, '/storage/')) {
                $rutaArchivo = substr($rutaArchivo, strlen('/storage/'));
            }
            $rutaArchivo = ltrim((string)$rutaArchivo, '/');

            if (Storage::disk('public')->exists($rutaArchivo)) {
                Storage::disk('public')->delete($rutaArchivo);
                \Log::info("[EppController] Archivo físico eliminado: {$rutaArchivo}");
            }
            
            // Eliminar el registro de la base de datos
            $imagen->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Imagen eliminada correctamente'
            ]);
            
        } catch (\Exception $e) {
            \Log::error("[EppController] Error eliminando imagen de pedido EPP: {$imagenId}", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la imagen: ' . $e->getMessage()
            ], 500);
        }
    }

    private function resolverActor(Request $request, ?PedidoProduccion $pedido = null): array
    {
        $usuario = $request->user();
        if (!$usuario && auth('web')->check()) {
            $usuario = auth('web')->user();
        }
        if (!$usuario && auth()->check()) {
            $usuario = auth()->user();
        }
        if (!$usuario && \Illuminate\Support\Facades\Auth::user()) {
            $usuario = \Illuminate\Support\Facades\Auth::user();
        }

        if ($usuario) {
            $rol = 'Usuario';
            if (method_exists($usuario, 'getRoleNames')) {
                $roles = $usuario->getRoleNames();
                if ($roles && count($roles) > 0) {
                    $rol = ucfirst((string) $roles[0]);
                }
            } elseif (method_exists($usuario, 'roles')) {
                $primerRol = $usuario->roles()->first();
                if ($primerRol) {
                    $rol = ucfirst((string) ($primerRol->name ?? 'Usuario'));
                }
            }

            return [
                'usuario' => $usuario,
                'nombre' => (string) ($usuario->name ?? $usuario->email ?? 'Sistema'),
                'rol' => $rol,
            ];
        }

        $nombreAsesora = $pedido?->asesora?->name;
        if (!empty($nombreAsesora)) {
            return [
                'usuario' => null,
                'nombre' => (string) $nombreAsesora,
                'rol' => 'Asesor',
            ];
        }

        return [
            'usuario' => null,
            'nombre' => 'Sistema',
            'rol' => 'Sistema',
        ];
    }

    private function esPedidoBorrador(PedidoProduccion $pedido): bool
    {
        if ($pedido->numero_pedido === null) {
            return true;
        }

        return strtolower((string) $pedido->estado) === 'borrador';
    }
}
