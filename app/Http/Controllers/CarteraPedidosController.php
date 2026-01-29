<?php

namespace App\Http\Controllers;

use App\Models\PedidoProduccion;
use App\Application\Services\Asesores\ObtenerDatosFacturaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CarteraPedidosController extends Controller
{
    /**
     * Obtener pedidos pendientes de cartera
     */
    public function obtenerPedidos(Request $request)
    {
        try {
            // Estados que deben estar listos para cartera
            $estadosPendientes = ['pendiente_cartera'];
            
            // Usar PedidoProduccion modelo con la tabla correcta
            $pedidos = PedidoProduccion::whereIn('estado', $estadosPendientes)
                ->orderBy('fecha_de_creacion_de_orden', 'desc')
                ->get();
            
            // Mapear respuesta
            $data = $pedidos->map(function($pedido) {
                return [
                    'id' => $pedido->id,
                    'numero' => $pedido->numero_pedido,
                    'numero_pedido' => $pedido->numero_pedido,
                    'cliente_nombre' => $pedido->cliente,
                    'cliente' => $pedido->cliente,
                    'estado' => $pedido->estado,
                    'created_at' => $pedido->fecha_de_creacion_de_orden ?? $pedido->created_at
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en CarteraPedidosController::obtenerPedidos: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener pedidos: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Aprobar pedido
     */
    public function aprobarPedido($id, Request $request)
    {
        try {
            $pedido = PedidoProduccion::find($id);
            
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }
            
            $pedido->update([
                'estado' => 'PENDIENTE_SUPERVISOR',
                'aprobado_por_usuario_cartera' => auth()->user()->id,
                'aprobado_por_cartera_en' => now(),
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Pedido aprobado correctamente',
                'numero_pedido' => $pedido->numero_pedido
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en CarteraPedidosController::aprobarPedido: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Rechazar pedido
     */
    public function rechazarPedido($id, Request $request)
    {
        try {
            $request->validate([
                'motivo' => 'required|string|max:2000'
            ]);
            
            $pedido = PedidoProduccion::find($id);
            
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }
            
            // Obtener usuario y fecha/hora para la novedad
            $usuario = auth()->user()->name ?? auth()->user()->email ?? 'Usuario Cartera';
            $fechaHora = \Carbon\Carbon::now()->format('d-m-Y h:i:s A');
            
            // Crear novedad del rechazo
            $novedadRechazo = "[{$usuario} - {$fechaHora}] RECHAZADO POR CARTERA: {$request->motivo}";
            
            // Obtener novedades actuales
            $novedadesActuales = $pedido->novedades ?? '';
            
            // Concatenar con salto de línea si hay novedades anteriores
            if (!empty($novedadesActuales)) {
                $novedadesNuevas = $novedadesActuales . "\n\n" . $novedadRechazo;
            } else {
                $novedadesNuevas = $novedadRechazo;
            }
            
            $pedido->update([
                'estado' => 'RECHAZADO_CARTERA',
                'motivo_rechazo_cartera' => $request->motivo,
                'rechazado_por_usuario_cartera' => auth()->user()->id,
                'rechazado_por_cartera_en' => now(),
                'novedades' => $novedadesNuevas
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Pedido rechazado correctamente',
                'numero_pedido' => $pedido->numero_pedido
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en CarteraPedidosController::rechazarPedido: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtener datos de factura para mostrar en modal
     */
    public function obtenerDatosFactura($id)
    {
        try {
            // Usar el mismo servicio que usa AsesoresController para obtener datos completos
            $service = app(\App\Application\Services\Asesores\ObtenerDatosFacturaService::class);
            $datos = $service->obtener($id);
            
            \Log::info('[CARTERA-FACTURA] Datos obtenidos correctamente', [
                'pedido_id' => $id,
                'prendas_count' => count($datos['prendas'] ?? []),
                'procesos_total' => collect($datos['prendas'] ?? [])->sum(fn($p) => count($p['procesos'] ?? [])),
                'epps_count' => count($datos['epps'] ?? [])
            ]);
            
            return response()->json($datos);
        } catch (\Exception $e) {
            \Log::error('Error en CarteraPedidosController::obtenerDatosFactura: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos: ' . $e->getMessage()
            ], 500);
        }
    }
}
