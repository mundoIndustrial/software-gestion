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
use Illuminate\Support\Facades\Storage;

/**
 * Controller: EppController
 * 
 * Infrastructure Layer - Maneja requests HTTP y dispara CQRS
 * Cumple: Separación de responsabilidades, DDD
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
     * - q: término de búsqueda (código o nombre)
     * - categoria: filtrar por categoría
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $termino = $request->query('q');
            $categoria = $request->query('categoria');

            // Debug: Log de entrada
            \Log::info('[EppController] Búsqueda iniciada', [
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

            \Log::info('[EppController] Búsqueda completada', [
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
     * POST /api/epp
     * 
     * Crear nuevo EPP
     * Solo requiere: nombre y descripción
     * Los campos adicionales (categoría, cantidad, observaciones, imágenes) se agregan después en la edición
     */
    public function store(Request $request): JsonResponse
    {
        try {
            \Log::info('[EppController] === INICIANDO CREACIÓN DE EPP ===');
            \Log::info('[EppController] Request data:', $request->all());
            
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'categoria' => 'nullable|string|max:255',
            ]);

            \Log::info('[EppController] Validación exitosa:', $validated);

            $command = new CrearEppCommand(
                nombre: $validated['nombre'],
                categoria: $validated['categoria'] ?? 'General',
                codigo: null,
                descripcion: null
            );

            \Log::info('[EppController] Comando creado:', [
                'command' => class_basename($command),
                'nombre' => $validated['nombre'],
                'categoria' => $validated['categoria'] ?? 'General',
            ]);

            $epp = $this->commandBus->execute($command);

            \Log::info('[EppController] EPP creado exitosamente:', ['epp' => $epp]);

            return response()->json([
                'success' => true,
                'message' => 'EPP creado exitosamente',
                'data' => $epp,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('[EppController] ❌ Validation error:', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('[EppController] ❌ Error creating EPP', [
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
     * Obtener todas las categorías
     */
    public function crearEppSimple(Request $request): JsonResponse
    {
        try {
            \Log::info('[EppController::crearEppSimple] Iniciando creación de EPP simplificada');
            \Log::info('[EppController::crearEppSimple] Request completa:', $request->all());
            
            // Validación simple
            $validated = $request->validate([
                'nombre_completo' => 'required|string|max:500',
            ]);

            \Log::info('[EppController::crearEppSimple] Datos validados:', $validated);

            // Obtener otros parámetros opcionales
            $categoria_id = $request->input('categoria_id', 19);
            $tipo = $request->input('tipo', 'PRODUCTO');
            $activo = filter_var($request->input('activo', true), FILTER_VALIDATE_BOOLEAN);

            \Log::info('[EppController::crearEppSimple] Parámetros opcionales:', [
                'categoria_id' => $categoria_id,
                'tipo' => $tipo,
                'activo' => $activo
            ]);

            // Buscar si ya existe un EPP con el mismo nombre
            $eppExistente = \App\Models\Epp::where('nombre_completo', $validated['nombre_completo'])
                ->first();

            if ($eppExistente) {
                \Log::info('[EppController::crearEppSimple] EPP ya existe:', $eppExistente->toArray());
                
                return response()->json([
                    'success' => true,
                    'message' => 'EPP ya existe',
                    'data' => $eppExistente,
                    'epp' => $eppExistente,
                    'existia' => true,
                ], 200);
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
                'existia' => false,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('[EppController::crearEppSimple] Validation error:', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
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
     * Obtener todas las categorías
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
                'message' => 'Error al obtener categorías',
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
     * Agregar EPP a un pedido con imágenes opcionales
     */
    public function agregar(int $pedidoId, Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'epp_id' => 'required|integer|exists:epps,id',
                'cantidad' => 'required|integer|min:1',
                'observaciones' => 'nullable|string|max:1000',
                'imagenes' => 'nullable|array|max:5',
                'imagenes.*' => 'nullable|string',
            ]);

            // Procesar imágenes si existen
            $imagenes = [];
            if ($request->hasFile('imagenes')) {
                foreach ($request->file('imagenes') as $imagen) {
                    if ($imagen->isValid()) {
                        // Guardar imagen en directorio específico del pedido
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

            return response()->json($resultado, 201);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar EPP',
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
     * Obtener un EPP específico de un pedido con todos sus detalles
     */
    public function obtenerEppDelPedidoPorId(int $pedidoId, int $pedidoEppId): JsonResponse
    {
        try {
            // Obtener la relación pedido_epp
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
                'cantidad' => 'nullable|integer|min:1',
                'observaciones' => 'nullable|string|max:1000',
                'epp_id' => 'nullable|integer|exists:epps,id',
                'novedad' => 'nullable|string|max:2000',
            ]);

            // Obtener la relación pedido_epp
            $pedidoEpp = \App\Models\PedidoEpp::where('pedido_produccion_id', $pedidoId)
                ->where('id', $pedidoEppId)
                ->first();

            if (!$pedidoEpp) {
                return response()->json([
                    'success' => false,
                    'message' => 'EPP no encontrado en este pedido',
                ], 404);
            }

            // Actualizar solo los campos proporcionados
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

            // Guardar la novedad si se proporciona
            if (isset($validated['novedad']) && !empty($validated['novedad'])) {
                try {
                    $usuario = null;
                    
                    // Intenta obtener usuario de múltiples formas
                    // 1. Usar Auth::user() que debería estar disponible en la sesión
                    if (auth('web')->check()) {
                        $usuario = auth('web')->user();
                    } elseif (auth()->check()) {
                        $usuario = auth()->user();
                    } elseif (\Illuminate\Support\Facades\Auth::user()) {
                        // Fallback a Auth facade
                        $usuario = \Illuminate\Support\Facades\Auth::user();
                    }
                    
                    $nombreUsuario = 'Sistema';
                    $rol = 'Usuario';
                    
                    if ($usuario) {
                        // Obtener nombre
                        $nombreUsuario = strtoupper($usuario->name ?? $usuario->email ?? 'Sistema');
                        
                        \Log::info('[EppController] Usuario encontrado:', [
                            'id' => $usuario->id,
                            'name' => $usuario->name,
                            'email' => $usuario->email,
                        ]);
                        
                        // Intentar obtener el rol de diferentes formas
                        if (method_exists($usuario, 'getRoleNames')) {
                            $roles = $usuario->getRoleNames();
                            if ($roles && count($roles) > 0) {
                                $rol = strtoupper($roles[0]);
                            }
                        } elseif (method_exists($usuario, 'roles')) {
                            $usuarioRoles = $usuario->roles();
                            if ($usuarioRoles) {
                                $primerRol = $usuarioRoles->first();
                                if ($primerRol) {
                                    $rol = strtoupper($primerRol->name ?? 'Usuario');
                                }
                            }
                        } elseif (property_exists($usuario, 'rol')) {
                            $rol = strtoupper($usuario->rol ?? 'Usuario');
                        } elseif (property_exists($usuario, 'role')) {
                            $rol = strtoupper($usuario->role ?? 'Usuario');
                        }
                    }

                    // Formato: NOMBRE-ROL-dd/mm/yyyy-h:mmAM/PM
                    //         CAMBIO EPP: DESCRIPCIÓN
                    $fechaActual = now();
                    $fechaFormato = $fechaActual->format('d/m/Y') . '-' . $fechaActual->format('g:iA');
                    $linea_novedad = "{$nombreUsuario}-{$rol}-{$fechaFormato}\nCAMBIO EPP: {$validated['novedad']}";
                    
                    \Log::info('[EppController] Datos de usuario para novedad:', [
                        'nombreUsuario' => $nombreUsuario,
                        'rol' => $rol,
                        'fechaFormato' => $fechaFormato,
                        'linea_novedad' => $linea_novedad,
                    ]);
                    
                    // Obtener el pedido de producción
                    $pedido = \App\Models\PedidoProduccion::find($pedidoId);
                    
                    if ($pedido) {
                        // Agregar la novedad al campo existente con separador visual
                       if (!empty($pedido->novedades)) {
                            // Solo salto de línea entre novedades
                            $pedido->novedades .= "\n\n" . $linea_novedad;
                        } else {
                            $pedido->novedades = $linea_novedad;
                        }

                        $pedido->save();
                        
                        \Log::info('[EppController] Novedad registrada en pedido:', [
                            'pedidoId' => $pedidoId,
                            'novedad' => $linea_novedad,
                        ]);
                    } else {
                        \Log::warning('[EppController] Pedido no encontrado para guardar novedad:', [
                            'pedidoId' => $pedidoId,
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::warning('[EppController] Error guardando novedad (continuando):', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    // No fallar el cambio si hay error guardando la novedad
                }
            }

            \Log::info('[EppController] EPP actualizado correctamente:', [
                'pedidoId' => $pedidoId,
                'pedidoEppId' => $pedidoEppId,
                'cantidad' => $validated['cantidad'] ?? 'sin cambios',
                'observaciones' => $validated['observaciones'] ? 'actualizado' : 'sin cambios',
                'epp_id' => $validated['epp_id'] ?? 'sin cambios',
                'novedad' => isset($validated['novedad']) ? 'registrada' : 'sin registrar',
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
                'message' => 'Datos inválidos',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('[EppController] Error al actualizar EPP del pedido:', [
                'pedidoId' => $pedidoId,
                'pedidoEppId' => $pedidoEppId,
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
     * POST /api/epp/{eppId}/imagenes
     * 
     * Subir imagen a un EPP
     */
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

            // Si es principal, desmarcar otras imágenes como principales
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
                'message' => 'Datos inválidos',
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
                
                \Log::info('✅ [EppController] Imagen de PedidoEpp eliminada', [
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
     * DEPRECADO: Las imágenes se envían directamente con FormData al crear el pedido
     * No se suben por separado, se procesan junto con epps[] en crearPedido()
     */
    public function subirImagenEpp(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Este endpoint no debe usarse. Las imágenes se envían con FormData al crear el pedido.',
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
                    'message' => 'Término muy corto'
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
}
