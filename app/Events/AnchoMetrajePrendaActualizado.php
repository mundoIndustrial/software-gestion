<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class AnchoMetrajePrendaActualizado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly array $payload
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('insumos.materiales'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ancho-metraje.actualizado';
    }

    public function broadcastWith(): array
    {
        return array_merge($this->payload, [
            'timestamp' => now()->toISOString(),
        ]);
    }
}
