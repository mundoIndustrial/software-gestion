<?php

namespace App\Application\Services\Asesores;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use App\Models\ProcesoPrenda;
use App\Models\MaterialesOrdenInsumos;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * EliminarPedidoService
 * 
 * Servicio para eliminar pedidos de producciÃ³n y todas sus relaciones.
 * Encapsula la lÃ³gica compleja de cascada de eliminaciÃ³n.
 */
class EliminarPedidoService
{
    /**
     * Eliminar un pedido completamente (incluyendo todas sus relaciones)
     * 
     * Elimina:
     * - El pedido de producciÃ³n
     * - Todas las prendas asociadas
     * - Todos los procesos de prenda
     * - Todos los materiales de insumos
     * - Historial de cambios de estado
     * - Los logos asociados
     * - Todas las fotos de prendas
     * - Todas las fotos de telas
     * - Todas las fotos de logos de prendas
     */
    public function eliminarPedido(int $numeroPedido): array
    {
        $userId = Auth::id();
        
        // Obtener el pedido con mayor tolerancia
        $pedidoData = Pedidos::where('numero_pedido', $numeroPedido)
            ->where('asesor_id', $userId)
            ->first();

        if (!$pedidoData) {
            throw new \Exception('Pedido no encontrado o no tienes permiso para eliminarlo', 404);
        }

        DB::beginTransaction();
        try {
            $numeroPedidoGuardado = $pedidoData->numero_pedido;
            $pedidoId = $pedidoData->id;
            
            \Log::info('ðŸ—‘ï¸ Iniciando eliminaciÃ³n de pedido', [
                'numero_pedido' => $numeroPedidoGuardado,
                'pedido_id' => $pedidoId,
            ]);
            
            // Obtener todas las prendas del pedido para eliminar sus fotos
            $prendas = PrendaPedido::where('numero_pedido', $numeroPedidoGuardado)->get();
            
            // 1. Eliminar fotos de prendas (prenda_fotos_pedido)
            foreach ($prendas as $prenda) {
                DB::table('prenda_fotos_pedido')
                    ->where('prenda_pedido_id', $prenda->id)
                    ->delete();
                
                // 2. Eliminar fotos de telas (prenda_fotos_tela_pedido)
                DB::table('prenda_fotos_tela_pedido')
                    ->where('prenda_pedido_id', $prenda->id)
                    ->delete();
                
                // 3. Eliminar fotos de logos de prendas (prenda_fotos_logo_pedido)
                DB::table('prenda_fotos_logo_pedido')
                    ->where('prenda_pedido_id', $prenda->id)
                    ->delete();
            }
            
            \Log::info('ðŸ—‘ï¸ Fotos de prendas eliminadas', [
                'numero_pedido' => $numeroPedidoGuardado,
                'prendas_procesadas' => $prendas->count()
            ]);
            
            // 4. Eliminar procesos de prenda (relacionados por numero_pedido)
            ProcesoPrenda::where('numero_pedido', $numeroPedidoGuardado)->delete();
            
            // 5. Eliminar prendas (relacionadas por numero_pedido)
            PrendaPedido::where('numero_pedido', $numeroPedidoGuardado)->delete();
            
            \Log::info('ðŸ—‘ï¸ Prendas eliminadas', [
                'numero_pedido' => $numeroPedidoGuardado,
                'cantidad_prendas' => $prendas->count()
            ]);
            
            // 6. Eliminar materiales de insumos (relacionados por numero_pedido)
            MaterialesOrdenInsumos::where('numero_pedido', $numeroPedidoGuardado)->delete();
            
            // 7. Eliminar pedido(s) de LOGO si esta es una cotizaciÃ³n combinada
            $logoPedidos = DB::table('logo_pedidos')
                ->where('pedido_id', $pedidoId)
                ->get();
            
            if ($logoPedidos->count() > 0) {
                \Log::info('ðŸ—‘ï¸ Encontrados logo_pedidos vinculados', [
                    'cantidad' => $logoPedidos->count(),
                    'pedido_id' => $pedidoId
                ]);
                
                foreach ($logoPedidos as $logoPedido) {
                    // Eliminar fotos del logo
                    DB::table('logo_pedido_fotos')
                        ->where('logo_pedido_id', $logoPedido->id)
                        ->delete();
                    
                    DB::table('logo_pedido_imagenes')
                        ->where('logo_pedido_id', $logoPedido->id)
                        ->delete();
                    
                    // Eliminar procesos del logo
                    DB::table('procesos_pedidos_logo')
                        ->where('logo_pedido_id', $logoPedido->id)
                        ->delete();
                    
                    \Log::info('ðŸ—‘ï¸ Fotos y procesos del logo eliminados', [
                        'logo_pedido_id' => $logoPedido->id,
                        'numero_logo_pedido' => $logoPedido->numero_pedido ?? 'N/A'
                    ]);
                }
                
                // Eliminar el logo_pedido
                DB::table('logo_pedidos')
                    ->where('pedido_id', $pedidoId)
                    ->delete();
                
                \Log::info('ðŸ—‘ï¸ Logo pedidos eliminados', [
                    'pedido_id' => $pedidoId,
                    'cantidad' => $logoPedidos->count()
                ]);
            }
            
            // 8. Eliminar el pedido de pedidos_produccion
            $pedidoData->delete();
            
            \Log::info('ðŸ—‘ï¸ Pedido eliminado de pedidos_produccion', [
                'numero_pedido' => $numeroPedidoGuardado,
                'pedido_id' => $pedidoId,
            ]);
            
            // 9. Decrementar el nÃºmero de secuencia
            DB::table('numero_secuencias')
                ->where('tipo', 'pedido_produccion')
                ->decrement('siguiente');
            
            \Log::info('ðŸ—‘ï¸ NÃºmero de secuencia decrementado', [
                'numero_pedido' => $numeroPedidoGuardado,
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Pedido, prendas, logos y todas sus fotos eliminados exitosamente'
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error(' Error al eliminar pedido: ' . $e->getMessage(), [
                'numero_pedido' => $numeroPedido,
                'usuario' => $userId,
                'exception' => $e
            ]);
            throw $e;
        }
    }
}

