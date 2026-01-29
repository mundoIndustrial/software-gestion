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
        public array $changedFields = [],
        public string $action = 'updated'
    ) {
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        // Canal privado para el asesor específico
        return new Channel('pedidos.' . $this->asesor->id);
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
                'cliente' => $this->pedido->cliente,
                'estado' => $this->pedido->estado,
                'novedades' => $this->pedido->novedades,
                'forma_pago' => $this->pedido->forma_pago,
                'fecha_estimada' => $this->pedido->fecha_estimada,
                'updated_at' => $this->pedido->updated_at->toISOString(),
            ],
            'action' => $this->action,
            'changedFields' => $this->changedFields,
            'timestamp' => now()->toISOString(),
        ];
    }
}
