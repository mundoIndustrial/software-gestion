<?php

namespace App\Domain\PedidoProduccion\Listeners;

use App\Domain\PedidoProduccion\Events\PedidoProduccionCreado;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * RegistrarAuditoriaPedido
 * 
 * Listener que se dispara cuando se crea un nuevo pedido
 * Responsabilidades:
 * - Registrar en tabla de auditoría
 * - Registrar cambios de estado
 * - Mantener histórico para compliance
 * 
 * Importante: No es un side effect destructivo, es informativo.
 * Se puede ejecutar asincronicamente sin afectar la operación principal.
 */
class RegistrarAuditoriaPedido
{
    /**
     * Ejecutar el listener
     */
    public function __invoke(PedidoProduccionCreado $event): void
    {
        try {
            Log::info('📝 Registrando auditoría de pedido', [
                'pedido_id' => $event->getPedidoId(),
                'numero_pedido' => $event->getNumeroPedido(),
            ]);

            // Registrar en tabla de auditoría
            DB::table('auditoria_pedidos')->insert([
                'pedido_id' => $event->getPedidoId(),
                'numero_pedido' => $event->getNumeroPedido(),
                'evento' => $event->getEventName(),
                'accion' => 'CREADO',
                'datos_anteriores' => null,
                'datos_nuevos' => json_encode($event->toArray()),
                'usuario_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('✅ Auditoría de pedido registrada', [
                'pedido_id' => $event->getPedidoId(),
                'evento' => $event->getEventName(),
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error registrando auditoría', [
                'error' => $e->getMessage(),
                'pedido_id' => $event->getPedidoId(),
            ]);
            // No re-lanzar para no interrumpir flujo principal
        }
    }
}
