<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrdenUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $orden;
    public $action; // 'created', 'updated', 'deleted'

    /**
     * Create a new event instance.
     */
    public function __construct($orden, $action = 'updated')
    {
        $this->orden = $orden;
        $this->action = $action;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return new Channel('ordenes');
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith()
    {
        $pedido = $this->orden->numero_pedido ?? $this->orden->pedido ?? $this->orden['numero_pedido'] ?? $this->orden['pedido'] ?? null;
        
        \Log::info('Broadcasting OrdenUpdated event', [
            'pedido' => $pedido,
            'action' => $this->action,
        ]);

        // Asegurar que el objeto orden tenga el atributo correcto para el frontend
        $ordenData = $this->orden instanceof \Illuminate\Database\Eloquent\Model 
            ? $this->orden->toArray() 
            : $this->orden;
        
        // Añadir ambos campos para compatibilidad
        if (!isset($ordenData['pedido']) && isset($ordenData['numero_pedido'])) {
            $ordenData['pedido'] = $ordenData['numero_pedido'];
        }

        return [
            'orden' => $ordenData,
            'action' => $this->action
        ];
    }
}
