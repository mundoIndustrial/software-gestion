<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EntregaEliminada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tipo;
    public $subtipo;
    public $id;
    public $entrega;

    /**
     * Create a new event instance.
     */
    public function __construct($tipo, $subtipo, $id, $entrega)
    {
        $this->tipo = $tipo;
        $this->subtipo = $subtipo;
        $this->id = $id;
        $this->entrega = $entrega;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return new Channel('entregas.' . $this->tipo);
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith()
    {
        \Log::info('Broadcasting EntregaEliminada event', [
            'tipo' => $this->tipo,
            'subtipo' => $this->subtipo,
            'id' => $this->id,
            'pedido' => $this->entrega->pedido ?? null,
        ]);

        return [
            'tipo' => $this->tipo,
            'subtipo' => $this->subtipo,
            'id' => $this->id,
            'entrega' => $this->entrega,
        ];
    }
}
