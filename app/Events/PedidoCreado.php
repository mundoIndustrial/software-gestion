<?php

namespace App\Events;

use App\Models\PedidoProduccion;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PedidoCreado
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
}
