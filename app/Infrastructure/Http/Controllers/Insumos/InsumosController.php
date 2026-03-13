<?php

namespace App\Infrastructure\Http\Controllers\Insumos;

use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Controllers\Traits\HandlesExceptions;
use App\Infrastructure\Http\Controllers\Traits\CalculateWorkingDays;
use App\Services\Insumos\MaterialesService;
use App\Services\Insumos\RecibosQueryService;
use App\Models\PedidoProduccion;
use App\Models\ConsecutivoReciboPedido;
use App\Models\MaterialesOrdenInsumos;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Insumos\GuardarAnchoMetrajeRequest;

class InsumosController extends Controller
{
    use HandlesExceptions;
    use CalculateWorkingDays;

    protected $materialesService;
    protected $recibosQueryService;

    public function __construct(MaterialesService $materialesService, RecibosQueryService $recibosQueryService)
    {
        $this->materialesService = $materialesService;
        $this->recibosQueryService = $recibosQueryService;
    }

    /**
     * Dashboard del rol insumos
     */
    public function dashboard()
    {
        $user = Auth::user();
        
        return view('insumos.dashboard', [
            'user' => $user,
        ]);
    }

    /**
     * Obtener valores únicos de una columna para filtros
     */
    public function obtenerValoresFiltro($column)
    {
        try {
            $resultado = $this->materialesService->obtenerOpcionesFiltro($column);
            return response()->json([
                'success' => true,
                'column' => $column,
                'valores' => $resultado,
                'total' => count($resultado)
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener filtros: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener filtros'
            ], 500);
        }
    }

