<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EncargadoCosturaAsignado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $pedidoId;
    public $prendaId;
    public $numeroRecibo;
    public $encargado;
    public $procesoId;
    public $nombrePrenda;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($pedidoId, $prendaId, $numeroRecibo, $encargado, $procesoId, $nombrePrenda = null)
    {
        $this->pedidoId = $pedidoId;
        $this->prendaId = $prendaId;
        $this->numeroRecibo = $numeroRecibo;
        $this->encargado = $encargado;
        $this->procesoId = $procesoId;
        $this->nombrePrenda = $nombrePrenda;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('operarios.corte');
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'encargado.costura.asignado';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        \Log::info('Broadcasting EncargadoCosturaAsignado event', [
            'pedido_id' => $this->pedidoId,
            'prenda_id' => $this->prendaId,
            'numero_recibo' => $this->numeroRecibo,
            'encargado' => $this->encargado,
            'proceso_id' => $this->procesoId,
            'nombre_prenda' => $this->nombrePrenda
        ]);

        return [
            'pedido_id' => $this->pedidoId,
            'prenda_id' => $this->prendaId,
            'numero_recibo' => $this->numeroRecibo,
            'encargado' => $this->encargado,
            'proceso_id' => $this->procesoId,
            'nombre_prenda' => $this->nombrePrenda,
            'mensaje' => "El recibo #{$this->numeroRecibo} ({$this->nombrePrenda}) ha sido asignado a {$this->encargado} para costura"
        ];
    }
}
