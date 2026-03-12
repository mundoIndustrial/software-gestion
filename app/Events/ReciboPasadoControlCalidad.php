<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReciboPasadoControlCalidad implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $pedidoId;
    public $prendaId;
    public $numeroRecibo;
    public $nombrePrenda;
    public $tipoRecibo;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($pedidoId, $prendaId, $numeroRecibo, $nombrePrenda, $tipoRecibo)
    {
        $this->pedidoId = $pedidoId;
        $this->prendaId = $prendaId;
        $this->numeroRecibo = $numeroRecibo;
        $this->nombrePrenda = $nombrePrenda;
        $this->tipoRecibo = $tipoRecibo;

        \Log::info('[ReciboPasadoControlCalidad] Evento creado', [
            'pedido_id' => $this->pedidoId,
            'prenda_id' => $this->prendaId,
            'numero_recibo' => $this->numeroRecibo,
            'nombre_prenda' => $this->nombrePrenda,
            'tipo_recibo' => $this->tipoRecibo
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return [
            new Channel('recibos-costura'),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'recibo.pasado.control.calidad';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'pedido_id' => $this->pedidoId,
            'prenda_id' => $this->prendaId,
            'numero_recibo' => $this->numeroRecibo,
            'nombre_prenda' => $this->nombrePrenda,
            'tipo_recibo' => $this->tipoRecibo,
            'accion' => 'eliminar_vista_costurero',
            'mensaje' => "El recibo #{$this->numeroRecibo} ({$this->nombrePrenda}) ha pasado a Control de Calidad y ya no está disponible en costura",
            'timestamp' => now()->toISOString()
        ];
    }
}