    /**
     * Control de materiales - Delegado completamente al servicio
     */
    public function materiales(Request $request)
    {
        try {
            $user = Auth::user();
            
            // TODA la lógica de query/filtrado/paginación está en el servicio
            $ordenes = $this->recibosQueryService->obtenerRecibosConPaginacion(
                $request,
                fn($fecha) => $this->calcularDiasHabiles($fecha)
            );
            
            return view('insumos.materiales.index', [
                'ordenes' => $ordenes,
                'user' => $user,
                'search' => $request->get('search', ''),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'obtener recibos de costura');
        }
    }

    /**
     * Guardar materiales de una orden
     */
    public function guardarMateriales(Request $request, $ordenId)
    {
        try {
            $user = Auth::user();

            
            // Buscar por numero_pedido en lugar de ID
            $orden = PedidoProduccion::where('numero_pedido', $ordenId)->firstOrFail();
            
            // Validar datos
            $validated = $request->validate([
                'materiales' => 'array',
                'materiales.*.nombre' => 'required|string',
                'materiales.*.fecha_orden' => 'nullable|date',
                'materiales.*.fecha_pedido' => 'nullable|date',
                'materiales.*.fecha_pago' => 'nullable|date',
                'materiales.*.fecha_llegada' => 'nullable|date',
                'materiales.*.fecha_despacho' => 'nullable|date',
                'materiales.*.observaciones' => 'nullable|string',
                'materiales.*.recibido' => 'boolean',
                'prenda_id' => 'nullable|integer|exists:prendas_pedido,id',
            ]);
            
            $prendaId = $validated['prenda_id'] ?? null;
            
            // Si materiales no viene en el request, usar array vacío
            if (!isset($validated['materiales'])) {
                $validated['materiales'] = [];
            }
            
            // Guardar o eliminar materiales según el estado del checkbox
            $materialesGuardados = 0;
            $materialesEliminados = 0;
            
            \Log::info('🔵 GUARDANDO MATERIALES - Pedido ID: ' . $orden->id . ', Número: ' . $orden->numero_pedido);
            \Log::info(' Materiales recibidos:', $validated['materiales']);
            \Log::info(' Total de materiales: ' . count($validated['materiales']));
            
            foreach ($validated['materiales'] as $material) {
                $isRecibido = $material['recibido'] === true || $material['recibido'] === 'true' || $material['recibido'] === 1 || $material['recibido'] === '1';
                
                \Log::info(" Procesando material: {$material['nombre']}, recibido: {$material['recibido']}, isRecibido: " . ($isRecibido ? 'true' : 'false'));
                
                if ($isRecibido) {
                    // Guardar/actualizar si recibido es true
                    $matchCriteria = [
                        'numero_pedido' => $orden->numero_pedido,
                        'nombre_material' => $material['nombre'],
                    ];
                    if ($prendaId) {
                        $matchCriteria['prenda_id'] = $prendaId;
                    }
                    
                    $result = MaterialesOrdenInsumos::updateOrCreate(
                        $matchCriteria,
                        [
                            'prenda_id' => $prendaId,
                            'fecha_orden' => $material['fecha_orden'] ?? null,
                            'fecha_pedido' => $material['fecha_pedido'] ?? null,
                            'fecha_pago' => $material['fecha_pago'] ?? null,
                            'fecha_llegada' => $material['fecha_llegada'] ?? null,
                            'fecha_despacho' => $material['fecha_despacho'] ?? null,
                            'observaciones' => $material['observaciones'] ?? null,
                            'recibido' => true,
                        ]
                    );
                    $materialesGuardados++;
                    \Log::info(" Material guardado: {$material['nombre']}, ID: {$result->id}, Prenda: {$prendaId}, Fecha Pedido: {$material['fecha_pedido']}, Fecha Llegada: {$material['fecha_llegada']}");
                } else {
                    // Eliminar si recibido es false
                    $deleteCriteria = [
                        'numero_pedido' => $orden->numero_pedido,
                        'nombre_material' => $material['nombre'],
                    ];
                    if ($prendaId) {
                        $deleteCriteria['prenda_id'] = $prendaId;
                    }
                    
                    $deleted = MaterialesOrdenInsumos::where($deleteCriteria)->delete();
                    
                    if ($deleted > 0) {
                        $materialesEliminados++;
                        \Log::info("🗑️ Material eliminado: {$material['nombre']}");
                    } else {
                        \Log::info(" No se encontró material para eliminar: {$material['nombre']}");
                    }
                }
            }
            
            \Log::info(" Resumen: Guardados: $materialesGuardados, Eliminados: $materialesEliminados");
            
            $mensaje = [];
            if ($materialesGuardados > 0) {
                $mensaje[] = "Se guardaron {$materialesGuardados} material(es)";
            }
            if ($materialesEliminados > 0) {
                $mensaje[] = "Se eliminaron {$materialesEliminados} material(es)";
            }
            
            return response()->json([
                'success' => true,
                'message' => !empty($mensaje) 
                    ? implode(' y ', $mensaje) . ' correctamente' 
                    : 'Sin cambios',
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = [];
            foreach ($e->errors() as $field => $messages) {
                $errors = array_merge($errors, $messages);
            }
            return response()->json([
                'success' => false,
                'message' => 'Error de validación: ' . implode(', ', $errors)
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al guardar materiales: ' . $e->getMessage(), [
                'pedido' => $ordenId,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar los materiales: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un material inmediatamente
     */
    public function eliminarMaterial(Request $request, $ordenId)
    {
        try {
            $user = Auth::user();

            
            // Buscar por numero_pedido en lugar de ID
            $orden = PedidoProduccion::where('numero_pedido', $ordenId)->firstOrFail();
            
            // Validar datos
            $validated = $request->validate([
                'nombre_material' => 'required|string',
            ]);
            
            // Eliminar el material
            $deleted = MaterialesOrdenInsumos::where([
                'numero_pedido' => $orden->numero_pedido,
                'nombre_material' => $validated['nombre_material'],
            ])->delete();
            
            if ($deleted > 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Material eliminado correctamente',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Material no encontrado',
                ], 404);
            }
        } catch (\Exception $e) {
            \Log::error('Error al eliminar material: ' . $e->getMessage(), [
                'pedido' => $ordenId,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el material: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener materiales de una orden (API)
     */
    public function obtenerMateriales($pedido)
    {
        try {
            $user = Auth::user();

            
            // Validar que el pedido existe
            PedidoProduccion::where('numero_pedido', $pedido)->firstOrFail();
            
            // Obtener materiales guardados usando numero_pedido, filtrados por prenda si se envía
            $query = MaterialesOrdenInsumos::where('numero_pedido', $pedido);
            
            $prendaId = request('prenda_id');
            if ($prendaId) {
                $query->where('prenda_id', $prendaId);
            }
            
            $materiales = $query->get();
            
            // Obtener nombre de la prenda si se filtró por prenda_id
            $nombrePrenda = null;
            if ($prendaId) {
                $prenda = \App\Models\PrendaPedido::find($prendaId);
                $nombrePrenda = $prenda ? $prenda->nombre_prenda : null;
            }
            
            // Transformar los datos para la respuesta
            $materialesTransformados = $materiales->map(function($material) {
                return [
                    'id' => $material->id,
                    'nombre_material' => $material->nombre_material,
                    'recibido' => $material->recibido,
                    'prenda_id' => $material->prenda_id,
                    'fecha_orden' => $material->fecha_orden ? $material->fecha_orden->format('Y-m-d') : null,
                    'fecha_pedido' => $material->fecha_pedido ? $material->fecha_pedido->format('Y-m-d') : null,
                    'fecha_pago' => $material->fecha_pago ? $material->fecha_pago->format('Y-m-d') : null,
                    'fecha_llegada' => $material->fecha_llegada ? $material->fecha_llegada->format('Y-m-d') : null,
                    'fecha_despacho' => $material->fecha_despacho ? $material->fecha_despacho->format('Y-m-d') : null,
                    'dias_demora' => $material->dias_demora,
                    'observaciones' => $material->observaciones,
                ];
            });
            
            return response()->json([
                'success' => true,
                'materiales' => $materialesTransformados,
                'nombre_prenda' => $nombrePrenda,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al obtener materiales: ' . $e->getMessage(), [
                'pedido' => $pedido,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los materiales: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar todas las notificaciones como leídas (para supervisor_planta)
     */
    public function markAllNotificationsAsRead()
    {
        try {
            $user = Auth::user();
            
            // Verificar que sea usuario de insumos

            
            // Guardar en sesión los IDs de órdenes pendientes de revisión
            $ordenesPendientes = PedidoProduccion::whereNull('aprobado_por_supervisor_en')
                ->whereNotNull('cotizacion_id')
                ->where('estado', '!=', 'Anulada')
                ->pluck('id')
                ->toArray();
            
            session(['viewed_ordenes_' . $user->id => $ordenesPendientes]);
            
            return response()->json([
                'success' => true,
                'message' => 'Notificaciones marcadas como leídas'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al marcar notificaciones como leídas',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     */
    public function cambiarEstado(Request $request, $numeroPedido)
    {
        try {
            $user = Auth::user();

            
            // Validar datos
            $validated = $request->validate([
                'estado' => [
                    'required',
                    'string',
                    Rule::in(['No iniciado', 'En Ejecución', 'PENDIENTE_INSUMOS'])
                ],
            ]);
            
            // Buscar el pedido
            $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)
                ->lockForUpdate()
                ->firstOrFail();
            
            // Delegar al servicio
            $resultado = $this->materialesService->cambiarEstado($pedido->id, $validated['estado']);
            
            return response()->json($resultado);
        } catch (\Exception $e) {
            return $this->handleExceptionWithContext(
                $e,
                'Error al cambiar estado'
            );
        }
    }

    /**
     * Cambiar estado de un recibo individual (consecutivos_recibos_pedidos)
     * Solo aprueba ese recibo específico, NO todo el pedido
     */
    public function cambiarEstadoRecibo(Request $request, $reciboId)
    {
        try {
            $user = Auth::user();

            
            $validated = $request->validate([
                'estado' => ['required', 'string', Rule::in(['No iniciado', 'En Ejecución'])],
            ]);
            
            $resultado = $this->materialesService->cambiarEstadoRecibo($reciboId, $validated['estado']);
            return response()->json($resultado);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Recibo no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return $this->handleExceptionWithContext(
                $e,
                'Error al cambiar el estado del recibo',
                ['recibo_id' => $reciboId]
            );
        }
    }

    /**
     * Obtener las prendas de un pedido para el selector
     */
    public function obtenerPrendas($numeroPedido)
    {
        try {
            $user = Auth::user();

            
            // Buscar el pedido por número para obtener el ID
            $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();
            
            // Obtener las prendas del pedido usando el ID
            $prendas = $pedido->prendas()
                ->select('id', 'nombre_prenda', 'descripcion')
                ->get();
            
            return response()->json([
                'success' => true,
                'prendas' => $prendas
            ]);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Error al obtener prendas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener prendas'
            ], 500);
        }
    }

    /**
     * Obtener ancho y metraje de una prenda específica
     */
    /**
     * Obtener colores/telas disponibles para una prenda (detecta el flujo: normal, piezas o talla-color)
     * 
     * Lógica:
     * 1. Si prenda_pedido_talla_colores tiene registros → MODO TALLA-COLOR (matriz)
     * 2. Else si prenda_pedido_colores_telas tiene registros → MODO PIEZAS (múltiples telas/colores)
     * 3. Else → MODO NORMAL (una sola tela)
     */
    public function obtenerColoresPrenda($numeroPedido, $prendaId)
    {
        try {
            $user = Auth::user();

            
            $pedido = PedidoProduccion::find($numeroPedido)
                ?? PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();
            
            // Delegar al servicio
            $resultado = $this->materialesService->obtenerColoresPrenda($pedido->id, $prendaId);
            
            return response()->json($resultado);
        } catch (\Exception $e) {
            return $this->handleExceptionWithContext(
                $e,
                'Error al obtener colores de prenda'
            );
        }
    }
    
    /**
     * Obtener ancho general y metrajes por color para una prenda
     */
    public function obtenerAnchoMetrajePrenda($numeroPedido, $prendaId)
    {
        try {
            $user = Auth::user();

            
            $pedido = PedidoProduccion::find($numeroPedido)
                ?? PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();
            
            // Delegar al servicio
            $resultado = $this->materialesService->obtenerAnchoMetrajePrenda($pedido->id, $prendaId);
            
            return response()->json($resultado);
        } catch (\Exception $e) {
            \Log::error('Error al obtener ancho/metraje de prenda: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ancho/metraje de prenda'
            ], 500);
        }
    }

    /**
     * Guardar ancho general y/o metraje por color
     */
    public function guardarAnchoMetrajePrenda(GuardarAnchoMetrajeRequest $request, $numeroPedido)
    {
        try {
            $user = Auth::user();

            $validated = $request->validated();
            
            // Buscar el pedido
            $pedido = PedidoProduccion::find($numeroPedido)
                ?? PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();
            
            // Delegar al servicio con pedido, prenda y datos validados
            $resultado = $this->materialesService->guardarAnchoMetrajePrenda(
                $pedido->id,
                $validated['prenda_pedido_id'],
                $validated
            );
            
            return response()->json($resultado);
        } catch (\Exception $e) {
            return $this->handleExceptionWithContext(
                $e,
                'Error al guardar ancho y metraje de prenda'
            );
        }
    }

    /**
     * Elimina ancho general y/o metraje por color de una prenda
     */
    public function eliminarAnchoMetrajePrenda(Request $request, $numeroPedido)
    {
        try {
            $user = Auth::user();

            
            // Validar datos
            $validated = $request->validate([
                'prenda_id' => 'required|integer|exists:prendas_pedido,id'
            ]);
            
            // Buscar el pedido
            $pedido = PedidoProduccion::find($numeroPedido)
                ?? PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();
            
            // Delegar al servicio
            $resultado = $this->materialesService->eliminarAnchoMetrajePrenda($pedido->id, $validated['prenda_id']);
            
            return response()->json($resultado);
        } catch (\Exception $e) {
            return $this->handleExceptionWithContext(
                $e,
                'Error al eliminar ancho y metraje de prenda'
            );
        }
    }

    /**
     * Obtener el número de recibo para una prenda en un pedido
     */
    public function obtenerReciboPrenda($numeroPedido, $prendaId)
    {
        try {
            $user = Auth::user();

            
            $pedido = PedidoProduccion::find($numeroPedido)
                ?? PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();
            
            // Buscar el recibo más reciente/activo para esta prenda en este pedido
            $recibo = ConsecutivoReciboPedido::where('pedido_produccion_id', $pedido->id)
                ->where('prenda_id', $prendaId)
                ->where('activo', 1)
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($recibo) {
                return response()->json([
                    'success' => true,
                    'recibo' => $recibo->consecutivo_actual
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'recibo' => null
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('Error al obtener recibo de prenda: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener recibo de prenda'
            ], 500);
        }
    }

    /**
     * Mostrar recibos de costura para el módulo de insumos
     * Funciona como /recibos-costura pero manteniendo las opciones de insumos
     */
    public function recibosCostura(Request $request)
    {
        try {
            $user = Auth::user();


            // Obtener recibos de costura activos, aprobados y excluyendo pedidos con estado PENDIENTE_SUPERVISOR
            $recibosCostura = DB::table('consecutivos_recibos_pedidos')
                ->where('tipo_recibo', 'COSTURA')
                ->where('activo', 1)
                ->where('estado', '!=', 'PENDIENTE_INSUMOS')
                ->whereNotExists(function($query) {
                    $query->select(DB::raw(1))
                          ->from('pedidos_produccion')
                          ->whereRaw('pedidos_produccion.id = consecutivos_recibos_pedidos.pedido_produccion_id')
                          ->where('pedidos_produccion.estado', 'PENDIENTE_SUPERVISOR');
                })
                ->orderBy('consecutivo_actual', 'desc')
                ->get();

            \Log::info('[recibosCostura] Filtrando recibos de costura - excluyendo pedidos PENDIENTE_SUPERVISOR', [
                'total_recibos_encontrados' => $recibosCostura->count()
            ]);

            // Obtener información adicional de pedidos y prendas
            $recibosConInfo = $recibosCostura->map(function ($recibo) {
                $pedido = PedidoProduccion::find($recibo->pedido_produccion_id);
                
                
                if ($pedido) {
                    \Log::info('[materiales] Prendas del pedido', [
                        'pedido_id' => $pedido->id,
                        'total_prendas' => $pedido->prendas ? $pedido->prendas->count() : 0,
                        'prendas_ids' => $pedido->prendas ? $pedido->prendas->pluck('id')->toArray() : []
                    ]);
                }
                
                // Calcular días hábiles usando el trait
                $diasCalculados = 0;
                if ($pedido && $pedido->fecha_de_creacion_de_orden) {
                    $diasCalculados = $this->calcularDiasHabiles($pedido->fecha_de_creacion_de_orden);
                }
                
                // Obtener el proceso más reciente para el área
                $areaProcesoReciente = $this->obtenerAreaProcesoMasReciente($recibo->pedido_produccion_id, $recibo->prenda_id);
                
                return [
                    'id' => $recibo->id,
                    'consecutivo_actual' => $recibo->consecutivo_actual,
                    'pedido_produccion_id' => $recibo->pedido_produccion_id,
                    'prenda_id' => $recibo->prenda_id,
                    'tipo_recibo' => $recibo->tipo_recibo,
                    'notas' => $recibo->notas,
                    'created_at' => $recibo->created_at,
                    'updated_at' => $recibo->updated_at,
                    'dias_calculados' => $diasCalculados,
                    'pedido_info' => $pedido ? [
                        'numero_pedido' => $pedido->numero_pedido,
                        'cliente' => $pedido->cliente,
                        'estado' => $pedido->estado,
                        'area' => $areaProcesoReciente,
                        'dia_de_entrega' => $pedido->dia_de_entrega,
                        'fecha_estimada_de_entrega' => $pedido->fecha_estimada_de_entrega ? $pedido->fecha_estimada_de_entrega->format('d/m/Y') : null,
                        'fecha_creacion_orden' => $pedido->fecha_de_creacion_de_orden ? $pedido->fecha_de_creacion_de_orden->format('Y-m-d H:i:s') : null,
                    ] : null,
                ];
            });

            // Si es una solicitud AJAX, retornar JSON con HTML de la tabla
            if ($request->ajax() || $request->wantsJson()) {
                // Renderizar el HTML del tbody
                $htmlTbody = view('components.recibos.recibos-costura-table-tbody', [
                    'recibos' => $recibosConInfo,
                    'totalCantidadGlobal' => 0
                ])->render();
                
                return response()->json([
                    'success' => true,
                    'recibos' => [
                        'html' => $htmlTbody,
                        'data' => $recibosConInfo
                    ],
                    'total' => $recibosConInfo->count(),
                    'total_cantidad' => 0
                ]);
            }

            return view('insumos.materiales.recibos-costura', [
                'recibos' => $recibosConInfo,
                'title' => 'Recibos de Costura - Insumos'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en recibosCostura (Insumos): ' . $e->getMessage());
            return back()->with('error', 'Error al cargar los recibos de costura');
        }
    }

    /**
     * Obtener el área del proceso más reciente de una prenda
     */
    private function obtenerAreaProcesoMasReciente($pedidoId, $prendaId)
    {
        try {
            $procesoReciente = DB::table('pedidos_procesos_prenda_detalles')
                ->where('pedido_produccion_id', $pedidoId)
                ->where('prenda_pedido_id', $prendaId)
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($procesoReciente && $procesoReciente->tipo_proceso_id) {
                $tipoProceso = DB::table('tipos_procesos')->where('id', $procesoReciente->tipo_proceso_id)->first();
                return $tipoProceso ? $tipoProceso->nombre : 'Sin área';
            }

            return 'Sin procesos';
        } catch (\Exception $e) {
            \Log::warning('Error obteniendo área proceso más reciente', [
                'pedido_id' => $pedidoId,
                'prenda_id' => $prendaId,
                'error' => $e->getMessage()
            ]);
            return 'Error';
        }
    }

    /**
     * Contar y listar recibos COSTURA en estado PENDIENTE_INSUMOS (no vistos por el usuario actual)
     * Endpoint: GET /insumos/api/contar-costura-pendiente
     */
    public function contarCosturaPendiente()
    {
        try {
            $user = Auth::user();


            $resultado = $this->materialesService->contarCosturaPendiente($user->id);

            $lista = collect($resultado['recibos'])->map(function ($recibo) {
                return [
                    'id' => $recibo->id,
                    'numero_recibo' => $recibo->consecutivo_actual,
                    'cliente' => $recibo->pedido->cliente ?? 'Sin cliente',
                    'pedido_id' => $recibo->pedido_produccion_id,
                    'fecha' => $recibo->created_at ? $recibo->created_at->format('d/m/Y H:i') : '',
                ];
            });

            return response()->json([
                'success' => true,
                'total' => $resultado['total'] ?? count($lista),
                'recibos' => $lista,
            ]);

        } catch (\Exception $e) {
            return $this->handleExceptionWithContext(
                $e,
                'Error al obtener contador',
                ['context' => 'contar costura pendiente']
            );
        }
    }

    /**
     * Marcar un recibo como visto por el usuario actual
     * Endpoint: POST /insumos/api/recibo/{id}/marcar-visto
     */
    public function marcarReciboVisto($id)
    {
        try {
            $user = Auth::user();


            // Delegar al servicio
            $resultado = $this->materialesService->marcarReciboVisto($id, $user->id);
            
            return response()->json($resultado);

        } catch (\Exception $e) {
            return $this->handleExceptionWithContext(
                $e,
                'Error al marcar recibo como visto',
                ['recibo_id' => $id]
            );
        }
    }

    /**
     * Guardar observaciones de un material
     * POST /insumos/guardar-observaciones
     */
    public function guardarObservaciones(Request $request)
    {
        // Validar datos
        $validated = $request->validate([
            'numero_pedido' => 'required|string',
            'nombre_material' => 'required|string',
            'observaciones' => 'nullable|string|max:5000',
        ]);

        try {
            // Buscar el registro en materiales_orden_insumos
            $material = MaterialesOrdenInsumos::where('numero_pedido', $validated['numero_pedido'])
                ->where('nombre_material', $validated['nombre_material'])
                ->first();

            if (!$material) {
                return response()->json([
                    'success' => false,
                    'error' => 'Material no encontrado',
                ], 404);
            }

            // Actualizar observaciones
            $material->update([
                'observaciones' => $validated['observaciones'],
            ]);

            Log::info("Observaciones guardadas para material: {$validated['nombre_material']} del pedido {$validated['numero_pedido']}");

            return response()->json([
                'success' => true,
                'message' => 'Observaciones guardadas exitosamente',
                'material_id' => $material->id,
                'observaciones' => $material->observaciones,
            ]);
        } catch (\Exception $e) {
            Log::error('Error guardando observaciones: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error al guardar observaciones',
                'message' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
