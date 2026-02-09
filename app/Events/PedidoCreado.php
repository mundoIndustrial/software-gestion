<?php

namespace App\Events;

use App\Models\PedidoProduccion;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PedidoCreado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public PedidoProduccion $pedido,
        public User $asesor
    ) {
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            // Canal para todos los usuarios que pueden ver cartera (contadores, admins, etc)
            new Channel('pedidos.creados'),
            // Canal para el asesor específico
            new Channel('pedidos.asesor.' . $this->asesor->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'pedido.creado';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        \Log::info('Broadcasting PedidoCreado event', [
            'pedido_id' => $this->pedido->id,
            'numero_pedido' => $this->pedido->numero_pedido,
            'asesor_id' => $this->asesor->id,
            'cliente' => $this->pedido->cliente,
        ]);

        return [
            'pedido' => [
                'id' => $this->pedido->id,
                'numero_pedido' => $this->pedido->numero_pedido,
                'cliente' => $this->pedido->cliente,
                'estado' => $this->pedido->estado,
                'forma_pago' => $this->pedido->forma_pago,
                'fecha_creacion' => $this->pedido->created_at?->toISOString(),
                'cantidad_total' => $this->pedido->cantidad_total,
                'asesora' => $this->asesor->name,
                'asesor_id' => $this->asesor->id,
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}

