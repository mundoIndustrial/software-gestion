<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RegistrosPorOrdenUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $pedido;
    public $registros;
    public $action; // 'updated', 'deleted'

    /**
     * Create a new event instance.
     */
    public function __construct($pedido, $registros = null, $action = 'updated')
    {
        $this->pedido = $pedido;
        $this->registros = $registros;
        $this->action = $action;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return new Channel('registros-por-orden');
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith()
    {
        \Log::info('Broadcasting RegistrosPorOrdenUpdated event', [
            'pedido' => $this->pedido,
            'action' => $this->action,
            'registros_count' => $this->registros ? count($this->registros) : 0
        ]);

        return [
            'pedido' => $this->pedido,
            'registros' => $this->registros,
            'action' => $this->action
        ];
    }
}
