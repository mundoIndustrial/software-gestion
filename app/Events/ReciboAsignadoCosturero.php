<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReciboAsignadoCosturero implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $pedidoId;
    public $prendaId;
    public $numeroRecibo;
    public $nombrePrenda;
    public $encargado;
    public $procesoId;
    public $nombreCosturero;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($pedidoId, $prendaId, $numeroRecibo, $nombrePrenda, $encargado, $procesoId, $nombreCosturero)
    {
        $this->pedidoId = $pedidoId;
        $this->prendaId = $prendaId;
        $this->numeroRecibo = $numeroRecibo;
        $this->nombrePrenda = $nombrePrenda;
        $this->encargado = $encargado;
        $this->procesoId = $procesoId;
        $this->nombreCosturero = $nombreCosturero;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // Normalizar el nombre del costurero para que sea válido para Pusher
        $nombreNormalizado = $this->normalizarNombreCanal($this->nombreCosturero);
        
        // Canal privado específico para el costurero
        return new PrivateChannel('costurero.' . $nombreNormalizado);
    }

    /**
     * Normaliza el nombre para que sea válido para canales de Pusher
     */
    private function normalizarNombreCanal($nombre)
    {
        // Convertir a minúsculas, reemplazar espacios y caracteres especiales
        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $nombre));
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'recibo.asignado';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        \Log::info('Broadcasting ReciboAsignadoCosturero event', [
            'pedido_id' => $this->pedidoId,
            'prenda_id' => $this->prendaId,
            'numero_recibo' => $this->numeroRecibo,
            'nombre_prenda' => $this->nombrePrenda,
            'encargado' => $this->encargado,
            'proceso_id' => $this->procesoId,
            'nombre_costurero' => $this->nombreCosturero,
        ]);

        return [
            'pedido_id' => $this->pedidoId,
            'prenda_id' => $this->prendaId,
            'numero_recibo' => $this->numeroRecibo,
            'nombre_prenda' => $this->nombrePrenda,
            'encargado' => $this->encargado,
            'proceso_id' => $this->procesoId,
            'nombre_costurero' => $this->nombreCosturero,
            'mensaje' => "Tienes un nuevo recibo de costura asignado: {$this->nombrePrenda} #{$this->numeroRecibo}"
        ];
    }
}
