<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CorteRecordCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $registro;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($registro)
    {
        $this->registro = $registro;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('corte');
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        \Log::info('Broadcasting CorteRecordCreated event', [
            'registro_id' => $this->registro->id,
            'hora' => $this->registro->hora->hora ?? null,
            'operario' => $this->registro->operario->name ?? null,
        ]);

        return [
            'registro' => $this->registro
        ];
    }
}
