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
    public $changedFields; // Array de campos que cambiaron

    /**
     * Create a new event instance.
     */
    public function __construct($orden, $action = 'updated', $changedFields = null)
    {
        $this->orden = $orden;
        $this->action = $action;
        $this->changedFields = $changedFields;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn()
    {
        return [
            new Channel('supervisor-pedidos'),
            new Channel('ordenes'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'orden.updated';
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
            'changedFields' => $this->changedFields,
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
            'action' => $this->action,
            'changedFields' => $this->changedFields
        ];
    }
}
