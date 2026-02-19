<?php

namespace App\Events;

use App\Models\PedidoProduccion;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PedidoActualizado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public PedidoProduccion $pedido,
        public User $asesor,
        public array $changedFields,
        public string $action = 'updated'
    ) {
        \Log::info('[PedidoActualizado] Evento creado', [
            'pedido_id' => $this->pedido->id,
            'numero_pedido' => $this->pedido->numero_pedido,
            'asesor_id' => $this->asesor->id,
            'action' => $this->action,
            'changedFields' => $this->changedFields,
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        // Canal privado para el asesor específico
        $channels = [
            new Channel('pedidos.' . $this->asesor->id)
        ];
        
        // También broadcast al canal público de despacho para actualizaciones en tiempo real
        // Usar Channel público para que todos puedan escuchar sin autenticación
        $channels[] = new Channel('despacho.pedidos');
        
        \Log::info('[PedidoActualizado] Canales de broadcast', [
            'pedido_id' => $this->pedido->id,
            'numero_pedido' => $this->pedido->numero_pedido,
            'canal_privado' => 'pedidos.' . $this->asesor->id,
            'canal_despacho' => 'despacho.pedidos',
            'total_canales' => count($channels)
        ]);
        
        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs()
    {
        return 'pedido.actualizado';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith()
    {
        \Log::info('Broadcasting PedidoActualizado event', [
            'pedido_id' => $this->pedido->id,
            'asesor_id' => $this->asesor->id,
            'action' => $this->action,
            'changedFields' => $this->changedFields,
        ]);

        return [
            'pedido' => [
                'id' => $this->pedido->id,
                'numero_pedido' => $this->pedido->numero_pedido,
                'cliente' => $this->pedido->cliente,
                'estado' => $this->pedido->estado,
                'novedades' => $this->pedido->novedades,
                'forma_pago' => $this->pedido->forma_pago,
                'fecha_estimada' => $this->pedido->fecha_estimada,
                'updated_at' => $this->pedido->updated_at->toISOString(),
            ],
            'pedido_id' => $this->pedido->id,
            'numero_pedido' => $this->pedido->numero_pedido,
            'nuevo_estado' => $this->changedFields['estado'] ?? $this->pedido->estado,
            'anterior_estado' => $this->changedFields['estado']['old'] ?? $this->pedido->estado,
            'action' => $this->action,
            'changedFields' => $this->changedFields,
            'timestamp' => now()->toISOString(),
        ];
    }
}
