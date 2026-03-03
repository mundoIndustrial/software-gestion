<?php

namespace App\Infrastructure\Insumos\Controllers;

use App\Application\Insumos\DTOs\GuardarMaterialesDTO;
use App\Application\Insumos\UseCases\GuardarMaterialesUseCase;
use App\Application\Insumos\DTOs\CambiarEstadoReciboDTO;
use App\Application\Insumos\UseCases\CambiarEstadoReciboUseCase;
use App\Application\Insumos\UseCases\ObtenerRecibosConFiltrosUseCase;
use App\Domain\Insumos\Services\CalcularDiasDemoraService;
use App\Http\Controllers\Controller;
use App\Infrastructure\Insumos\InsumosService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Controller Thin para Insumos
 * Delega toda la lógica a InsumosService (Infrastructure)
 * 
 * Responsabilidades del Controller:
 * - Recibir Request HTTP
 * - Delegación a Service/UseCase
 * - Retornar Response HTTP
 * 
 * Responsabilidades de InsumosService (Infrastructure):
 * - Toda la lógica de negocio e infraestructura
 */
class InsumosController extends Controller
{
    public function __construct(
        private InsumosService $insumosService,
        private CalcularDiasDemoraService $calcularDiasDemoraService,
        private GuardarMaterialesUseCase $guardarMaterialesUseCase,
        private CambiarEstadoReciboUseCase $cambiarEstadoReciboUseCase,
        private ObtenerRecibosConFiltrosUseCase $obtenerRecibosConFiltrosUseCase
    ) {}
    
    /**
     * Dashboard del rol insumos
     */
    public function dashboard()
    {
        return $this->insumosService->dashboard();
    }
    
    /**
     * Obtener valores únicos de una columna para filtros
     */
    public function obtenerValoresFiltro($column)
    {
        $resultado = $this->obtenerRecibosConFiltrosUseCase->obtenerValoresColumna($column);

        if (!$resultado->success) {
            return response()->json([
                'success' => false,
                'message' => $resultado->mensaje,
                'column' => $column,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'column' => $column,
            'valores' => $resultado->valores,
            'total' => count($resultado->valores),
        ]);
    }
    
    /**
     * Obtener recibos con filtros, búsqueda y paginación (API)
     */
    public function obtenerRecibosConFiltros(Request $request)
    {
        $filtros = $request->get('filtros', []);
        $busqueda = $request->get('busqueda', '');
        $perPage = (int) $request->get('per_page', 10);
        $page = (int) $request->get('page', 1);
        
        $resultado = $this->obtenerRecibosConFiltrosUseCase->ejecutar($filtros, $busqueda, $perPage, $page);

        return response()->json($resultado->toArray());
    }
    
    /**
     * Control de materiales - Recibos de costura
     */
    public function materiales(Request $request)
    {
        return $this->insumosService->materiales($request);
    }
    
    /**
     * Guardar materiales de una orden
     */
    public function guardarMateriales(Request $request, $ordenId)
    {
        return $this->insumosService->guardarMateriales($request, $ordenId);
    }
    
    /**
     * Eliminar un material inmediatamente
     */
    public function eliminarMaterial(Request $request, $ordenId)
    {
        return $this->insumosService->eliminarMaterial($request, $ordenId);
    }
    
    /**
     * Obtener materiales de una orden (API)
     */
    public function obtenerMateriales($pedido)
    {
        return $this->insumosService->obtenerMateriales($pedido);
    }
    
    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllNotificationsAsRead()
    {
        return $this->insumosService->markAllNotificationsAsRead();
    }
    
    /**
     * Obtener ancho y metraje de un pedido
     */
    public function obtenerAnchoMetraje($numeroPedido)
    {
        return $this->insumosService->obtenerAnchoMetraje($numeroPedido);
    }
    
    /**
     * Cambiar estado de un pedido
     */
    public function cambiarEstado(Request $request, $numeroPedido)
    {
        return $this->insumosService->cambiarEstado($request, $numeroPedido);
    }
    
    /**
     * Cambiar estado de un recibo individual
     */
    public function cambiarEstadoRecibo(Request $request, $reciboId)
    {
        return $this->insumosService->cambiarEstadoRecibo($request, $reciboId);
    }
    
    /**
     * Guardar ancho y metraje de un pedido
     */
    public function guardarAnchoMetraje(Request $request, $numeroPedido)
    {
        return $this->insumosService->guardarAnchoMetraje($request, $numeroPedido);
    }
    
    /**
     * Obtener las prendas de un pedido
     */
    public function obtenerPrendas($numeroPedido)
    {
        return $this->insumosService->obtenerPrendas($numeroPedido);
    }
    
    /**
     * Obtener colores/telas disponibles para una prenda
     */
    public function obtenerColoresPrenda($numeroPedido, $prendaId)
    {
        return $this->insumosService->obtenerColoresPrenda($numeroPedido, $prendaId);
    }
    
    /**
     * Obtener ancho y metraje de una prenda específica
     */
    public function obtenerAnchoMetrajePrenda($numeroPedido, $prendaId)
    {
        return $this->insumosService->obtenerAnchoMetrajePrenda($numeroPedido, $prendaId);
    }
    
    /**
     * Guardar ancho general y/o metraje por color
     */
    public function guardarAnchoMetrajePrenda(Request $request, $numeroPedido)
    {
        return $this->insumosService->guardarAnchoMetrajePrenda($request, $numeroPedido);
    }
    
    /**
     * Obtener el número de recibo para una prenda
     */
    public function obtenerReciboPrenda($numeroPedido, $prendaId)
    {
        return $this->insumosService->obtenerReciboPrenda($numeroPedido, $prendaId);
    }
    
    /**
     * API: Calcular días de demora entre dos fechas
     * GET /insumos/api/calcular-demora?fecha_inicio=YYYY-MM-DD&fecha_fin=YYYY-MM-DD
     * 
     * Respuesta:
     * {
     *   "dias": 5,
     *   "color_clase": "bg-green-500",
     *   "texto_clase": "text-white",
     *   "nombre_color": "green"
     * }
     */
    public function calcularDemora(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'required|date_format:Y-m-d',
            'fecha_fin' => 'required|date_format:Y-m-d',
        ]);

