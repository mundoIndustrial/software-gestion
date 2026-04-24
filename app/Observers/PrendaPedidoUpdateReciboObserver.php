<?php

namespace App\Observers;

use App\Models\PrendaPedido;
use Illuminate\Support\Facades\DB;

class PrendaPedidoUpdateReciboObserver
{
    /**
     * Handle the PrendaPedido "created" event.
     */
    public function created(PrendaPedido $prendaPedido): void
    {
        $this->actualizarRecibos($prendaPedido);
    }

    /**
     * Handle the PrendaPedido "updated" event.
     */
    public function updated(PrendaPedido $prendaPedido): void
    {
        $this->actualizarRecibos($prendaPedido);
    }

    /**
     * Actualizar ultima_actividad en todos los recibos del pedido
     */
    private function actualizarRecibos(PrendaPedido $prendaPedido): void
    {
        if (!$prendaPedido->pedido_produccion_id) {
            return;
        }

        DB::table('consecutivos_recibos_pedidos')
            ->where('pedido_produccion_id', $prendaPedido->pedido_produccion_id)
            ->update(['ultima_actividad' => now()]);
    }

    /**
     * Handle the PrendaPedido "deleted" event.
     */
    public function deleted(PrendaPedido $prendaPedido): void
    {
        //
    }

    /**
     * Handle the PrendaPedido "restored" event.
     */
    public function restored(PrendaPedido $prendaPedido): void
    {
        //
    }

    /**
     * Handle the PrendaPedido "force deleted" event.
     */
    public function forceDeleted(PrendaPedido $prendaPedido): void
    {
        //
    }
}
