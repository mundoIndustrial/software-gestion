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
            
            // Cargar estado de entrega de cada prenda
            if (isset($datos['prendas']) && is_array($datos['prendas'])) {
                foreach ($datos['prendas'] as &$prenda) {
                    if (isset($prenda['id'])) {
                        $entrega = \App\Models\PrendaEntrega::where('prenda_pedido_id', $prenda['id'])->first();
                        $prenda['entrega'] = $entrega ? [
                            'entregado' => $entrega->entregado,
                            'fecha_entrega' => $entrega->fecha_entrega?->format('Y-m-d H:i:s'),
                            'usuario' => $entrega->usuario?->name,
                        ] : null;
                    }
                }
                unset($prenda); // Romper referencia
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
     * 
     * Filtrado especial para bodeguero: solo muestra COSTURA-BODEGA
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
            
            // Verificar si es bodeguero
            $esBodyguero = auth()->check() && auth()->user()->hasRole('bodeguero');
            
            // VALIDACIÓN BODEGUERO: No puede ver recibos si pedido está en pendiente_cartera o RECHAZADO_CARTERA
            if ($esBodyguero) {
                $estadoPedido = strtolower($pedido->estado ?? '');
                \Log::info('[PedidoController] Estado del pedido para bodeguero', [
                    'numero_pedido' => $pedido->numero_pedido,
                    'estado_raw' => $pedido->estado,
                    'estado_lower' => $estadoPedido,
                    'es_pendiente_cartera' => $estadoPedido === 'pendiente_cartera',
                    'es_rechazado_cartera' => $estadoPedido === 'rechazado_cartera'
                ]);
                
                if ($estadoPedido === 'pendiente_cartera' || $estadoPedido === 'rechazado_cartera') {
                    \Log::warning('[PedidoController] 🔐 Bodeguero bloqueado - Pedido en estado: ' . $pedido->estado, [
                        'pedido_id' => $pedido->id,
                        'numero_pedido' => $pedido->numero_pedido,
                        'usuario_id' => auth()->id(),
                        'estado' => $pedido->estado
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'No puedes ver recibos de pedidos en estado ' . $pedido->estado
                    ], 403);
                }
            }
            
            // Usar el ID real del pedido para obtener los detalles
            // En /registros: filtrar solo procesos APROBADOS (no mostrar PENDIENTE)
            // En otras vistas (supervisor): mostrar todos
            $filtrarSoloAprobados = request()->is('registros*');
            $response = $this->obtenerPedidoUseCase->ejecutar($pedido->id, $filtrarSoloAprobados);
            
            // Convertir a array para modificar
            $responseData = $response->toArray();
            
            // FILTRO BODEGUERO: Si es bodeguero, filtrar procesos para mostrar SOLO 'costura-bodega'
            if ($esBodyguero && isset($responseData['prendas']) && is_array($responseData['prendas'])) {
                \Log::info('[PedidoController] 🔐 FILTRO BODEGUERO: Filtrando procesos - Solo COSTURA-BODEGA', [
                    'pedido_id' => $pedido->id,
                    'usuario_id' => auth()->id(),
                    'total_prendas' => count($responseData['prendas'])
                ]);
                
                // DEBUG: Loguear estructura de procesos
                foreach ($responseData['prendas'] as $prendaIdx => $prenda) {
                    if (isset($prenda['procesos']) && is_array($prenda['procesos'])) {
                        \Log::debug('[PedidoController] Estructura de procesos para prenda', [
                            'prenda_idx' => $prendaIdx,
                            'prenda_nombre' => $prenda['nombre'] ?? 'N/A',
                            'total_procesos' => count($prenda['procesos']),
                            'primer_proceso' => isset($prenda['procesos'][0]) ? $prenda['procesos'][0] : 'vacío',
                            'claves_primer_proceso' => isset($prenda['procesos'][0]) ? array_keys($prenda['procesos'][0]) : []
                        ]);
                    }
                }
                
                foreach ($responseData['prendas'] as &$prenda) {
                    if (isset($prenda['procesos']) && is_array($prenda['procesos'])) {
                        // Filtrar: solo mantener procesos 'costura-bodega'
                        $procesosFiltrados = array_filter($prenda['procesos'], function($proceso) {
                            // Intentar obtener el nombre del proceso desde varias claves posibles
                            $tipoProceso = $proceso['tipo_proceso'] ?? $proceso['nombre_proceso'] ?? $proceso['nombre'] ?? $proceso['proceso'] ?? '';
                            $tipoLower = strtolower(trim($tipoProceso));
                            
                            \Log::debug('[PedidoController] Verificando proceso para bodeguero', [
                                'tipo_proceso' => $tipoProceso,
                                'tipo_lower' => $tipoLower,
                                'proceso_keys' => array_keys($proceso),
                                'es_costura_bodega' => $tipoLower === 'costura-bodega' || $tipoLower === 'costurabodega'
                            ]);
                            
                            return $tipoLower === 'costura-bodega' || $tipoLower === 'costurabodega';
                        });
                        
                        $prenda['procesos'] = array_values($procesosFiltrados); // Reindexar array
                        
                        \Log::info('[PedidoController] 🔐 Procesos filtrados para bodeguero', [
                            'prenda_id' => $prenda['id'] ?? 'N/A',
                            'procesos_antes' => count($prenda['procesos'] ?? []),
                            'procesos_despues' => count($procesosFiltrados)
                        ]);
                    }
                }
                
                // Validar que bodeguero tenga al menos UN proceso costura-bodega después del filtrado
                $tieneProcesoCosturaBodega = false;
                foreach ($responseData['prendas'] as $prenda) {
                    if (isset($prenda['procesos']) && is_array($prenda['procesos']) && !empty($prenda['procesos'])) {
                        $tieneProcesoCosturaBodega = true;
                        break;
                    }
                }
                
                if (!$tieneProcesoCosturaBodega) {
                    \Log::warning('[PedidoController] 🔐 Bodeguero intenta ver pedido sin procesos costura-bodega', [
                        'pedido_id' => $pedido->id,
                        'numero_pedido' => $pedido->numero_pedido,
                        'usuario_id' => auth()->id()
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Este pedido no tiene procesos de COSTURA-BODEGA disponibles'
                    ], 403);
                }
            }
            
            // FILTRO INSUMOS: Si es insumos, filtrar prendas para mostrar SOLO de_bodega = false
            $esInsumos = auth()->check() && auth()->user()->hasRole('insumos');
            if ($esInsumos && isset($responseData['prendas']) && is_array($responseData['prendas'])) {
                \Log::info('[PedidoController]  FILTRO INSUMOS: Mostrando solo prendas con de_bodega = false', [
                    'pedido_id' => $pedido->id,
                    'usuario_id' => auth()->id(),
                    'total_prendas_antes' => count($responseData['prendas'])
                ]);
                
                // Filtrar: solo mantener prendas con de_bodega = false
                $prendasFiltradas = array_filter($responseData['prendas'], function($prenda) {
                    $deBodega = $prenda['de_bodega'] ?? false;
                    // Si de_bodega es string (tinyint from DB puede ser "0" o "1")
                    if (is_string($deBodega)) {
                        $deBodega = (bool)intval($deBodega);
                    }
                    return !$deBodega; // Mostrar solo si NO es de_bodega
                });
                
                $responseData['prendas'] = array_values($prendasFiltradas); // Reindexar array
                
                \Log::info('[PedidoController]  Prendas filtradas para insumos', [
                    'pedido_id' => $pedido->id,
                    'total_prendas_antes' => count($responseData['prendas'] ?? []) + count($prendasFiltradas),
                    'total_prendas_despues' => count($prendasFiltradas),
                    'prendas_filtradas' => array_map(function($p) {
                        return [
                            'nombre' => $p['nombre'] ?? 'N/A',
                            'de_bodega' => $p['de_bodega'] ?? 'N/A'
                        ];
                    }, $prendasFiltradas)
                ]);
                
                // Validar que insumos tenga al menos UNA prenda después del filtrado
                if (empty($prendasFiltradas)) {
                    \Log::warning('[PedidoController]  Insumos intenta ver pedido sin prendas de_bodega=false', [
                        'pedido_id' => $pedido->id,
                        'numero_pedido' => $pedido->numero_pedido,
                        'usuario_id' => auth()->id()
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Este pedido no tiene prendas disponibles para insumos (todas son de bodega)'
                    ], 403);
                }
            }
            
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
                        $prenda['consecutivos'] = $consecutivos; // Agregar también para consistencia
                        
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

            // DEBUG: Loguear estructura final de tallas antes de enviar
            if (isset($responseData['prendas']) && is_array($responseData['prendas'])) {
                foreach ($responseData['prendas'] as $prendaIndex => $prenda) {
                    if (isset($prenda['procesos']) && is_array($prenda['procesos'])) {
                        foreach ($prenda['procesos'] as $procIndex => $proceso) {
                            if (isset($proceso['tallas'])) {
                                \Log::info('[PedidoController] ESTRUCTURA FINAL DE TALLAS ANTES DE ENVIAR', [
                                    'prenda_index' => $prendaIndex,
                                    'proceso_index' => $procIndex,
                                    'proceso_id' => $proceso['id'] ?? 'N/A',
                                    'tallas_keys' => array_keys($proceso['tallas']),
                                    'tallas_data' => $proceso['tallas'],
                                    'caballero_data' => $proceso['tallas']['caballero'] ?? 'NO ENCONTRADO'
                                ]);
                            }
                        }
                    }
                }
            }

            // Agregar datos adicionales del pedido
            if ($pedido) {
                // Agregar fecha estimada de entrega si no está presente
                if (!isset($responseData['fecha_estimada_de_entrega'])) {
                    $responseData['fecha_estimada_de_entrega'] = $pedido->fecha_estimada_de_entrega;
                }
                
                // Agregar otros campos importantes si no están presentes
                if (!isset($responseData['area'])) {
                    $responseData['area'] = $pedido->area;
                }
                
                if (!isset($responseData['dia_de_entrega'])) {
                    $responseData['dia_de_entrega'] = $pedido->dia_de_entrega;
                }
                
                \Log::info('[PedidoController] Datos del pedido agregados', [
                    'pedido_id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido,
                    'fecha_estimada_de_entrega' => $pedido->fecha_estimada_de_entrega,
                    'area' => $pedido->area,
                    'dia_de_entrega' => $pedido->dia_de_entrega
                ]);
            }
            
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

            // Obtener procesos aprobados de esta prenda
            $procesosAprobados = \Illuminate\Support\Facades\DB::table('pedidos_procesos_prenda_detalles')
                ->where('prenda_pedido_id', $prendaId)
                ->where('estado', 'APROBADO')
                ->pluck('tipo_proceso_id')
                ->toArray();

            \Log::info('[PedidoController] Procesos aprobados encontrados', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'procesos_aprobados' => $procesosAprobados
            ]);

            if (empty($procesosAprobados)) {
                \Log::info('[PedidoController] No hay procesos aprobados para prenda', [
                    'pedido_id' => $pedidoId,
                    'prenda_id' => $prendaId
                ]);
                return null;
            }

            // Mapear tipo_proceso_id a tipo_recibo
            $tipoProcesoARecibo = [
                1 => 'REFLECTIVO',      // ID 1 = Reflectivo
                2 => 'ESTAMPADO',       // ID 2 = Estampado
                3 => 'BORDADO',         // ID 3 = Bordado
                4 => 'DTF',             // ID 4 = DTF
                5 => 'SUBLIMADO',       // ID 5 = Sublimado
                6 => 'COSTURA',         // ID 6 = Costura
                // Agregar más mapeos según sea necesario
            ];

            $tiposReciboPermitidos = array_unique(array_map(function($procesoId) use ($tipoProcesoARecibo) {
                return $tipoProcesoARecibo[$procesoId] ?? null;
            }, $procesosAprobados));

            $tiposReciboPermitidos = array_filter($tiposReciboPermitidos);

            \Log::info('[PedidoController] Tipos de recibo permitidos', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'tipos_recibo' => $tiposReciboPermitidos
            ]);

            // Obtener consecutivos específicos para esta prenda basados en sus procesos aprobados
            $consecutivos = \Illuminate\Support\Facades\DB::table('consecutivos_recibos_pedidos')
                ->where('pedido_produccion_id', $pedidoId)
                ->where('prenda_id', $prendaId)  // Solo específicos de esta prenda
                ->where('activo', 1)
                ->whereIn('tipo_recibo', $tiposReciboPermitidos)
                ->orderBy('created_at', 'desc')  // Más reciente primero
                ->get();

            \Log::info('[PedidoController] Consecutivos encontrados en BD', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'total_encontrados' => $consecutivos->count(),
                'datos_crudos' => $consecutivos->toArray()
            ]);

            if ($consecutivos->isEmpty()) {
                \Log::info('[PedidoController] No hay consecutivos para procesos aprobados de la prenda', [
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
                'DTF' => null,
                'SUBLIMADO' => null,
                'REFLECTIVO' => null
            ];

            foreach ($consecutivos as $consecutivo) {
                $tipo = $consecutivo->tipo_recibo;
                if (array_key_exists($tipo, $recibos)) {
                    // Si ya hay un valor, mantener el más reciente (ya están ordenados por created_at desc)
                    if ($recibos[$tipo] === null) {
                        $recibos[$tipo] = $consecutivo->consecutivo_actual;
                    }
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

            // Cargar talla_colores manualmente para cada prenda
            if ($pedido->prendas) {
                foreach ($pedido->prendas as $prenda) {
                    $tallaColores = \DB::table('prenda_pedido_talla_colores as ptc')
                        ->join('prenda_pedido_tallas as pt', 'ptc.prenda_pedido_talla_id', '=', 'pt.id')
                        ->where('pt.prenda_pedido_id', $prenda->id)
                        ->select([
                            'ptc.id',
                            'ptc.prenda_pedido_talla_id',
                            'pt.genero',
                            'pt.talla',
                            'ptc.tela_id',
                            'ptc.tela_nombre',
                            'ptc.color_id',
                            'ptc.color_nombre',
                            'ptc.cantidad'
                        ])
                        ->get()
                        ->toArray();
                    
                    $prenda->talla_colores = $tallaColores;
                    
                    \Log::info('[PedidoController] talla_colores cargados para prenda ' . $prenda->id, [
                        'cantidad' => count($tallaColores)
                    ]);
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

            //  BÚSQUEDA EXACTA: Solo coincidencia perfecta
            // NO usar case-insensitive - debe ser EXACTAMENTE lo que el usuario escribe
            $tela = \App\Models\TelaPrenda::where('nombre', $nombre)
                ->first();

            // Si no existe, crearla
            if (!$tela) {
                $tela = \App\Models\TelaPrenda::create([
                    'nombre' => $nombre,  //  Guardar exactamente como enviadas
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

            //  BÚSQUEDA EXACTA: Solo coincidencia perfecta
            // NO usar case-insensitive - debe ser EXACTAMENTE lo que el usuario escribe
            $color = \App\Models\ColorPrenda::where('nombre', $nombre)
                ->first();

            // Si no existe, crearlo
            if (!$color) {
                $color = \App\Models\ColorPrenda::create([
                    'nombre' => $nombre,  //  Guardar exactamente como enviadas
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
