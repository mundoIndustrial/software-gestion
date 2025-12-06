<?php

namespace App\Services;

use App\Models\PedidoProduccion;
use App\Models\News;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * RegistroOrdenDeletionService
 * 
 * Responsabilidad: Lógica de eliminación de órdenes y limpieza de datos asociados
 * Cumple con SRP: Solo maneja eliminación, no validación
 * Cumple con DIP: Inyecta dependencias necesarias
 */
class RegistroOrdenDeletionService
{
    /**
     * Eliminar orden completa y sus datos asociados
     */
    public function deleteOrder(int $numeroPedido): void
    {
        DB::beginTransaction();

        try {
            // Obtener la orden
            $orden = PedidoProduccion::where('numero_pedido', $numeroPedido)->firstOrFail();

            // Eliminar todas las prendas asociadas (las entregas se eliminan automáticamente por cascada)
            $orden->prendas()->delete();
            
            // Eliminar la orden
            $orden->delete();

            // Invalidar caché
            $this->invalidateCacheDays($numeroPedido);

            // Registrar evento
            $this->logOrderDeleted($numeroPedido);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Invalidar caché de días calculados para la orden eliminada
     */
    private function invalidateCacheDays(int $numeroPedido): void
    {
        $hoy = now()->format('Y-m-d');
        $currentYear = now()->year;
        $festivos = FestivosColombiaService::obtenerFestivos($currentYear);
        $festivosCacheKey = md5(serialize($festivos));
        
        $estados = ['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'];
        
        foreach ($estados as $estado) {
            $cacheKey = "orden_dias_{$numeroPedido}_{$estado}_{$hoy}_{$festivosCacheKey}";
            Cache::forget($cacheKey);
        }
        
        // También invalidar para días anteriores (últimos 7 días)
        for ($i = 1; $i <= 7; $i++) {
            $fecha = now()->subDays($i)->format('Y-m-d');
            foreach ($estados as $estado) {
                $cacheKey = "orden_dias_{$numeroPedido}_{$estado}_{$fecha}_{$festivosCacheKey}";
                Cache::forget($cacheKey);
            }
        }
    }

    /**
     * Registrar evento de eliminación
     */
    private function logOrderDeleted(int $numeroPedido): void
    {
        News::create([
            'event_type' => 'order_deleted',
            'description' => "Orden eliminada: Pedido {$numeroPedido}",
            'user_id' => auth()->id(),
            'pedido' => $numeroPedido,
            'metadata' => ['action' => 'deleted']
        ]);
    }

    /**
     * Broadcast evento de orden eliminada
     */
    public function broadcastOrderDeleted(int $numeroPedido): void
    {
        broadcast(new \App\Events\OrdenUpdated(['numero_pedido' => $numeroPedido], 'deleted'));
    }
}