        try {
            $fechaInicio = Carbon::createFromFormat('Y-m-d', $request->fecha_inicio);
            $fechaFin = Carbon::createFromFormat('Y-m-d', $request->fecha_fin);

            // Usar el Domain Service para calcular
            $dias = $this->calcularDiasDemoraService->calcular($fechaInicio, $fechaFin);
            
            // Obtener el color de demora
            $colorData = $this->calcularDiasDemoraService->calcularColorDemora($dias);

            return response()->json([
                'success' => true,
                'dias' => $dias,
                'color_bg' => $colorData['bg'],
                'color_text' => $colorData['text'],
                'color_nombre' => $colorData['nombre'],
                'mensaje' => "{$dias} días"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'dias' => 0,
                'color_bg' => 'bg-gray-100',
                'color_text' => 'text-gray-600'
            ], 400);
        }
    }

    /**
     * API: Guardar Materiales (refactorizado con UseCase)
     * POST /insumos/api/guardar-materiales/{numeroPedido}
     * 
     * Body JSON:
     * {
     *   "materiales": [
     *     {
     *       "nombre": "Botones",
     *       "fecha_pedido": "2026-03-01",
     *       "fecha_llegada": "2026-03-05",
     *       "recibido": true,
     *       "observaciones": "..."
     *     }
     *   ],
     *   "prenda_id": null
     * }
     * 
     * @param Request $request
     * @param string $numeroPedido
     * @return \Illuminate\Http\JsonResponse
     */
    public function guardarMaterialesAPI(Request $request, $numeroPedido)
    {
        try {
            // Validar entrada básica
            $request->validate([
                'materiales' => 'array',
                'materiales.*.nombre' => 'required|string',
                'materiales.*.fecha_orden' => 'nullable|date_format:Y-m-d',
                'materiales.*.fecha_pedido' => 'nullable|date_format:Y-m-d',
                'materiales.*.fecha_pago' => 'nullable|date_format:Y-m-d',
                'materiales.*.fecha_llegada' => 'nullable|date_format:Y-m-d',
                'materiales.*.fecha_despacho' => 'nullable|date_format:Y-m-d',
                'materiales.*.observaciones' => 'nullable|string',
                'materiales.*.recibido' => 'required|boolean',
                'prenda_id' => 'nullable|integer',
            ]);

            // Crear DTO
            $dto = GuardarMaterialesDTO::fromRequest(
                $numeroPedido,
                $request->all()
            );

            // Ejecutar UseCase
            $resultado = $this->guardarMaterialesUseCase->ejecutar($dto);

            return response()->json($resultado->toArray(), $resultado->success ? 200 : 400);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Cambiar Estado de Recibo a Producción (refactorizado con UseCase)
     * POST /insumos/api/cambiar-estado-recibo/{reciboId}
     * 
     * Body JSON (opcionales - se calcula el siguiente estado):
     * {
     *   "estado_actual": "PENDIENTE_INSUMOS",
     *   "estado_nuevo": "En Ejecución"  // Opcional, se sugiere el siguiente
     * }
     * 
     * Flujo de estados válidos:
     * - PENDIENTE_INSUMOS → En Ejecución
     * - En Ejecución → Completado
     * - Completado → Despachado
     * 
     * Respuesta JSON:
     * {
     *   "success": true,
     *   "message": "Recibo X cambió a 'En Ejecución' correctamente",
     *   "recibo_id": 123,
     *   "estado_anterior": "PENDIENTE_INSUMOS",
     *   "estado_nuevo": "En Ejecución"
     * }
     * 
     * @param Request $request
     * @param int $reciboId
     * @return \Illuminate\Http\JsonResponse
     */
    public function cambiarEstadoReciboAPI(Request $request, $reciboId)
    {
        try {
            // Validar entrada
            $request->validate([
                'estado_actual' => 'required|string',
                'estado_nuevo' => 'nullable|string',
            ]);

            // Crear DTO (con normalización y validación de transición)
            $dto = CambiarEstadoReciboDTO::fromRequest(
                (int) $reciboId,
                $request->input('estado_actual'),
                $request->input('estado_nuevo')
            );

            // Ejecutar UseCase
            $resultado = $this->cambiarEstadoReciboUseCase->ejecutar($dto);

            return response()->json($resultado->toArray(), $resultado->success ? 200 : 400);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage(),
            ], 500);
        }
    }
}

