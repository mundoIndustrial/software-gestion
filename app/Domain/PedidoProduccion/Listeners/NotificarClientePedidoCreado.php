<?php

namespace App\Domain\PedidoProduccion\Listeners;

use App\Domain\PedidoProduccion\Events\PedidoProduccionCreado;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * NotificarClientePedidoCreado
 * 
 * Listener que se dispara cuando se crea un nuevo pedido de producción
 * Responsabilidades:
 * - Notificar al cliente via email
 * - Notificar al asesor
 * - Registrar la notificación en logs
 * 
 * Este es un ejemplo de un side effect de dominio.
 * Los side effects no deben estar en el agregado, sino en listeners.
 */
class NotificarClientePedidoCreado
{
    /**
     * Ejecutar el listener
     */
    public function __invoke(PedidoProduccionCreado $event): void
    {
        try {
            Log::info('📧 Notificando cliente de nuevo pedido', [
                'pedido_id' => $event->getPedidoId(),
                'numero_pedido' => $event->getNumeroPedido(),
                'cliente' => $event->getCliente(),
            ]);

            // Obtener asesor para obtener email del cliente
            $asesor = User::find($event->getAseoreId());
            
            if (!$asesor) {
                Log::warning('⚠️ Asesor no encontrado para notificación', [
                    'asesor_id' => $event->getAseoreId(),
                ]);
                return;
            }

            // Aquí irían las notificaciones via email/SMS
            // Por ahora, solo logging
            Log::info(' Notificación de pedido enviada', [
                'pedido_id' => $event->getPedidoId(),
                'numero_pedido' => $event->getNumeroPedido(),
                'cliente' => $event->getCliente(),
                'asesor' => $asesor->name,
                'estado' => $event->getEstado(),
            ]);

        } catch (\Exception $e) {
            Log::error(' Error notificando cliente', [
                'error' => $e->getMessage(),
                'pedido_id' => $event->getPedidoId(),
            ]);
            // No re-lanzar excepción para no interrumpir el flujo principal
        }
    }
}
