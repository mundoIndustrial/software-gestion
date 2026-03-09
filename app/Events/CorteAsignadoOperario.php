<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CorteAsignadoOperario implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public array $payload = [])
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('operarios.corte');
    }

    public function broadcastAs(): string
    {
        return 'corte.asignado';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
