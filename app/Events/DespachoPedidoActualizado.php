<?php

namespace App\Events;

use App\Models\PedidoProduccion;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DespachoPedidoActualizado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public PedidoProduccion $pedido,
        public array $data = []
    ) {
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return new Channel('despacho.pedidos');
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
        return array_merge($this->data, [
            'pedido_id' => $this->pedido->id,
            'numero_pedido' => $this->pedido->numero_pedido,
            'estado' => $this->pedido->estado,
            'cliente' => $this->pedido->cliente,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
