<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EntregaRegistrada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tipo;
    public $subtipo;
    public $entrega;
    public $fecha;

    /**
     * Create a new event instance.
     */
    public function __construct($tipo, $subtipo, $entrega, $fecha)
    {
        $this->tipo = $tipo;
        $this->subtipo = $subtipo;
        $this->entrega = $entrega;
        $this->fecha = $fecha;
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
        \Log::info('Broadcasting EntregaRegistrada event', [
            'tipo' => $this->tipo,
            'subtipo' => $this->subtipo,
            'pedido' => $this->entrega->pedido ?? null,
            'fecha' => $this->fecha,
        ]);

        return [
            'tipo' => $this->tipo,
            'subtipo' => $this->subtipo,
            'entrega' => $this->entrega,
            'fecha' => $this->fecha,
        ];
    }
}
