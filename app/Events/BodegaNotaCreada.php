<?php

namespace App\Events;

use App\Models\BodegaNota;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BodegaNotaCreada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public BodegaNota $nota)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('asesores.observaciones'),
            new Channel('pedido.' . $this->nota->pedido_produccion_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'bodega.nota';
    }

    public function broadcastWith(): array
    {
        return [
            'pedido_id' => $this->nota->pedido_produccion_id,
            'numero_pedido' => $this->nota->numero_pedido,
            'talla' => $this->nota->talla,
            'nota' => [
                'id' => (int) $this->nota->id,
                'contenido' => $this->nota->contenido,
                'usuario_id' => (int) $this->nota->usuario_id,
                'usuario_nombre' => $this->nota->usuario_nombre,
                'usuario_rol' => $this->nota->usuario_rol,
                'created_at' => optional($this->nota->created_at)->toISOString(),
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}
