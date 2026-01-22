<?php

namespace App\Application\Pedidos\Listeners;

use App\Domain\Pedidos\Events\PedidoCreado;
use Illuminate\Support\Facades\Log;

/**
 * Listener: Cuando se crea un Pedido
 * 
 * Reacciona a eventos de dominio
 */
class PedidoCreadoListener
{
    public function handle(PedidoCreado $evento): void
    {
        Log::info('Pedido creado', [
            'numero' => $evento->numero,
            'cliente_id' => $evento->clienteId,
            'total_prendas' => $evento->totalPrendas,
        ]);

        // TODO: Aquí puedes:
        // - Enviar email al cliente
        // - Actualizar caché
        // - Sincronizar con otros sistemas
        // - Crear notificaciones
    }
}
