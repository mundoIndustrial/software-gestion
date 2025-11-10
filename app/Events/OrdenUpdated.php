<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrdenUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $orden;
    public $action; // 'created', 'updated', 'deleted'

    /**
     * Create a new event instance.
     */
    public function __construct($orden, $action = 'updated')
    {
        $this->orden = $orden;
        $this->action = $action;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return new Channel('ordenes');
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith()
    {
        \Log::info('Broadcasting OrdenUpdated event', [
            'pedido' => $this->orden->pedido ?? $this->orden['pedido'] ?? null,
            'action' => $this->action,
        ]);

        return [
            'orden' => $this->orden,
            'action' => $this->action
        ];
    }
}
