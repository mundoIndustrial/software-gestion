<?php

namespace App\Events;

use App\Models\PedidoObservacionesDespacho;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ObservacionDespachoCreada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $observacion;
    public $pedidoId;
    public $action; // 'created', 'updated', 'deleted'

    /**
     * Create a new event instance.
     */
    public function __construct(PedidoObservacionesDespacho $observacion, string $action = 'created')
    {
        $this->observacion = $observacion;
        $this->pedidoId = $observacion->pedido_produccion_id;
        $this->action = $action;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            // Canal público para el pedido específico
            new Channel('pedido.' . $this->pedidoId),
            // Canal público general para despacho
            new Channel('despacho.observaciones'),
            // Canal público general para asesores
            new Channel('asesores.observaciones'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'observacion.despacho';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'observacion' => [
                'id' => (string) $this->observacion->uuid,
                'pedido_produccion_id' => $this->observacion->pedido_produccion_id,
                'contenido' => $this->observacion->contenido,
                'usuario_id' => $this->observacion->usuario_id,
                'usuario_nombre' => $this->observacion->usuario_nombre,
                'usuario_rol' => $this->observacion->usuario_rol,
                'estado' => (int) $this->observacion->estado,
                'created_at' => optional($this->observacion->created_at)->toISOString(),
                'updated_at' => optional($this->observacion->updated_at)->toISOString(),
            ],
            'action' => $this->action,
            'pedido_id' => $this->pedidoId,
            'timestamp' => now()->toISOString(),
        ];
    }
}
