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
            $pedido = PedidoProduccion::with([
                'prendas.variantes.tipoManga',
                'prendas.variantes.tipoBroche',
                'prendas.tallas'
            ])->find($id);
            
            if (!$pedido) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }
            
            // Mapear prendas con variantes para el modal profesional
            $prendasFormato = $pedido->prendas->map(function($prenda) {
                // Obtener tallas agrupadas por género en formato { GENERO: { TALLA: CANTIDAD } }
                $tallasAgrupadas = [];
                if ($prenda->tallas && $prenda->tallas->count() > 0) {
                    foreach ($prenda->tallas as $talla) {
                        if (!isset($tallasAgrupadas[$talla->genero])) {
                            $tallasAgrupadas[$talla->genero] = [];
                        }
                        $tallasAgrupadas[$talla->genero][$talla->talla] = $talla->cantidad;
                    }
                }
                
                return [
                    'nombre' => $prenda->nombre_prenda ?? 'Prenda sin nombre',
                    'cantidad' => $prenda->cantidad ?? 0,
                    'manga' => null,
                    'obs_manga' => $prenda->observaciones,
                    'broche' => null,
                    'obs_broche' => null,
                    'tiene_bolsillos' => $prenda->tiene_bolsillos ?? false,
                    'obs_bolsillos' => null,
                    'tallas' => $tallasAgrupadas,  // Estructura: { DAMA: { L: 5, M: 15, S: 10 } }
                    'variantes' => $prenda->variantes ? $prenda->variantes->map(function($var) {
                        return [
                            'talla' => $var->talla ?? 'N/A',
                            'cantidad' => $var->cantidad ?? 0,
                            'manga' => $var->tipoManga?->nombre ?? null,
                            'manga_obs' => $var->manga_obs ?? null,
                            'broche' => $var->tipoBroche?->nombre ?? null,
                            'broche_obs' => $var->broche_obs ?? null,
                            'bolsillos' => $var->tiene_bolsillos ?? false,
                            'bolsillos_obs' => $var->bolsillos_obs ?? null
                        ];
                    })->toArray() : []
                ];
            })->toArray();
            
            // Obtener datos con estructura compatible con crearModalPreviewFactura
            $datos = [
                'numero_pedido' => $pedido->numero_pedido,
                'numero_pedido_temporal' => $pedido->id,
                'cliente' => $pedido->cliente,
                'fecha' => $pedido->fecha_de_creacion_de_orden ?? $pedido->created_at,
                'prendas' => $prendasFormato
            ];
            
            // LOG: Debug de estructura de tallas
            \Log::info(' [CarteraPedidosController] Estructura de prendas con tallas:', [
                'pedido_numero' => $pedido->numero_pedido,
                'cantidad_prendas' => count($prendasFormato),
                'primera_prenda' => $prendasFormato[0] ?? null,
                'tallas_primera_prenda' => $prendasFormato[0]['tallas'] ?? 'VACÍO'
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
