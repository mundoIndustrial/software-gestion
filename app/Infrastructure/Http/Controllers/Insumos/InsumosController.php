<?php

namespace App\Infrastructure\Http\Controllers\Insumos;

use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Controllers\Traits\HandlesExceptions;
use App\Infrastructure\Http\Controllers\Traits\CalculateWorkingDays;
use App\Application\Insumos\UseCases\EliminarMaterialPorNombreUseCase;
use App\Application\Insumos\UseCases\GuardarMaterialesDetalladosUseCase;
use App\Application\Insumos\UseCases\GuardarObservacionesMaterialUseCase;
use App\Application\Insumos\UseCases\MarcarNotificacionesInsumosLeidasUseCase;
use App\Application\Insumos\UseCases\MarcarReciboVistoInsumosUseCase;
use App\Application\Insumos\UseCases\ObtenerRecibosCosturaPendientesInsumosUseCase;
use App\Application\Insumos\UseCases\ObtenerResumenRecibosPendientesInsumosUseCase;
use App\Application\Insumos\UseCases\ObtenerMaterialesPedidoUseCase;
use App\Application\Insumos\UseCases\ObtenerOpcionesFiltroInsumosUseCase;
use App\Application\Insumos\UseCases\ObtenerPrendasPedidoInsumosUseCase;
use App\Application\Insumos\UseCases\ObtenerReciboPrendaInsumosUseCase;
use App\Application\Insumos\UseCases\CambiarEstadoReciboInsumosUseCase;
use App\Application\Insumos\UseCases\CambiarEstadoPedidoInsumosUseCase;
use App\Application\Insumos\UseCases\EliminarAnchoMetrajePrendaInsumosUseCase;
use App\Application\Insumos\UseCases\GuardarAnchoMetrajePrendaInsumosUseCase;
use App\Application\Insumos\UseCases\ObtenerAnchoMetrajePrendaInsumosUseCase;
use App\Application\Insumos\UseCases\ObtenerColoresPrendaInsumosUseCase;
use App\Application\Insumos\Services\RecibosQueryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Requests\Insumos\GuardarAnchoMetrajeRequest;

class InsumosController extends Controller
{
    use HandlesExceptions;
    use CalculateWorkingDays;

    protected $obtenerOpcionesFiltroUseCase;
    protected $guardarMaterialesDetalladosUseCase;
    protected $eliminarMaterialPorNombreUseCase;
    protected $obtenerMaterialesPedidoUseCase;
    protected $marcarNotificacionesLeidasUseCase;
    protected $obtenerPrendasPedidoUseCase;
    protected $obtenerReciboPrendaUseCase;
    protected $guardarObservacionesMaterialUseCase;
    protected $cambiarEstadoReciboUseCase;
    protected $obtenerResumenRecibosPendientesUseCase;
    protected $obtenerRecibosCosturaPendientesUseCase;
    protected $marcarReciboVistoUseCase;
    protected $cambiarEstadoPedidoUseCase;
    protected $obtenerColoresPrendaUseCase;
    protected $obtenerAnchoMetrajePrendaUseCase;
    protected $guardarAnchoMetrajePrendaUseCase;
    protected $eliminarAnchoMetrajePrendaUseCase;
    protected $recibosQueryService;

