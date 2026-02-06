<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BodegaDetallesActualizados implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $numeroPedido,
        public string $talla,
        public array $detalles
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("bodega-detalles-{$this->numeroPedido}-{$this->talla}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'detalle.actualizado';
    }
}
