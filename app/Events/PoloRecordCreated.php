<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PoloRecordCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $registro;

    /**
     * Create a new event instance.
     */
    public function __construct($registro)
    {
        $this->registro = $registro;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return new Channel('polo');
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith()
    {
        \Log::info('Broadcasting PoloRecordCreated event', [
            'registro_id' => $this->registro->id ?? null,
            'hora' => $this->registro->hora->hora ?? null,
            'operario' => $this->registro->operario->name ?? null,
        ]);

        return [
            'registro' => $this->registro
        ];
    }
}
