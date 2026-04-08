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
    public $consecutivoReciboId;
    public $encargado;
    public $procesoId;
    public $nombrePrenda;
    public $cliente;
    public $procesoUpdatedAt;
    public $encargadoRol;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($pedidoId, $prendaId, $numeroRecibo, $encargado, $procesoId, $nombrePrenda = null, $procesoUpdatedAt = null, $consecutivoReciboId = null, $cliente = null, $encargadoRol = null)
    {
        $this->pedidoId = $pedidoId;
        $this->prendaId = $prendaId;
        $this->numeroRecibo = $numeroRecibo;
        $this->encargado = $encargado;
        $this->procesoId = $procesoId;
        $this->nombrePrenda = $nombrePrenda;
        $this->procesoUpdatedAt = $procesoUpdatedAt;
        $this->consecutivoReciboId = $consecutivoReciboId;
        $this->cliente = $cliente;
        $this->encargadoRol = $encargadoRol;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return [
            new Channel('operarios.corte'),
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
            'consecutivo_recibo_id' => $this->consecutivoReciboId,
            'encargado' => $this->encargado,
            'encargado_rol' => $this->encargadoRol,
            'proceso_id' => $this->procesoId,
            'nombre_prenda' => $this->nombrePrenda,
            'cliente' => $this->cliente,
            'proceso_updated_at' => $this->procesoUpdatedAt
        ]);

        return [
            'pedido_id' => $this->pedidoId,
            'prenda_id' => $this->prendaId,
            'numero_recibo' => $this->numeroRecibo,
            'consecutivo_recibo_id' => $this->consecutivoReciboId,
            'encargado' => $this->encargado,
            'encargado_rol' => $this->encargadoRol,
            'proceso_id' => $this->procesoId,
            'nombre_prenda' => $this->nombrePrenda,
            'cliente' => $this->cliente,
            'proceso_updated_at' => $this->procesoUpdatedAt,
            'mensaje' => "El recibo #{$this->numeroRecibo} ({$this->nombrePrenda}) ha sido asignado a {$this->encargado} para costura"
        ];
    }
}
