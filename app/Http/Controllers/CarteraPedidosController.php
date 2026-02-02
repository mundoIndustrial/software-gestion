<?php

namespace App\Http\Controllers;

use App\Models\PedidoProduccion;
use App\Application\Services\Asesores\ObtenerDatosFacturaService;
use App\Domain\Pedidos\Services\PedidoSequenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CarteraPedidosController extends Controller
{
    /**
     * Obtener pedidos pendientes de cartera con paginación y filtros
     */
    public function obtenerPedidos(Request $request)
    {
        try {
            // Parámetros de paginación
            $page = (int) $request->get('page', 1);
            $perPage = (int) $request->get('per_page', 15);
            $search = $request->get('search', '');
            $cliente = $request->get('cliente', '');
            $fechaDesde = $request->get('fecha_desde', '');
            $fechaHasta = $request->get('fecha_hasta', '');
            $sortBy = $request->get('sort_by', 'fecha');
            $sortOrder = $request->get('sort_order', 'desc');
            
            // Validar valores
            $page = max(1, $page);
            $perPage = max(1, min($perPage, 100));
            $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? strtolower($sortOrder) : 'desc';
            
            // Estados que deben estar listos para cartera
            $estadosPendientes = ['pendiente_cartera'];
            
            // Si se especifica un estado específico, usarlo
            if ($request->has('estado') && in_array($request->estado, $estadosPendientes)) {
                $estadosPendientes = [$request->estado];
            }
            
            // Construir query
            $query = PedidoProduccion::whereIn('estado', $estadosPendientes);
            
            // Aplicar búsqueda general
            if (!empty($search)) {
                $search = '%' . $search . '%';
                $query->where(function($q) use ($search) {
                    $q->where('cliente', 'like', $search)
                      ->orWhere('numero_pedido', 'like', $search)
                      ->orWhere('id', 'like', $search);
                });
            }
            
            // Aplicar filtro de cliente
            if (!empty($cliente)) {
                $clientePattern = '%' . $cliente . '%';
                $query->where('cliente', 'like', $clientePattern);
            }
            
            // Aplicar filtro de fechas
            if (!empty($fechaDesde)) {
                $query->whereDate('fecha_de_creacion_de_orden', '>=', $fechaDesde);
            }
            if (!empty($fechaHasta)) {
                $query->whereDate('fecha_de_creacion_de_orden', '<=', $fechaHasta);
            }
            
            // Aplicar ordenamiento
            if ($sortBy === 'cliente') {
                $query->orderBy('cliente', $sortOrder);
            } else {
                $query->orderBy('fecha_de_creacion_de_orden', $sortOrder);
            }
            
            // Obtener total
            $total = $query->count();
            
            // Aplicar paginación
            $pedidos = $query->forPage($page, $perPage)->get();
            
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
                'data' => $data,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage),
                    'from' => ($page - 1) * $perPage + 1,
                    'to' => min($page * $perPage, $total)
                ]
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
                
                // Generar número de pedido correlativo solo al aprobar usando servicio centralizado
                $pedidoSequenceService = app(PedidoSequenceService::class);
                $siguienteNumero = $pedidoSequenceService->generarNumeroPedido();
                
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
