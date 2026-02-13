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

    public $pedidoId;
    public $numeroPedido;
    public $cliente;
    public $asesorId;
    public $asesorNombre;
    public $estado;
    public $formaPago;
    public $cantidadTotal;
    public $fechaCreacion;

    /**
     * Create a new event instance.
     */
    public function __construct(
        PedidoProduccion $pedido,
        User $asesor
    ) {
        // Extraer datos del pedido y asesor para evitar problemas de serialización
        $this->pedidoId = $pedido->id;
        $this->numeroPedido = $pedido->numero_pedido;
        $this->cliente = $pedido->cliente;
        $this->asesorId = $asesor->id;
        $this->asesorNombre = $asesor->name;
        $this->estado = $pedido->estado;
        $this->formaPago = $pedido->forma_de_pago;
        $this->cantidadTotal = $pedido->cantidad_total;
        $this->fechaCreacion = $pedido->created_at?->toISOString();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            // Canal para todos los usuarios que pueden ver cartera
            new Channel('pedidos.creados'),
            // Canal para el asesor específico
            new Channel('pedidos.asesor.' . $this->asesorId),
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
            'pedido_id' => $this->pedidoId,
            'numero_pedido' => $this->numeroPedido,
            'asesor_id' => $this->asesorId,
            'cliente' => $this->cliente,
        ]);

        return [
            'pedido' => [
                'id' => $this->pedidoId,
                'numero_pedido' => $this->numeroPedido,
                'cliente' => $this->cliente,
                'estado' => $this->estado,
                'forma_pago' => $this->formaPago,
                'fecha_creacion' => $this->fechaCreacion,
                'cantidad_total' => $this->cantidadTotal,
                'asesora' => $this->asesorNombre,
                'asesor_id' => $this->asesorId,
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}


