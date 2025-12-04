<?php

namespace App\Http\Controllers;

use App\Models\PedidoProduccion;
use App\Services\PedidoEstadoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PedidoEstadoController extends Controller
{
    public function __construct(
        private PedidoEstadoService $estadoService
    ) {}

    /**
     * Aprobar pedido como supervisor
     * PENDIENTE_SUPERVISOR → APROBADO_SUPERVISOR (asigna número vía Job)
     * POST /pedidos/{id}/aprobar-supervisor
     */
    public function aprobarSupervisor(Request $request, PedidoProduccion $pedido): JsonResponse
    {
        try {
            // Validar que sea Supervisor_Pedidos
            $this->authorize('isSupervisorPedidos'); // TODO: Implementar gate

            $this->estadoService->aprobarComoSupervisor($pedido);

            return response()->json([
                'success' => true,
                'message' => 'Pedido aprobado por supervisor. Se está asignando número...',
                'pedido' => [
                    'id' => $pedido->id,
                    'estado' => $pedido->estado,
                    'numero_pedido' => $pedido->numero_pedido,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Obtener historial de cambios de estado
     * GET /pedidos/{id}/historial
     */
    public function historial(PedidoProduccion $pedido): JsonResponse
    {
        try {
            $historial = $this->estadoService->obtenerHistorial($pedido);

            return response()->json([
                'success' => true,
                'data' => $historial->map(fn($cambio) => [
                    'id' => $cambio->id,
                    'estado_anterior' => $cambio->estado_anterior,
                    'estado_nuevo' => $cambio->estado_nuevo,
                    'usuario_nombre' => $cambio->usuario_nombre,
                    'rol_usuario' => $cambio->rol_usuario,
                    'razon_cambio' => $cambio->razon_cambio,
                    'fecha' => $cambio->created_at->format('Y-m-d H:i:s'),
                    'datos_adicionales' => $cambio->datos_adicionales,
                ])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Obtener seguimiento de pedido (para Asesor y otros roles)
     * GET /pedidos/{id}/seguimiento
     */
    public function seguimiento(Request $request, PedidoProduccion $pedido): JsonResponse
    {
        try {
            // Validar que sea la asesor dueña o un rol permitido
            if ($pedido->asesor_id !== $request->user()->id && !$request->user()->hasRole(['supervisor_pedidos', 'produccion', 'admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para ver el seguimiento'
                ], 403);
            }

            $estadoEnum = \App\Enums\EstadoPedido::tryFrom($pedido->estado);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $pedido->id,
                    'numero_pedido' => $pedido->numero_pedido ?? 'Por asignar',
                    'numero_cotizacion' => $pedido->numero_cotizacion,
                    'cliente' => $pedido->cliente,
                    'estado' => $pedido->estado,
                    'estado_label' => $estadoEnum?->label() ?? 'Desconocido',
                    'estado_color' => $estadoEnum?->color() ?? 'gray',
                    'estado_icono' => $estadoEnum?->icon() ?? 'question',
                    'fecha_creacion' => $pedido->fecha_de_creacion_de_orden?->format('Y-m-d H:i:s'),
                    'aprobado_por_supervisor_en' => $pedido->aprobado_por_supervisor_en?->format('Y-m-d H:i:s'),
                    'fecha_estimada_entrega' => $pedido->fecha_estimada_de_entrega?->format('Y-m-d'),
                    'area_actual' => $pedido->getAreaActual(),
                    'historial' => $this->estadoService->obtenerHistorial($pedido)->map(fn($cambio) => [
                        'estado_anterior' => $cambio->estado_anterior,
                        'estado_nuevo' => $cambio->estado_nuevo,
                        'usuario_nombre' => $cambio->usuario_nombre,
                        'fecha' => $cambio->created_at->format('Y-m-d H:i:s'),
                    ])
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
