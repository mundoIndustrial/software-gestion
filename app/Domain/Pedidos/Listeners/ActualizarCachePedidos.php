<?php

namespace App\Domain\Pedidos\Listeners;

use App\Domain\Pedidos\Events\PedidoProduccionCreado;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ActualizarCachePedidos
 * 
 * Listener que se dispara cuando se crea un nuevo pedido
 * Responsabilidades:
 * - Invalidar cachÃ©s relacionados
 * - Actualizar estadÃ­sticas en cachÃ©
 * - Mantener datos frescos en Redis
 * 
 * Este es otro ejemplo de un side effect que no pertenece al agregado.
 */
class ActualizarCachePedidos
{
    /**
     * Ejecutar el listener
     */
    public function __invoke(PedidoProduccionCreado $event): void
    {
        try {
            Log::info('ðŸ”„ Actualizando cachÃ© de pedidos', [
                'pedido_id' => $event->getPedidoId(),
                'numero_pedido' => $event->getNumeroPedido(),
            ]);

            // Invalidar cachÃ©s relacionados
            $cacheKeys = [
                'pedidos_list',
                'pedidos_pending_count',
                'pedidos_asesor_' . $event->getAseoreId(),
                'estadisticas_pedidos',
            ];

            foreach ($cacheKeys as $key) {
                Cache::forget($key);
                Log::debug("CachÃ© invalidado: $key");
            }

            // Actualizar estadÃ­sticas
            $statsKey = 'pedidos_stats';
            $stats = Cache::get($statsKey, [
                'total' => 0,
                'pendientes' => 0,
                'en_proceso' => 0,
                'completados' => 0,
            ]);

            $stats['total'] = ($stats['total'] ?? 0) + 1;
            $stats['pendientes'] = ($stats['pendientes'] ?? 0) + 1;

            Cache::put($statsKey, $stats, now()->addHours(24));

            Log::info(' CachÃ© de pedidos actualizado', [
                'pedido_id' => $event->getPedidoId(),
                'estadisticas' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error(' Error actualizando cachÃ©', [
                'error' => $e->getMessage(),
                'pedido_id' => $event->getPedidoId(),
            ]);
        }
    }
}

