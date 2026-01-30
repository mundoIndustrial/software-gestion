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
            return DB::transaction(function () use ($id, $request) {
                $pedido = PedidoProduccion::find($id);
                
                if (!$pedido) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Pedido no encontrado'
                    ], 404);
                }
                
                // Generar número de pedido correlativo solo al aprobar
                $siguienteNumero = $this->generarSiguienteNumeroPedido();
                
                // Obtener ID de usuario autenticado o null para evitar foreign key issues
                $usuarioId = auth()->check() ? auth()->user()->id : null;
                
                $pedido->update([
                    'numero_pedido' => $siguienteNumero,
                    'estado' => 'PENDIENTE_SUPERVISOR',
                    'aprobado_por_usuario_cartera' => $usuarioId,
                    'aprobado_por_cartera_en' => now(),
                ]);
                
                \Log::info('[CARTERA] Pedido aprobado y número generado', [
                    'pedido_id' => $pedido->id,
                    'numero_pedido_generado' => $siguienteNumero,
                    'aprobado_por' => $usuarioId
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Pedido aprobado correctamente',
                    'numero_pedido' => $siguienteNumero
                ]);
            });
        } catch (\Exception $e) {
            \Log::error('Error en CarteraPedidosController::aprobarPedido: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generar el siguiente número de pedido correlativo de forma segura
     * Usa tabla de secuencias con control de concurrencia
     */
    private function generarSiguienteNumeroPedido(): int
    {
        return DB::transaction(function () {
            // Obtener y bloquear la secuencia para evitar concurrencia
            $secuencia = DB::table('numero_secuencias')
                ->where('tipo', 'pedido_produccion')
                ->lockForUpdate()
                ->first();
            
            if (!$secuencia) {
                // Crear secuencia si no existe
                $secuenciaId = DB::table('numero_secuencias')->insertGetId([
                    'tipo' => 'pedido_produccion',
                    'siguiente' => 2, // El siguiente será 2, este es 1
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $numeroPedido = 1;
            } else {
                // Usar el siguiente número disponible
                $numeroPedido = $secuencia->siguiente;
                
                // Incrementar para la próxima vez
                DB::table('numero_secuencias')
                    ->where('tipo', 'pedido_produccion')
                    ->update([
                        'siguiente' => $numeroPedido + 1,
                        'updated_at' => now(),
                    ]);
            }
            
            \Log::info('[CARTERA] Número de pedido generado desde secuencia', [
                'numero_pedido' => $numeroPedido,
                'secuencia_id' => $secuencia->id ?? $secuenciaId,
                'tipo_secuencia' => 'pedido_produccion',
            ]);
            
            return $numeroPedido;
        });
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
            $usuario = auth()->check() ? (auth()->user()->name ?? auth()->user()->email ?? 'Usuario Cartera') : 'Usuario Cartera';
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
            
            // Obtener ID de usuario autenticado o null
            $usuarioId = auth()->check() ? auth()->user()->id : null;
            
            $pedido->update([
                'estado' => 'RECHAZADO_CARTERA',
                'motivo_rechazo_cartera' => $request->motivo,
                'rechazado_por_usuario_cartera' => $usuarioId,
                'rechazado_por_cartera_en' => now(),
                'novedades' => $novedadesNuevas,
                // numero_pedido permanece null al rechazar
            ]);
            
            \Log::info('[CARTERA] Pedido rechazado', [
                'pedido_id' => $pedido->id,
                'motivo' => $request->motivo,
                'rechazado_por' => $usuarioId,
                'numero_pedido' => $pedido->numero_pedido // debe ser null
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
