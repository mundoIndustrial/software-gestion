<?php

namespace App\Infrastructure\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Constants\AreaOptions;
use Illuminate\Http\Request;
use App\Models\PedidoProduccion;
use App\Models\Festivo;
use App\Services\FestivosColombiaService;

// New DDD QueryHandlers
use App\Application\RegistrosOrdenes\QueryHandlers\ListarOrdenesQueryHandler;
use App\Application\RegistrosOrdenes\QueryHandlers\ObtenerOrdenDetalleQueryHandler;
use App\Application\RegistrosOrdenes\QueryHandlers\ObtenerSeguimientoPrendaQueryHandler;
use App\Application\RegistrosOrdenes\QueryHandlers\ObtenerImagenesOrdenQueryHandler;

/**
 * RegistroOrdenQueryController
 * 
 * REFACTORIZADO CON DDD+SOLID
 * Responsabilidad única: HTTP adapter que delega a QueryHandlers
 */
class RegistroOrdenQueryController extends Controller
{
    use RegistroOrdenExceptionHandler;

    public function __construct(
        private ListarOrdenesQueryHandler $listarOrdenesHandler,
        private ObtenerOrdenDetalleQueryHandler $obtenerDetalleHandler,
        private ObtenerSeguimientoPrendaQueryHandler $obtenerSeguimientoHandler,
        private ObtenerImagenesOrdenQueryHandler $obtenerImagenesHandler,
    ) {}

    /**
     * Listar órdenes con paginación, búsqueda y filtros
     * GET /registros
     */
    public function index(Request $request)
    {
        try {
            // Valores únicos para filtros
            if ($request->has('get_unique_values') && $request->has('column')) {
                return response()->json(['unique_values' => []]);
            }

            $resultado = $this->listarOrdenesHandler->handle($request);
            $areaOptions = AreaOptions::getArray();
            $userRole = auth()->user()?->role?->name;

            if ($request->wantsJson()) {
                return response()->json([
                    'orders' => $resultado['orders'],
                    'totalDiasCalculados' => $resultado['totalDiasCalculados'],
                    'areaOptions' => $areaOptions,
                    'context' => 'registros',
                    'userRole' => $userRole,
                    'pagination' => $resultado['pagination'],
                    'pagination_html' => '',
                ]);
            }

            $ordenes = new \Illuminate\Pagination\LengthAwarePaginator(
                $resultado['orders'],
                $resultado['pagination']['total'],
                $resultado['pagination']['per_page'],
                $resultado['pagination']['current_page'],
                ['path' => $request->url()]
            );

            return view('orders.index', array_merge(
                compact('ordenes', 'resultado', 'areaOptions'),
                [
                    'context' => 'registros',
                    'title' => 'Registro de Órdenes',
                    'icon' => 'fa-clipboard-list',
                    'fetchUrl' => '/registros',
                    'updateUrl' => '/registros',
                    'modalContext' => 'orden'
                ]
            ));

        } catch (\Exception $e) {
            \Log::error('Error en index: ' . $e->getMessage());
            return response()->json(['error' => 'Error al listar órdenes'], 500);
        }
    }

    /**
     * Obtener orden específica
     * GET /registros/{pedido}
     */
    public function show($pedido)
    {
        try {
            $orderArray = $this->obtenerDetalleHandler->handle($pedido);
            return response()->json($orderArray);
        } catch (\Exception $e) {
            \Log::error('Error en show: ' . $e->getMessage());
            return response()->json(['error' => 'Orden no encontrada'], 404);
        }
    }

