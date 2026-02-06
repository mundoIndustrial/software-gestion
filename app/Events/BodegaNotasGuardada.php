<?php

namespace App\Events;

use App\Models\BodegaNota;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BodegaNotasGuardada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $numeroPedido,
        public string $talla,
        public array $notaData
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("bodega-notas-{$this->numeroPedido}-{$this->talla}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'nota.guardada';
    }
}
