<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ControlCalidadUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $orden;
    public $action; // 'added', 'removed'
    public $tipo; // 'pedido', 'bodega'

    /**
     * Create a new event instance.
     */
    public function __construct($orden, $action, $tipo)
    {
        $this->orden = $orden;
        $this->action = $action;
        $this->tipo = $tipo;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return new Channel('control-calidad');
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith()
    {
        return [
            'orden' => $this->orden,
            'action' => $this->action,
            'tipo' => $this->tipo
        ];
    }
}