    /**
     * Obtener seguimiento por prenda
     * GET /registros/{pedido}/seguimiento-prenda
     */
    public function getSeguimientoPorPrenda($pedido)
    {
        try {
            $resultado = $this->obtenerSeguimientoHandler->handle($pedido);
            return response()->json($resultado);
        } catch (\Exception $e) {
            \Log::error('Error en getSeguimientoPorPrenda: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener seguimiento'], 500);
        }
    }

    /**
     * Obtener imágenes de orden
     * GET /registros/{pedido}/images
     */
    public function getOrderImages($pedido)
    {
        try {
            $tipo = request()->query('tipo');
            $resultado = $this->obtenerImagenesHandler->handle($pedido, $tipo);
            return response()->json($resultado);
        } catch (\Exception $e) {
            \Log::error('Error en getOrderImages: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener imágenes'], 500);
        }
    }

    /**
     * Obtener consecutivo de costura
     * GET /registros/{pedido}/consecutivo-costura
     */
    public function getConsecutivoCostura($pedido)
    {
        try {
            $prendaId = request()->query('prenda_id');

            $pedidoModel = is_numeric($pedido)
                ? PedidoProduccion::find($pedido)
                : PedidoProduccion::where('numero_pedido', $pedido)->first();

            if (!$pedidoModel) {
                return response()->json(['error' => 'Orden no encontrada'], 404);
            }

            $numeroPedido = (int) $pedidoModel->numero_pedido;

            $query = \DB::table('consecutivos_recibos_pedidos')
                ->where('pedido_produccion_id', $pedidoModel->id)
                ->where('tipo_recibo', 'COSTURA')
                ->where('activo', 1);

            if ($prendaId) {
                $query->where('prenda_id', $prendaId);
            }

            $registro = $query->orderByDesc('id')->first();

            if (!$registro) {
                return response()->json([
                    'success' => false,
                    'consecutivo' => null,
                    'message' => 'No se encontró consecutivo de costura'
                ]);
            }

            return response()->json([
                'success' => true,
                'consecutivo' => $registro->consecutivo_actual,
                'area' => $registro->area ?? null,
                'prenda_id' => $registro->prenda_id,
                'tipo' => $registro->tipo_recibo,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en getConsecutivoCostura: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener consecutivo'], 500);
        }
    }

    /**
     * Obtener novedades
     * GET /registros/{id}/novedades
     */
    public function getNovedades($id)
    {
        try {
            $pedido = PedidoProduccion::where('numero_pedido', $id)->firstOrFail();
            return response()->json(['novedades' => $pedido->novedades ?? '']);
        } catch (\Exception $e) {
            \Log::error('Error en getNovedades: ' . $e->getMessage());
            return response()->json(['error' => 'Pedido no encontrado'], 404);
        }
    }

    /**
     * Calcular días de una orden
     * GET /registros/{pedido}/calcular-dias
     */
    public function calcularDiasAPI(Request $request, $numeroPedido)
    {
        try {
            if (!$numeroPedido) {
                return response()->json(['error' => 'Número de pedido requerido'], 400);
            }

            $festivos = Festivo::pluck('fecha')->toArray();
            $orden = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();

            if (!$orden) {
                return response()->json(['error' => 'Orden no encontrada'], 404);
            }

            $resultado = \App\Services\CacheCalculosService::getTotalDiasBatch([$orden], $festivos);
            $diasCalculados = $resultado[$numeroPedido] ?? 0;

            return response()->json([
                'success' => true,
                'numero_pedido' => $numeroPedido,
                'total_dias' => intval($diasCalculados),
                'timestamp' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en calcularDiasAPI: ' . $e->getMessage());
            return response()->json(['error' => 'Error al calcular días'], 500);
        }
    }

    /**
     * Calcular dias batch
     * POST /registros/calcular-dias-batch
     */
    public function calcularDiasBatchAPI(Request $request)
    {
        try {
            $numeroPedidos = $request->input('numero_pedidos', []);
            if (empty($numeroPedidos)) {
                return response()->json(['error' => 'Lista de pedidos requerida'], 400);
            }

            $festivos = Festivo::pluck('fecha')->toArray();
            $ordenes = PedidoProduccion::whereIn('numero_pedido', $numeroPedidos)->get();

            if ($ordenes->isEmpty()) {
                return response()->json(['error' => 'No se encontraron órdenes'], 404);
            }

            $resultados = \App\Services\CacheCalculosService::getTotalDiasBatch($ordenes->toArray(), $festivos);

            $dias = [];
            foreach ($numeroPedidos as $pedido) {
                $dias[$pedido] = intval($resultados[$pedido] ?? 0);
            }

            return response()->json([
                'success' => true,
                'dias' => $dias,
                'total' => count($dias),
                'timestamp' => now()->toIso8601String()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en calcularDiasBatchAPI: ' . $e->getMessage());
            return response()->json(['error' => 'Error al calcular días'], 500);
        }
    }

    /**
     * Calcular fecha estimada
     * POST /api/registros/{id}/calcular-fecha-estimada
     */
    public function calcularFechaEstimada(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'dia_de_entrega' => 'required|integer|min:1'
            ]);

            $orden = PedidoProduccion::findOrFail($id);

            if (!$orden->fecha_de_creacion_de_orden) {
                return response()->json([
                    'success' => false,
                    'message' => 'La orden no tiene fecha de creación'
                ], 400);
            }

            $orden->dia_de_entrega = $validated['dia_de_entrega'];
            $fechaEstimada = $orden->calcularFechaEstimada();

            if (!$fechaEstimada) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo calcular la fecha estimada'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'fecha_estimada' => $fechaEstimada->format('d/m/Y'),
                'fecha_estimada_iso' => $fechaEstimada->toIso8601String(),
                'dias' => $validated['dia_de_entrega'],
                'fecha_creacion' => $orden->fecha_de_creacion_de_orden->format('d/m/Y')
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validación fallida',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error en calcularFechaEstimada: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al calcular la fecha estimada'
            ], 500);
        }
    }

    /**
     * Obtener descripción de prendas
     * GET /registros/{pedido}/descripcion-prendas
     */
    public function getDescripcionPrendas($pedido)
    {
        try {
            $orden = PedidoProduccion::where('numero_pedido', $pedido)
                ->orWhere('id', $pedido)
                ->first();

            if (!$orden) {
                return response()->json([
                    'success' => false,
                    'message' => 'Orden no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'descripcion_prendas' => $orden->descripcion_prendas ?? '',
                'numero_pedido' => $orden->numero_pedido,
                'orden_id' => $orden->id
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al obtener descripción de prendas: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener descripción de prendas'
            ], 500);
        }
    }

    /**
     * Stub: Methods moved to RegistroOrdenController (CRUD)
     */
    public function getNextPedido()
    {
        throw new \BadMethodCallException('Use RegistroOrdenController::getNextPedido()');
    }

    public function validatePedido(Request $request)
    {
        throw new \BadMethodCallException('Use RegistroOrdenController::validatePedido()');
    }
}
