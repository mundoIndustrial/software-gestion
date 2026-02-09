<?php

namespace App\Observers;

use App\Jobs\BroadcastPedidoCreado;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PedidoProduccionObserver
{
    /**
     * Handle the PedidoProduccion "created" event.
     */
    public function created(PedidoProduccion $pedido): void
    {
        try {
            $userId = Auth::id();
            
            if (!$userId) {
                Log::warning('[PedidoProduccionObserver] No authenticated user when pedido created', [
                    'pedido_id' => $pedido->id,
                ]);
                return;
            }

            Log::info('[PedidoProduccionObserver] Pedido creado, disparando broadcast', [
                'pedido_id' => $pedido->id,
                'numero_pedido' => $pedido->numero_pedido,
                'asesor_id' => $userId,
            ]);

            // Dispatchear job asíncrono para broadcasting
            BroadcastPedidoCreado::dispatch($pedido->id, $userId);

        } catch (\Exception $e) {
            Log::error('[PedidoProduccionObserver] Error en observer', [
                'pedido_id' => $pedido->id,
                'error' => $e->getMessage(),
            ]);
            // No lanzar excepción - no queremos bloquear la creación del pedido
        }
    }

    /**
     * Handle the PedidoProduccion "updated" event.
     */
    public function updated(PedidoProduccion $pedido): void
    {
        //
    }

    /**
     * Handle the PedidoProduccion "deleted" event.
     */
    public function deleted(PedidoProduccion $pedido): void
    {
        //
    }
}
