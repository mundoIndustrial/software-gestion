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
        // Canal para supervisor-pedidos y otros sistemas
        $channels = [
            new Channel('supervisor-pedidos'),
            new Channel('ordenes'),
        ];
        
        \Log::info('[OrdenUpdated] Canales de broadcast', [
            'action' => $this->action,
            'canales' => array_map(fn($ch) => $ch->name, $channels),
            'total_canales' => count($channels)
        ]);
        
        return $channels;
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
        $ordenData = $this->convertirOrdenAArray();
        
        // Añadir ambos campos para compatibilidad
        if (!isset($ordenData['pedido']) && isset($ordenData['numero_pedido'])) {
            $ordenData['pedido'] = $ordenData['numero_pedido'];
        }

        // Agregar información del cliente si está disponible
        if ($this->orden instanceof \Illuminate\Database\Eloquent\Model && $this->orden->cliente) {
            $ordenData['cliente_nombre'] = $this->orden->cliente->nombre ?? $this->orden->cliente->razon_social ?? 'Sin cliente';
        } elseif (isset($ordenData['cliente_id'])) {
            // Si no está cargada, intentar obtenerla
            try {
                $cliente = \App\Models\Cliente::find($ordenData['cliente_id']);
                if ($cliente) {
                    $ordenData['cliente_nombre'] = $cliente->nombre ?? $cliente->razon_social ?? 'Sin cliente';
                }
            } catch (\Exception $e) {
                \Log::warning('Error obteniendo cliente en OrdenUpdated: ' . $e->getMessage());
            }
        }

        return [
            'orden' => $ordenData,
            'action' => $this->action,
            'changedFields' => $this->changedFields
        ];
    }

    /**
     * Convierte el objeto orden a un array
     * @return array
     */
    private function convertirOrdenAArray(): array
    {
        if ($this->orden instanceof \Illuminate\Database\Eloquent\Model) {
            return $this->orden->toArray();
        }

        if (is_array($this->orden)) {
            return $this->orden;
        }

        return (array) $this->orden;
    }
}
