<?php

namespace App\Infrastructure\Http\Controllers\Insumos;

use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Controllers\Traits\HandlesExceptions;
use App\Infrastructure\Http\Controllers\Traits\CalculateWorkingDays;
use App\Services\Insumos\MaterialesService;
use App\Services\Insumos\RecibosQueryService;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

            $resultado = $this->materialesService->guardarMaterialesDetallados(
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

            $resultado = $this->materialesService->eliminarMaterialPorNombre(
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
            $resultado = $this->materialesService->obtenerMaterialesPedido(
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
                $this->materialesService->marcarTodasNotificacionesLeidas((int) $user->id)
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
            return response()->json(
                $this->materialesService->obtenerPrendasPedido((string) $numeroPedido)
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
            return response()->json(
                $this->materialesService->obtenerReciboPrenda((string) $numeroPedido, (int) $prendaId)
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
        $validated = $request->validate([
            'numero_pedido' => 'required|string',
            'nombre_material' => 'required|string',
            'observaciones' => 'nullable|string|max:5000',
        ]);

        try {
            $resultado = $this->materialesService->guardarObservaciones(
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