    public function __construct(
        ObtenerOpcionesFiltroInsumosUseCase $obtenerOpcionesFiltroUseCase,
        GuardarMaterialesDetalladosUseCase $guardarMaterialesDetalladosUseCase,
        EliminarMaterialPorNombreUseCase $eliminarMaterialPorNombreUseCase,
        ObtenerMaterialesPedidoUseCase $obtenerMaterialesPedidoUseCase,
        MarcarNotificacionesInsumosLeidasUseCase $marcarNotificacionesLeidasUseCase,
        ObtenerPrendasPedidoInsumosUseCase $obtenerPrendasPedidoUseCase,
        ObtenerReciboPrendaInsumosUseCase $obtenerReciboPrendaUseCase,
        GuardarObservacionesMaterialUseCase $guardarObservacionesMaterialUseCase,
        CambiarEstadoReciboInsumosUseCase $cambiarEstadoReciboUseCase,
        ObtenerResumenRecibosPendientesInsumosUseCase $obtenerResumenRecibosPendientesUseCase,
        ObtenerRecibosCosturaPendientesInsumosUseCase $obtenerRecibosCosturaPendientesUseCase,
        MarcarReciboVistoInsumosUseCase $marcarReciboVistoUseCase,
        CambiarEstadoPedidoInsumosUseCase $cambiarEstadoPedidoUseCase,
        ObtenerColoresPrendaInsumosUseCase $obtenerColoresPrendaUseCase,
        ObtenerAnchoMetrajePrendaInsumosUseCase $obtenerAnchoMetrajePrendaUseCase,
        GuardarAnchoMetrajePrendaInsumosUseCase $guardarAnchoMetrajePrendaUseCase,
        EliminarAnchoMetrajePrendaInsumosUseCase $eliminarAnchoMetrajePrendaUseCase,
        RecibosQueryService $recibosQueryService
    ) {
        $this->obtenerOpcionesFiltroUseCase = $obtenerOpcionesFiltroUseCase;
        $this->guardarMaterialesDetalladosUseCase = $guardarMaterialesDetalladosUseCase;
        $this->eliminarMaterialPorNombreUseCase = $eliminarMaterialPorNombreUseCase;
        $this->obtenerMaterialesPedidoUseCase = $obtenerMaterialesPedidoUseCase;
        $this->marcarNotificacionesLeidasUseCase = $marcarNotificacionesLeidasUseCase;
        $this->obtenerPrendasPedidoUseCase = $obtenerPrendasPedidoUseCase;
        $this->obtenerReciboPrendaUseCase = $obtenerReciboPrendaUseCase;
        $this->guardarObservacionesMaterialUseCase = $guardarObservacionesMaterialUseCase;
        $this->cambiarEstadoReciboUseCase = $cambiarEstadoReciboUseCase;
        $this->obtenerResumenRecibosPendientesUseCase = $obtenerResumenRecibosPendientesUseCase;
        $this->obtenerRecibosCosturaPendientesUseCase = $obtenerRecibosCosturaPendientesUseCase;
        $this->marcarReciboVistoUseCase = $marcarReciboVistoUseCase;
        $this->cambiarEstadoPedidoUseCase = $cambiarEstadoPedidoUseCase;
        $this->obtenerColoresPrendaUseCase = $obtenerColoresPrendaUseCase;
        $this->obtenerAnchoMetrajePrendaUseCase = $obtenerAnchoMetrajePrendaUseCase;
        $this->guardarAnchoMetrajePrendaUseCase = $guardarAnchoMetrajePrendaUseCase;
        $this->eliminarAnchoMetrajePrendaUseCase = $eliminarAnchoMetrajePrendaUseCase;
        $this->recibosQueryService = $recibosQueryService;
    }

    /**
     * Obtener valores únicos de una columna para filtros
     * Soporta búsqueda opcional con parámetro ?search=
     * Soporta tipo de recibo opcional con parámetro ?tipo_recibo=
     */
    public function obtenerValoresFiltro(Request $request, $column)
    {
        try {
            $searchTerm = $request->query('search', null);
            $tipoRecibo = $request->query('tipo_recibo', 'COSTURA'); // Por defecto COSTURA
            $resultado = $this->obtenerOpcionesFiltroUseCase->execute($column, $searchTerm, $tipoRecibo);
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
            $traceId = (string) $request->header('X-Insumos-Trace-Id', '');
            $search = (string) $request->get('search', '');
            $filterColumns = (array) $request->get('filter_columns', []);
            $filterValues = (array) $request->get('filter_values', []);

            Log::info(' InsumosController.materiales() INICIADO', [
                'url' => $request->fullUrl(),
                'user_id' => Auth::id(),
                'user_name' => Auth::user()?->name ?? 'unknown',
                'is_ajax' => $request->header('X-Requested-With') === 'XMLHttpRequest',
                'trace_id' => $traceId,
                'search' => $search,
                'filter_columns' => $filterColumns,
                'filter_values' => $filterValues,
            ]);

            $user = Auth::user();
            
            if (!$user) {
                Log::error(' No hay usuario autenticado en materiales()');
                return redirect('/login');
            }

            Log::info(' Llamando a recibosQueryService.obtenerRecibosConPaginacion()');
            
            // TODA la lógica de query/filtrado/paginación está en el servicio
            $ordenes = $this->recibosQueryService->obtenerRecibosConPaginacion(
                $request,
                fn($fecha) => $this->calcularDiasHabiles($fecha),
                'COSTURA',
                'insumos.materiales.index'
            );

            Log::info(' Recibos obtenidos exitosamente', [
                'trace_id' => $traceId,
                'items_current_page' => count($ordenes),
                'current_page' => $ordenes->currentPage() ?? 'N/A',
                'total' => method_exists($ordenes, 'total') ? $ordenes->total() : null,
                'sample_consecutivos' => collect($ordenes->items())->map(fn($row) => $row->consecutivo_actual ?? null)->filter()->take(10)->values()->all(),
            ]);
            
            // Si es una petición AJAX, retornar solo la tabla HTML
            if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
                Log::info(' Retornando respuesta AJAX (solo tabla)', [
                    'trace_id' => $traceId,
                ]);
                return view('insumos.materiales.table-partial', [
                    'ordenes' => $ordenes,
                    'user' => $user,
                    'search' => $search,
                    'esGestionReflectivo' => false,
                ])->render();
            }
            
            // Si no es AJAX, retornar la vista completa
            return view('insumos.materiales.index', [
                'ordenes' => $ordenes,
                'user' => $user,
                'search' => $search,
            ]);
        } catch (\Exception $e) {
            Log::error(' ERROR en InsumosController.materiales()', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'url' => $request->fullUrl(),
                'trace_id' => $request->header('X-Insumos-Trace-Id'),
            ]);
            
