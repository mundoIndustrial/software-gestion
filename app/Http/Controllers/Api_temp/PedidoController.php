<?php

namespace App\Http\Controllers\Api_temp;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Application\Pedidos\UseCases\CrearPedidoUseCase;
use App\Application\Pedidos\UseCases\ConfirmarPedidoUseCase;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use App\Application\Pedidos\UseCases\ListarPedidosPorClienteUseCase;
use App\Application\Pedidos\UseCases\CancelarPedidoUseCase;
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
        private ObtenerPedidoUseCase $obtenerPedidoUseCase,
        private ListarPedidosPorClienteUseCase $listarPedidosPorClienteUseCase,
        private CancelarPedidoUseCase $cancelarPedidoUseCase,
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
     * DELETE /api/pedidos/{id}/cancelar
     * 
     * Cancelar un pedido
     */
    public function cancelar(int $id): JsonResponse
    {
        try {
            $response = $this->cancelarPedidoUseCase->ejecutar($id);

            return response()->json([
                'success' => true,
                'message' => 'Pedido cancelado exitosamente',
                'data' => $response->toArray()
            ], 200);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PATCH /api/pedidos/{id}/actualizar-descripcion
     * 
     * Actualizar descripción de un pedido con justificación
     */
    public function actualizarDescripcion(Request $request, int $id): JsonResponse
    {
        try {
            \Log::info('[actualizarDescripcion] Iniciando', [
                'pedido_id' => $id,
                'metodo' => $request->method(),
                'ruta' => $request->path(),
            ]);
            
            $request->validate([
                'descripcion' => 'nullable|string|max:2000',
                'cliente' => 'nullable|string|max:500',
                'forma_de_pago' => 'nullable|string|max:500',
                'novedades' => 'nullable|string|max:2000',
                'justificacion' => 'nullable|string|max:1000'
            ]);

            // Obtener directamente del modelo (no usar repository que podría cachear)
            $pedido = \App\Models\PedidoProduccion::findOrFail($id);
            
            // Actualizar cliente si viene
            if ($request->has('cliente') && !is_null($request->input('cliente'))) {
                $pedido->cliente = $request->input('cliente');
            }
            
            // Actualizar forma_de_pago si viene
            if ($request->has('forma_de_pago') && !is_null($request->input('forma_de_pago'))) {
                $pedido->forma_de_pago = $request->input('forma_de_pago');
            }
            
            // Actualizar novedades - PRIMERO
            if ($request->has('novedades') && !is_null($request->input('novedades'))) {
                $pedido->novedades = $request->input('novedades');
            }
            
            // DESPUÉS agregar la justificación a novedades existentes
            if ($request->has('justificacion') && !is_null($request->input('justificacion')) && !empty($request->input('justificacion'))) {
                $justificacion = $request->input('justificacion');
                $novedadesActuales = $pedido->novedades ?: '';
                
                // Obtener información del usuario
                $usuario = auth()->user();
                
                \Log::info('[actualizarDescripcion] Usuario autenticado:', [
                    'usuario' => $usuario ? $usuario->toArray() : null,
                    'auth_check' => auth()->check(),
                    'usuario_id' => auth()->id(),
                ]);
                
                $nombreUsuario = 'Sistema';
                $rolUsuario = 'Sin rol';
                
                if ($usuario) {
                    $nombreUsuario = $usuario->name ?: 'Usuario';
                    \Log::info('[actualizarDescripcion] Nombre del usuario:', ['nombre' => $nombreUsuario]);
                    
                    // Obtener el rol principal
                    $rolesUsuario = $usuario->roles();
                    \Log::info('[actualizarDescripcion] Roles del usuario:', [
                        'roles_ids' => $usuario->roles_ids,
                        'roles_count' => $rolesUsuario->count(),
                        'roles_data' => $rolesUsuario->get()->toArray(),
                    ]);
                    
                    if ($rolesUsuario && $rolesUsuario->count() > 0) {
                        $rolUsuario = $rolesUsuario->first()->name ?? 'Sin rol';
                    }
                }
                
                \Log::info('[actualizarDescripcion] Registro de novedad:', [
                    'usuario_final' => $nombreUsuario,
                    'rol_final' => $rolUsuario,
                ]);
                
                $fechaActual = now()->format('d/m/Y H:i');
                
                // Construir registro con información completa
                $registroNovedad = "[{$nombreUsuario} - {$rolUsuario} - {$fechaActual}]\n{$justificacion}";
                
                // Si ya hay novedades, agregar con separador
                if (!empty($novedadesActuales)) {
                    $pedido->novedades = $novedadesActuales . "\n\n" . $registroNovedad;
                } else {
                    $pedido->novedades = $registroNovedad;
                }
            }

            // Guardar directamente en BD
            $pedido->save();

            return response()->json([
                'success' => true,
                'message' => 'Cambios guardados exitosamente',
                'data' => $pedido->toArray()
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);

        } catch (\Exception $e) {
            \Log::error('[actualizarDescripcion] Error:', [
                'pedido_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar cambios: ' . $e->getMessage()
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
            $response = $this->obtenerPedidoUseCase->ejecutar($id);
            
            // Transformar datos a array
            $datos = $response->toArray();
            
            // Agregar fecha de creación si no existe
            if (!isset($datos['fecha_creacion'])) {
                $pedido = \App\Models\PedidoProduccion::find($id);
                if ($pedido) {
                    $fechaCreacion = $pedido->fecha_de_creacion_de_orden ?? $pedido->created_at;
                    $datos['fecha_creacion'] = $fechaCreacion 
                        ? (is_string($fechaCreacion) ? $fechaCreacion : $fechaCreacion->format('d/m/Y'))
                        : date('d/m/Y');
                }
            }
            
            // Agregar EPPs transformados con imágenes
            $eppsList = [];
            try {
                $pedido = \App\Models\PedidoProduccion::find($id);
                \Log::info('[PedidoController::show] Buscando EPPs', [
                    'pedido_id' => $id,
                    'tiene_epps' => $pedido && $pedido->epps ? $pedido->epps->count() : 0,
                ]);
                
                if ($pedido && $pedido->epps) {
                    foreach ($pedido->epps as $pedidoEpp) {
                        $epp = $pedidoEpp->epp;
                        
                        if (!$epp) {
                            \Log::warning('[PedidoController::show] EPP sin relación válida', [
                                'pedido_epp_id' => $pedidoEpp->id,
                            ]);
                            continue;
                        }
                        
                        // Obtener imágenes del EPP
                        $imagenes = [];
                        try {
                            $imagenesData = \DB::table('pedido_epp_imagenes')
                                ->where('pedido_epp_id', $pedidoEpp->id)
                                ->orderBy('orden', 'asc')
                                ->get(['ruta_web', 'ruta_original', 'principal', 'orden']);
                            
                            \Log::info('[PedidoController::show] Buscando imágenes de EPP', [
                                'pedido_epp_id' => $pedidoEpp->id,
                                'imagenes_encontradas' => $imagenesData->count(),
                            ]);
                            
                            if ($imagenesData->count() > 0) {
                                foreach ($imagenesData as $img) {
                                    $ruta = $img->ruta_web ?? $img->ruta_original;
                                    
                                    // Saltar si la ruta está vacía
                                    if (empty($ruta)) {
                                        \Log::warning('[PedidoController::show] Imagen sin ruta', [
                                            'pedido_epp_id' => $pedidoEpp->id,
                                        ]);
                                        continue;
                                    }
                                    
                                    \Log::debug('[PedidoController::show] Procesando imagen', [
                                        'ruta_original' => $ruta,
                                    ]);
                                    
                                    // Normalizar ruta
                                    if (!str_starts_with($ruta, '/storage/')) {
                                        if (str_starts_with($ruta, 'storage/')) {
                                            $ruta = '/' . $ruta;
                                        } else {
                                            $ruta = '/storage/' . $ruta;
                                        }
                                    }
                                    
                                    $imagenes[] = [
                                        'ruta_webp' => $ruta,
                                        'ruta_original' => $ruta,
                                        'ruta_web' => $ruta,
                                        'principal' => $img->principal ?? false,
                                        'orden' => $img->orden ?? 0,
                                    ];
                                }
                            }
                        } catch (\Exception $e) {
                            \Log::error('[PedidoController::show] Error obtener imágenes de EPP', [
                                'pedido_epp_id' => $pedidoEpp->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                        
                        $eppsList[] = [
                            'id' => $pedidoEpp->id,
                            'epp_id' => $pedidoEpp->epp_id,
                            'nombre' => $epp->nombre_completo ?? $epp->nombre ?? '',
                            'nombre_completo' => $epp->nombre_completo ?? $epp->nombre ?? '',
                            'cantidad' => $pedidoEpp->cantidad ?? 0,
                            'observaciones' => $pedidoEpp->observaciones ?? '',
                            'imagen' => !empty($imagenes) ? $imagenes[0] : null,
                            'imagenes' => $imagenes,
                        ];
                    }
                }
            } catch (\Exception $e) {
                \Log::error('[PedidoController::show] Error procesando EPPs', [
                    'pedido_id' => $id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
            
            \Log::info('[PedidoController::show] EPPs transformados', [
                'pedido_id' => $id,
                'epps_count' => count($eppsList),
                'primer_epp_imagenes' => !empty($eppsList) ? count($eppsList[0]['imagenes']) : 0,
            ]);
            
            // Agregar EPPs transformados
            $datos['epps_transformados'] = $eppsList;

            return response()->json([
                'success' => true,
                'data' => $datos
            ], 200);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pedido: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/pedidos/cliente/{clienteId}
     * 
     * Listar pedidos de un cliente
     */
    public function listarPorCliente(int $clienteId): JsonResponse
    {
        try {
            $response = $this->listarPedidosPorClienteUseCase->ejecutar($clienteId);

            return response()->json([
                'success' => true,
                'data' => array_map(fn($dto) => $dto->toArray(), $response)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al listar pedidos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/pedidos/{id}/recibos-datos
     * 
     * Obtener datos completos del pedido (para recibos)
     * Método de compatibilidad con rutas de asesores
     */
    public function obtenerDetalleCompleto(int $id, bool $filtrarProcesosPendientes = false): JsonResponse
    {
        try {
            // Buscar el pedido por ID o por numero_pedido
            $pedido = \App\Models\PedidoProduccion::find($id);
            
            // Si no encuentra por ID, intenta buscar por numero_pedido
            if (!$pedido) {
                $pedido = \App\Models\PedidoProduccion::where('numero_pedido', $id)->first();
            }
            
            // Si aún no encuentra el pedido, devolver error
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => "Pedido {$id} no encontrado"
                ], 404);
            }
            
            // Usar el ID real del pedido para obtener los detalles
            // En /registros: filtrar solo procesos APROBADOS (no mostrar PENDIENTE)
            // En otras vistas (supervisor): mostrar todos
            $filtrarSoloAprobados = request()->is('registros*');
            $response = $this->obtenerPedidoUseCase->ejecutar($pedido->id, $filtrarSoloAprobados);
            
            // Convertir a array para modificar
            $responseData = $response->toArray();
            
            // Agregar ancho y metraje a cada prenda individual
            if (isset($responseData['prendas']) && is_array($responseData['prendas'])) {
                \Log::info('[PedidoController] Agregando ancho/metraje y consecutivos a prendas', [
                    'pedido_id' => $pedido->id,
                    'total_prendas' => count($responseData['prendas'])
                ]);
                
                foreach ($responseData['prendas'] as $index => &$prenda) {
                    $prendaId = $prenda['id'] ?? $prenda['prenda_pedido_id'] ?? null;
                    
                    \Log::info('[PedidoController] Procesando prenda para datos adicionales', [
                        'index' => $index,
                        'prenda_id' => $prendaId,
                        'prenda_nombre' => $prenda['nombre'] ?? 'N/A'
                    ]);
                    
                    if ($prendaId) {
                        // Buscar ancho y metraje para esta prenda específica
                        $anchoMetrajePrenda = \App\Models\PedidoAnchoMetraje::where('pedido_produccion_id', $pedido->id)
                            ->where('prenda_pedido_id', $prendaId)
                            ->first();
                        
                        if ($anchoMetrajePrenda) {
                            $prenda['ancho_metraje'] = [
                                'ancho' => $anchoMetrajePrenda->ancho,
                                'metraje' => $anchoMetrajePrenda->metraje,
                                'prenda_id' => $anchoMetrajePrenda->prenda_pedido_id
                            ];
                            
                            \Log::info('[PedidoController] Ancho/Metraje encontrado para prenda', [
                                'pedido_id' => $pedido->id,
                                'prenda_id' => $prendaId,
                                'prenda_nombre' => $prenda['nombre'] ?? 'N/A',
                                'ancho' => $anchoMetrajePrenda->ancho,
                                'metraje' => $anchoMetrajePrenda->metraje
                            ]);
                        } else {
                            $prenda['ancho_metraje'] = null;
                            
                            \Log::info('[PedidoController] No hay ancho/metraje para prenda', [
                                'pedido_id' => $pedido->id,
                                'prenda_id' => $prendaId,
                                'prenda_nombre' => $prenda['nombre'] ?? 'N/A'
                            ]);
                        }
                        
                        // Agregar consecutivos para esta prenda
                        $consecutivos = $this->obtenerConsecutivosPrenda($pedido->id, $prendaId);
                        $prenda['recibos'] = $consecutivos;
                        
                        \Log::info('[PedidoController] Consecutivos agregados a prenda', [
                            'pedido_id' => $pedido->id,
                            'prenda_id' => $prendaId,
                            'consecutivos_devueltos' => $consecutivos,
                            'consecutivos_es_null' => is_null($consecutivos)
                        ]);
                        
                    } else {
                        $prenda['ancho_metraje'] = null;
                        $prenda['recibos'] = null;
                        \Log::warning('[PedidoController] Prenda sin ID válido', [
                            'index' => $index,
                            'prenda' => $prenda
                        ]);
                    }
                }
            }
            
            // Mantener el ancho/metraje general por compatibilidad (opcional)
            $anchoMetrajeGeneral = null;
            try {
                $pedido = \App\Models\PedidoProduccion::find($id);
                if ($pedido) {
                    $anchoMetrajeGeneral = [
                        'ancho' => $pedido->ancho ?? null,
                        'metraje' => $pedido->metraje ?? null,
                        'fecha_actualizacion' => $pedido->updated_at ?? null
                    ];
                }
            } catch (\Exception $e) {
                \Log::debug('[PedidoController] Error obteniendo ancho/metraje general', ['error' => $e->getMessage()]);
                $anchoMetrajeGeneral = null;
            }
            
            $responseData['ancho_metraje'] = $anchoMetrajeGeneral;

            return response()->json([
                'success' => true,
                'data' => $responseData
            ], 200);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener consecutivos de una prenda específica
     * 
     * @param int $pedidoId
     * @param int $prendaId
     * @return array|null
     */
    private function obtenerConsecutivosPrenda(int $pedidoId, int $prendaId): ?array
    {
        try {
            \Log::info('[PedidoController] Buscando consecutivos para prenda', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId
            ]);

            // Obtener consecutivos para este pedido (incluyendo generales y específicos de prenda)
            $consecutivos = \Illuminate\Support\Facades\DB::table('consecutivos_recibos_pedidos')
                ->where('pedido_produccion_id', $pedidoId)
                ->where('activo', 1)
                ->where(function($query) use ($prendaId) {
                    $query->where('prenda_id', $prendaId)  // Específicos de esta prenda
                          ->orWhereNull('prenda_id');       // Generales del pedido
                })
                ->get();

            \Log::info('[PedidoController] Consecutivos encontrados en BD', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'total_encontrados' => $consecutivos->count(),
                'datos_crudos' => $consecutivos->toArray()
            ]);

            if ($consecutivos->isEmpty()) {
                \Log::info('[PedidoController] No hay consecutivos para prenda', [
                    'pedido_id' => $pedidoId,
                    'prenda_id' => $prendaId
                ]);
                return null;
            }

            // Estructurar los consecutivos por tipo
            $recibos = [
                'COSTURA' => null,
                'ESTAMPADO' => null,
                'BORDADO' => null,
                'REFLECTIVO' => null
            ];

            foreach ($consecutivos as $consecutivo) {
                $tipo = $consecutivo->tipo_recibo;
                if (array_key_exists($tipo, $recibos)) {  // Cambiado de isset() a array_key_exists()
                    $recibos[$tipo] = $consecutivo->consecutivo_actual;
                }
            }

            \Log::info('[PedidoController] Consecutivos estructurados para prenda', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'recibos' => $recibos
            ]);

            return $recibos;

        } catch (\Exception $e) {
            \Log::error('[PedidoController] Error obteniendo consecutivos de prenda', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * GET /asesores/pedidos/{id}/editar-datos
     * 
     * Obtener datos de un pedido para edición
     * Incluye prendas con variantes, telas, colores, procesos e imágenes
     * Usado por el formulario de edición de pedidos
     */
    public function obtenerDatosEdicion(int $id): JsonResponse
    {
        try {
            $pedido = \App\Models\PedidoProduccion::with([
                'prendas.variantes',
                'prendas.coloresTelas.fotos',
                'prendas.procesos.tipoProceso',
                'prendas.fotos',
                'prendas.telaFotos',
                'epps.epp',
                'asesor:id,name',
                'cliente:id,nombre'
            ])->findOrFail($id);

            // Transformar variantes para incluir nombres de tipos
            if ($pedido->prendas) {
                foreach ($pedido->prendas as $prenda) {
                    if ($prenda->variantes) {
                        foreach ($prenda->variantes as $variante) {
                            // Obtener nombre de manga
                            if ($variante->tipo_manga_id) {
                                try {
                                    $manga = \App\Models\TipoManga::find($variante->tipo_manga_id);
                                    $variante->manga_nombre = $manga ? $manga->nombre : null;
                                } catch (\Exception $e) {
                                    \Log::debug('[PedidoController] Error obtener manga', ['error' => $e->getMessage()]);
                                }
                            }
                            
                            // Obtener nombre de broche
                            if ($variante->tipo_broche_boton_id) {
                                try {
                                    $broche = \App\Models\TipoBrocheBoton::find($variante->tipo_broche_boton_id);
                                    $variante->broche_nombre = $broche ? $broche->nombre : null;
                                } catch (\Exception $e) {
                                    \Log::debug('[PedidoController] Error obtener broche', ['error' => $e->getMessage()]);
                                }
                            }
                        }
                    }
                }
            }

            // Transformar EPPs para incluir imágenes con rutas normalizadas
            $eppsList = [];
            if ($pedido->epps) {
                foreach ($pedido->epps as $pedidoEpp) {
                    $epp = $pedidoEpp->epp;
                    
                    if (!$epp) {
                        continue;
                    }
                    
                    // Obtener imágenes del EPP
                    $imagenes = [];
                    try {
                        $imagenesData = \DB::table('pedido_epp_imagenes')
                            ->where('pedido_epp_id', $pedidoEpp->id)
                            ->orderBy('orden', 'asc')
                            ->get(['ruta_web', 'ruta_original', 'principal', 'orden']);
                        
                        if ($imagenesData->count() > 0) {
                            foreach ($imagenesData as $img) {
                                $ruta = $img->ruta_web ?? $img->ruta_original;
                                // Normalizar ruta
                                if (!str_starts_with($ruta, '/storage/')) {
                                    if (str_starts_with($ruta, 'storage/')) {
                                        $ruta = '/' . $ruta;
                                    } else {
                                        $ruta = '/storage/' . $ruta;
                                    }
                                }
                                
                                $imagenes[] = [
                                    'ruta_webp' => $ruta,
                                    'ruta_original' => $ruta,
                                    'ruta_web' => $ruta,
                                    'principal' => $img->principal ?? false,
                                    'orden' => $img->orden ?? 0,
                                ];
                            }
                        }
                    } catch (\Exception $e) {
                        \Log::debug('[PedidoController] Error obtener imágenes de EPP', ['error' => $e->getMessage()]);
                    }
                    
                    $eppsList[] = [
                        'id' => $pedidoEpp->id,
                        'epp_id' => $pedidoEpp->epp_id,
                        'nombre' => $epp->nombre_completo ?? $epp->nombre ?? '',
                        'nombre_completo' => $epp->nombre_completo ?? $epp->nombre ?? '',
                        'cantidad' => $pedidoEpp->cantidad ?? 0,
                        'observaciones' => $pedidoEpp->observaciones ?? '',
                        'imagen' => !empty($imagenes) ? $imagenes[0] : null,
                        'imagenes' => $imagenes,
                    ];
                }
            }

            // Agregar EPPs a los datos de respuesta
            $datosRespuesta = $pedido->toArray();
            $datosRespuesta['epps_transformados'] = $eppsList;
            
            // CRÍTICO: Verificar que procesos se cargan correctamente con tipoProceso
            if (!empty($datosRespuesta['prendas'])) {
                foreach ($datosRespuesta['prendas'] as $idx => $prenda) {
                    if (!empty($prenda['procesos'])) {
                        \Log::info('[obtenerDatosEdicion] Prenda ' . $idx . ' tiene procesos:', [
                            'prenda_id' => $prenda['id'],
                            'procesos_count' => count($prenda['procesos']),
                            'primer_proceso_keys' => array_keys($prenda['procesos'][0])
                        ]);
                        // Verificar que tipoProceso está en la estructura
                        if (isset($prenda['procesos'][0]['tipo_proceso'])) {
                            \Log::info('[obtenerDatosEdicion] tipoProceso encontrado:', $prenda['procesos'][0]['tipo_proceso']);
                        } elseif (isset($prenda['procesos'][0]['tipoProceso'])) {
                            \Log::info('[obtenerDatosEdicion] tipoProceso (camelCase) encontrado:', $prenda['procesos'][0]['tipoProceso']);
                        } else {
                            \Log::warning('[obtenerDatosEdicion] NO SE ENCONTRÓ tipoProceso en proceso');
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $datosRespuesta
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::warning('[PedidoController] Pedido no encontrado para edición', ['pedido_id' => $id]);
            
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('[PedidoController] Error obtener datos para edición: ' . $e->getMessage(), [
                'pedido_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del pedido',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/api/tipos-broche-boton
     * 
     * Obtener tipos de broche/botón disponibles
     * Array de tipos de broche/botón con su ID
     */
    public function obtenerTiposBrocheBoton(): JsonResponse
    {
        try {
            $tipos = \App\Models\TipoBrocheBoton::where('activo', true)
                ->select('id', 'nombre')
                ->orderBy('id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tipos
            ], 200);
        } catch (\Exception $e) {
            \Log::error('[PedidoController] Error obtener tipos broche/botón: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de broche/botón',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/api/tipos-manga
     * 
     * Obtener tipos de manga disponibles
     * Array de tipos de manga con su ID
     */
    public function obtenerTiposManga(): JsonResponse
    {
        try {
            $tipos = \App\Models\TipoManga::where('activo', true)
                ->select('id', 'nombre')
                ->orderBy('id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $tipos
            ], 200);
        } catch (\Exception $e) {
            \Log::error('[PedidoController] Error obtener tipos manga: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de manga',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /asesores/api/tipos-manga
     * 
     * Crear o obtener un tipo de manga por nombre
     * Si no existe, lo crea automáticamente
     */
    public function crearObtenerTipoManga(Request $request): JsonResponse
    {
        try {
            $nombre = trim($request->input('nombre', ''));
            
            if (empty($nombre)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El nombre del tipo de manga es requerido'
                ], 400);
            }

            // Buscar si ya existe (case-insensitive)
            $tipo = \App\Models\TipoManga::whereRaw('LOWER(nombre) = ?', [strtolower($nombre)])
                ->first();

            // Si no existe, crearlo
            if (!$tipo) {
                $tipo = \App\Models\TipoManga::create([
                    'nombre' => ucfirst(strtolower($nombre)),
                    'activo' => true
                ]);

                \Log::info('[PedidoController] Nuevo tipo de manga creado', [
                    'id' => $tipo->id,
                    'nombre' => $tipo->nombre
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $tipo,
                'mensaje' => $tipo->wasRecentlyCreated ? 'Tipo creado' : 'Tipo existente'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('[PedidoController] Error crear/obtener tipo manga: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear/obtener tipo de manga',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/api/telas
     * 
     * Obtener lista de telas activas
     * Array de { id, nombre }
     * NOTA: referencia ahora está en prenda_pedido_colores_telas, no en telas_prenda
     */
    public function obtenerTelas(): JsonResponse
    {
        try {
            $telas = \App\Models\TelaPrenda::where('activo', true)
                ->select('id', 'nombre')
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $telas
            ], 200);
        } catch (\Exception $e) {
            \Log::error('[PedidoController] Error obtener telas: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener telas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /asesores/api/telas
     * 
     * Crear o obtener una tela por nombre
     * Si no existe, la crea automáticamente
     */
    public function crearObtenerTela(Request $request): JsonResponse
    {
        try {
            $nombre = trim($request->input('nombre', ''));
            $referencia = trim($request->input('referencia', ''));
            
            if (empty($nombre)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El nombre de la tela es requerido'
                ], 400);
            }

            // Buscar si ya existe (case-insensitive)
            $tela = \App\Models\TelaPrenda::whereRaw('LOWER(nombre) = ?', [strtolower($nombre)])
                ->first();

            // Si no existe, crearla
            if (!$tela) {
                $tela = \App\Models\TelaPrenda::create([
                    'nombre' => ucfirst(strtolower($nombre)),
                    'referencia' => $referencia,
                    'activo' => true
                ]);

                \Log::info('[PedidoController] Nueva tela creada', [
                    'id' => $tela->id,
                    'nombre' => $tela->nombre,
                    'referencia' => $tela->referencia
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $tela,
                'mensaje' => $tela->wasRecentlyCreated ? 'Tela creada' : 'Tela existente'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('[PedidoController] Error crear/obtener tela: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear/obtener tela',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/api/colores
     * 
     * Obtener lista de colores activos
     * Array de { id, nombre, codigo }
     */
    public function obtenerColores(): JsonResponse
    {
        try {
            $colores = \App\Models\ColorPrenda::where('activo', true)
                ->select('id', 'nombre', 'codigo')
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $colores
            ], 200);
        } catch (\Exception $e) {
            \Log::error('[PedidoController] Error obtener colores: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener colores',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /asesores/api/colores
     * 
     * Crear o obtener un color por nombre
     * Si no existe, lo crea automáticamente
     */
    public function crearObtenerColor(Request $request): JsonResponse
    {
        try {
            $nombre = trim($request->input('nombre', ''));
            $codigo = trim($request->input('codigo', ''));
            
            if (empty($nombre)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El nombre del color es requerido'
                ], 400);
            }

            // Buscar si ya existe (case-insensitive)
            $color = \App\Models\ColorPrenda::whereRaw('LOWER(nombre) = ?', [strtolower($nombre)])
                ->first();

            // Si no existe, crearlo
            if (!$color) {
                $color = \App\Models\ColorPrenda::create([
                    'nombre' => ucfirst(strtolower($nombre)),
                    'codigo' => $codigo,
                    'activo' => true
                ]);

                \Log::info('[PedidoController] Nuevo color creado', [
                    'id' => $color->id,
                    'nombre' => $color->nombre,
                    'codigo' => $color->codigo
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $color,
                'mensaje' => $color->wasRecentlyCreated ? 'Color creado' : 'Color existente'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('[PedidoController] Error crear/obtener color: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear/obtener color',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /asesores/prendas-pedido/{prendaPedidoId}/fotos
     * 
     * DEPRECADO: Obtener fotos de una prenda del pedido
     * Requiere refactorización a DDD
     */
    public function obtenerFotosPrendaPedido($prendaPedidoId): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Esta funcionalidad está siendo refactorizada a DDD'
        ], 501);
    }

    /**
     * POST /asesores/pedidos/confirm
     * 
     * DEPRECADO: Alias para confirmar pedido
     * Usa: PATCH /api/pedidos/{id}/confirmar
     */
    public function confirm(Request $request): JsonResponse
    {
        $id = $request->input('pedido_id') ?: $request->route('id');
        
        if (!$id) {
            return response()->json([
                'success' => false,
                'message' => 'Se requiere el ID del pedido'
            ], 400);
        }

        return $this->confirmar($id);
    }

    /**
     * POST /asesores/pedidos/{id}/anular
     * 
     * DEPRECADO: Alias para cancelar pedido
     * Usa: DELETE /api/pedidos/{id}/cancelar
     */
    public function anularPedido(Request $request, $id): JsonResponse
    {
        return $this->cancelar($id);
    }
}
