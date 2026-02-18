<?php

namespace App\Application\Services;

use App\Models\BodegaDetallesTalla;
use App\Models\PedidoProduccion;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EntregaService
{
    /**
     * Registrar la entrega de una prenda cuando un pedido cambia a estado 'Entregado'
     */
    public function registrarEntregaPrenda(array $datosPrenda, int $pedidoProduccionId): BodegaDetallesTalla
    {
        try {
            // Obtener información del pedido
            $pedido = PedidoProduccion::find($pedidoProduccionId);
            if (!$pedido) {
                throw new \Exception("Pedido no encontrado con ID: {$pedidoProduccionId}");
            }
            
            // Registrar la entrega en bodega_detalles_talla
            $entrega = BodegaDetallesTalla::create([
                'pedido_produccion_id' => $pedidoProduccionId,
                'numero_pedido' => $pedido->numero_pedido,
                'prenda_nombre' => $datosPrenda['prenda_nombre'] ?? '',
                'talla' => $datosPrenda['talla'] ?? '',
                'cantidad' => $datosPrenda['cantidad'] ?? 0,
                'asesor' => $datosPrenda['asesor'] ?? '',
                'empresa' => $pedido->cliente ?? '',
                'fecha_pedido' => $pedido->created_at ?? now(),
                'fecha_entrega' => now(),
                'estado_bodega' => 'Entregado',
                'usuario_bodega_id' => auth()->id(),
                'usuario_bodega_nombre' => auth()->user()->name ?? '',
                'observaciones_bodega' => $datosPrenda['observaciones_entrega'] ?? 'Entregado desde bodega',
            ]);
            
            \Log::info('[ENTREGA] Entrega de prenda registrada', [
                'pedido_produccion_id' => $pedidoProduccionId,
                'numero_pedido' => $pedido->numero_pedido,
                'prenda' => $datosPrenda['prenda_nombre'],
                'talla' => $datosPrenda['talla'],
                'cantidad' => $datosPrenda['cantidad'],
                'cliente' => $pedido->cliente,
                'usuario' => auth()->user()->name,
                'fecha_entrega' => $entrega->fecha_entrega->format('Y-m-d H:i:s'),
                'estado_bodega' => $entrega->estado_bodega,
            ]);
            
            return $entrega;
            
        } catch (\Exception $e) {
            \Log::error('[ENTREGA] Error al registrar entrega de prenda', [
                'pedido_produccion_id' => $pedidoProduccionId,
                'datos_prenda' => $datosPrenda,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Registrar entregas masivas cuando un pedido cambia a estado 'Entregado'
     */
    public function registrarEntregasMasivas(int $pedidoProduccionId, array $prendasEntregadas): array
    {
        $entregas = [];
        
        try {
            $pedido = PedidoProduccion::find($pedidoProduccionId);
            if (!$pedido) {
                \Log::warning('[ENTREGA] Pedido no encontrado para registrar entregas masivas', [
                    'pedido_produccion_id' => $pedidoProduccionId,
                    'prendas_entregadas_count' => count($prendasEntregadas)
                ]);
                return $entregas;
            }
            
            foreach ($prendasEntregadas as $datosPrenda) {
                $entrega = $this->registrarEntregaPrenda($datosPrenda, $pedidoProduccionId);
                $entregas[] = $entrega;
            }
            
            \Log::info('[ENTREGA] Entregas masivas registradas', [
                'pedido_produccion_id' => $pedidoProduccionId,
                'numero_pedido' => $pedido->numero_pedido,
                'prendas_entregadas_count' => count($entregas),
                'cliente' => $pedido->cliente,
                'usuario' => auth()->user()->name
            ]);
            
            return $entregas;
            
        } catch (\Exception $e) {
            \Log::error('[ENTREGA] Error al registrar entregas masivas', [
                'pedido_produccion_id' => $pedidoProduccionId,
                'prendas_entregadas_count' => count($prendasEntregadas),
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }
    
    /**
     * Verificar si todas las prendas de un pedido han sido entregadas
     */
    public function verificarPedidoCompletamenteEntregado(int $pedidoProduccionId): bool
    {
        try {
            $pedido = PedidoProduccion::find($pedidoProduccionId);
            if (!$pedido) {
                return false;
            }
            
            // Contar prendas únicas del pedido
            $prendasTotales = DB::table('bodega_detalles_talla')
                ->where('pedido_produccion_id', $pedidoProduccionId)
                ->distinct(['prenda_nombre', 'talla'])
                ->count();
            
            // Contar prendas entregadas
            $prendasEntregadas = EntregaPrenda::where('pedido_produccion_id', $pedidoProduccionId)
                ->distinct(['prenda_nombre', 'talla'])
                ->count();
            
            $completamenteEntregado = ($prendasEntregadas >= $prendasTotales);
            
            \Log::info('[ENTREGA] Verificación de pedido completamente entregado', [
                'pedido_produccion_id' => $pedidoProduccionId,
                'numero_pedido' => $pedido->numero_pedido,
                'prendas_totales' => $prendasTotales,
                'prendas_entregadas' => $prendasEntregadas,
                'completamente_entregado' => $completamenteEntregado
            ]);
            
            return $completamenteEntregado;
            
        } catch (\Exception $e) {
            \Log::error('[ENTREGA] Error al verificar estado de entrega del pedido', [
                'pedido_produccion_id' => $pedidoProduccionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Obtener entregas de un pedido
     */
    public function obtenerEntregasPorPedido(int $pedidoProduccionId): array
    {
        try {
            return EntregaPrenda::where('pedido_produccion_id', $pedidoProduccionId)
                ->with(['pedidoProduccion', 'usuarioEntrega'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($entrega) {
                    return [
                        'id' => $entrega->id,
                        'numero_pedido' => $entrega->numero_pedido,
                        'prenda_nombre' => $entrega->prenda_nombre,
                        'talla' => $entrega->talla,
                        'cantidad' => $entrega->cantidad,
                        'cliente' => $entrega->cliente,
                        'asesor' => $entrega->asesor,
                        'fecha_entrega' => $entrega->getFechaHoraEntrega(),
                        'usuario_entrega' => $entrega->usuario_entrega_nombre,
                        'observaciones_entrega' => $entrega->observaciones_entrega,
                        'estado_pedido' => $entrega->getEstadoPedido(),
                        'nombre_completo' => $entrega->getNombreCompleto(),
                        'created_at' => $entrega->created_at->format('Y-m-d H:i:s'),
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            \Log::error('[ENTREGA] Error al obtener entregas del pedido', [
                'pedido_produccion_id' => $pedidoProduccionId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Obtener estadísticas de entregas
     */
    public function obtenerEstadisticasEntregas(): array
    {
        try {
            $totalEntregas = EntregaPrenda::count();
            $entregasHoy = EntregaPrenda::whereDate('fecha_entrega', now()->format('Y-m-d'))->count();
            $entregasMes = EntregaPrenda::whereMonth('fecha_entrega', now()->month)->whereYear('fecha_entrega', now()->year())->count();
            
            return [
                'total_entregas' => $totalEntregas,
                'entregas_hoy' => $entregasHoy,
                'entregas_mes' => $entregasMes,
                'fecha_actual' => now()->format('Y-m-d'),
            ];
        } catch (\Exception $e) {
            \Log::error('[ENTREGA] Error al obtener estadísticas de entregas', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Buscar entregas por diferentes criterios
     */
    public function buscarEntregas(array $criterios): array
    {
        $query = EntregaPrenda::with(['pedidoProduccion', 'usuarioEntrega']);
        
        // Filtrar por número de pedido
        if (isset($criterios['numero_pedido'])) {
            $query->porPedido($criterios['numero_pedido']);
        }
        
        // Filtrar por cliente
        if (isset($criterios['cliente'])) {
            $query->porCliente($criterios['cliente']);
        }
        
        // Filtrar por fecha
        if (isset($criterios['fecha'])) {
            $query->porFecha($criterios['fecha']);
        }
        
        // Filtrar por estado del pedido
        if (isset($criterios['estado_pedido'])) {
            $query->whereHas('pedidoProduccion', function ($query) use ($criterios) {
                    $query->where('estado', $criterios['estado_pedido']);
                });
        }
        
        // Ordenar resultados
        $orden = $criterios['orden'] ?? 'created_at';
        $direccion = $criterios['direccion'] ?? 'desc';
        
        if ($orden === 'fecha_entrega') {
            $query->orderBy('fecha_entrega', $direccion);
        } elseif ($orden === 'cliente') {
            $query->orderBy('cliente', $direccion);
        } elseif ($orden === 'created_at') {
            $query->orderBy('created_at', $direccion);
        }
        
        return $query->get()->toArray();
    }
}