            // Si es AJAX, retornar error JSON
            if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al obtener recibos: ' . $e->getMessage()
                ], 500);
            }
            
            return $this->handleException($e, 'obtener recibos de costura');
        }
    }

    /**
     * Gestion de reflectivo usando la misma vista de materiales.
     */
    public function materialesReflectivo(Request $request)
    {
        try {
            Log::info(' InsumosController.materialesReflectivo() INICIADO', [
                'url' => $request->fullUrl(),
                'user_id' => Auth::id(),
                'user_name' => Auth::user()?->name ?? 'unknown',
                'is_ajax' => $request->header('X-Requested-With') === 'XMLHttpRequest',
            ]);

            $user = Auth::user();

            if (!$user) {
                Log::error(' No hay usuario autenticado en materialesReflectivo()');
                return redirect('/login');
            }

            $ordenes = $this->recibosQueryService->obtenerRecibosConPaginacion(
                $request,
                fn($fecha) => $this->calcularDiasHabiles($fecha),
                'REFLECTIVO',
                'insumos.materiales.reflectivo'
            );

            if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
                return view('insumos.materiales.table-partial', [
                    'ordenes' => $ordenes,
                    'user' => $user,
                    'search' => $request->get('search', ''),
                    'esGestionReflectivo' => true,
                    'mostrarSoloVerRecibo' => true,
                ])->render();
            }

            return view('insumos.materiales.index', [
                'ordenes' => $ordenes,
                'user' => $user,
                'search' => $request->get('search', ''),
                'esGestionReflectivo' => true,
                'mostrarSoloVerRecibo' => true,
            ]);
        } catch (\Exception $e) {
            Log::error(' ERROR en InsumosController.materialesReflectivo()', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'url' => $request->fullUrl(),
            ]);

            if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al obtener recibos reflectivo: ' . $e->getMessage()
                ], 500);
            }

            return $this->handleException($e, 'obtener recibos de reflectivo');
        }
    }

    /**
     * Guardar materiales de una orden
     */
    public function guardarMateriales(Request $request, $ordenId)
    {
        try {
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

            $resultado = $this->guardarMaterialesDetalladosUseCase->execute(
                (string) $ordenId,
                $validated['materiales'] ?? [],
                $validated['prenda_id'] ?? null
            );

            return response()->json($resultado, 200);
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
            $validated = $request->validate([
                'nombre_material' => 'required|string',
                'prenda_id' => 'nullable|integer|exists:prendas_pedido,id',
            ]);

            $resultado = $this->eliminarMaterialPorNombreUseCase->execute(
                (string) $ordenId,
                $validated['nombre_material'],
                $validated['prenda_id'] ?? null
            );

            return response()->json($resultado, $resultado['success'] ? 200 : 404);
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
            $resultado = $this->obtenerMaterialesPedidoUseCase->execute(
                (string) $pedido,
                request('prenda_id') ? (int) request('prenda_id') : null
            );

            return response()->json($resultado);
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

            return response()->json(
                $this->marcarNotificacionesLeidasUseCase->execute((int) $user->id)
            );
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
            // Validar datos
            $validated = $request->validate([
                'estado' => [
                    'required',
                    'string',
                    Rule::in(['No iniciado', 'En Ejecución', 'PENDIENTE_INSUMOS'])
                ],
            ]);
            
            return response()->json(
                $this->cambiarEstadoPedidoUseCase->execute((string) $numeroPedido, $validated['estado'])
            );
        } catch (\Exception $e) {
            return $this->handleExceptionWithContext(
                $e,
                'Error al cambiar estado'
            );
        }
    }

    /**
     * Cambiar estado de un recibo individual (consecutivos_recibos_pedidos)
     */
    public function cambiarEstadoRecibo(Request $request, $reciboId)
    {
        try {
            $user = Auth::user();
            $userRole = $user->role->name ?? 'guest';
            
            // Determinar los estados permitidos según el rol
            $estadosPermitidos = [
                'No iniciado',
                'En Ejecución', 
                'PENDIENTE_INSUMOS',
                'Pendiente_Insumos',
                'Pendiente Tela',
                'PENDIENTE_TELA',
                'Pendiente Plotter',
                'PENDIENTE_PLOTTER',
                'Insumos Pedidos',
                'INSUMOS_PEDIDOS',
                'DEVUELTO_ASESOR',
                'Devuelto_Asesor',
                'COSTURA',
                'ESTAMPADO',
                'BORDADO',
                'REFLECTIVO',
                'DTF',
                'SUBLIMADO',
                'COSTURA-BODEGA',
                'Anulada'
            ];
            
            // Si es rol "insumos", solo permite Pendiente Insumos e Insumos Pedidos
            if ($userRole === 'insumos') {
                $estadosPermitidos = [
                    'En Ejecución',
                    'PENDIENTE_INSUMOS',
                    'Pendiente_Insumos',
                    'Pendiente Tela',
                    'PENDIENTE_TELA',
                    'Pendiente Plotter',
                    'PENDIENTE_PLOTTER',
                    'Insumos Pedidos',
                    'INSUMOS_PEDIDOS'
                ];
            }
            
            $validated = $request->validate([
                'estado' => ['required', 'string', Rule::in($estadosPermitidos)],
            ]);
            
            $resultado = $this->cambiarEstadoReciboUseCase->execute((int) $reciboId, $validated['estado']);
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
            return response()->json(
                $this->obtenerPrendasPedidoUseCase->execute((string) $numeroPedido)
            );
            
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
     * Lógica:
     * 1. Si prenda_pedido_talla_colores tiene registros → MODO TALLA-COLOR (matriz)
     * 2. Else si prenda_pedido_colores_telas tiene registros → MODO PIEZAS (múltiples telas/colores)
     * 3. Else → MODO NORMAL (una sola tela)
     */
    public function obtenerColoresPrenda($numeroPedido, $prendaId)
    {
        try {
            return response()->json(
                $this->obtenerColoresPrendaUseCase->execute((string) $numeroPedido, (int) $prendaId)
            );
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
            return response()->json(
                $this->obtenerAnchoMetrajePrendaUseCase->execute((string) $numeroPedido, (int) $prendaId)
            );
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
            $validated = $request->validated();

            return response()->json(
                $this->guardarAnchoMetrajePrendaUseCase->execute(
                    (string) $numeroPedido,
                    (int) $validated['prenda_pedido_id'],
                    $validated
                )
            );
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
            // Validar datos
            $validated = $request->validate([
                'prenda_id' => 'required|integer|exists:prendas_pedido,id'
            ]);

            return response()->json(
                $this->eliminarAnchoMetrajePrendaUseCase->execute(
                    (string) $numeroPedido,
                    (int) $validated['prenda_id']
                )
            );
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
            return response()->json(
                $this->obtenerReciboPrendaUseCase->execute((string) $numeroPedido, (int) $prendaId)
            );
            
        } catch (\Exception $e) {
            \Log::error('Error al obtener recibo de prenda: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener recibo de prenda'
            ], 500);
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


            return response()->json(
                $this->obtenerResumenRecibosPendientesUseCase->execute((int) $user->id)
            );

        } catch (\Exception $e) {
            return $this->handleExceptionWithContext(
                $e,
                'Error al obtener contador',
                ['context' => 'contar costura pendiente']
            );
        }
    }

    /**
     * Obtener TODOS los recibos de costura en estado PENDIENTE_INSUMOS
     * Endpoint: GET /insumos/api/recibos-costura-pendiente
     * Retorna: JSON con listado completo de recibos
     */
    public function obtenerRecibosCosTuraPendiente()
    {
        try {
            return response()->json(
                $this->obtenerRecibosCosturaPendientesUseCase->execute()
            );
        } catch (\Exception $e) {
            return $this->handleExceptionWithContext(
                $e,
                'Error al obtener recibos de costura pendiente',
                ['context' => 'obtener-recibos-costura-pendiente']
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
            $resultado = $this->marcarReciboVistoUseCase->execute((int) $id, (int) $user->id);
            
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
        $validated = $request->validate([
            'numero_pedido' => 'required|string',
            'nombre_material' => 'required|string',
            'observaciones' => 'nullable|string|max:5000',
        ]);

        try {
            $resultado = $this->guardarObservacionesMaterialUseCase->execute(
                $validated['numero_pedido'],
                $validated['nombre_material'],
                $validated['observaciones'] ?? null
            );

            if (!$resultado['success']) {
                return response()->json($resultado, 404);
            }

            Log::info("Observaciones guardadas para material: {$validated['nombre_material']} del pedido {$validated['numero_pedido']}");

            return response()->json($resultado);
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
