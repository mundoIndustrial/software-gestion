<?php

namespace App\Observers;

use App\Models\ProcesoPrenda;
use App\Models\PedidoProduccion;

class ProcesoPrendaObserver
{
    /**
     * Handle the ProcesoPrenda "created" event.
     */
    public function created(ProcesoPrenda $procesoPrenda): void
    {
        // Actualizar el área del pedido basado en el último proceso registrado
        $this->actualizarAreaPedido($procesoPrenda);
    }

    /**
     * Handle the ProcesoPrenda "updated" event.
     */
    public function updated(ProcesoPrenda $procesoPrenda): void
    {
        // Si cambió la fecha/hora, podría afectar el orden, así que actualizar
        if ($procesoPrenda->isDirty('created_at')) {
            $this->actualizarAreaPedido($procesoPrenda);
        }
    }

    /**
     * Actualizar el área del pedido al último proceso
     */
    private function actualizarAreaPedido(ProcesoPrenda $procesoPrenda): void
    {
        try {
            // Obtener la prenda relacionada
            $prenda = $procesoPrenda->prenda;
            
            if (!$prenda) {
                return;
            }

            // Obtener el pedido relacionado
            $pedido = $prenda->pedidoProduccion;
            
            if (!$pedido) {
                return;
            }

            // Obtener el último proceso de TODAS las prendas del pedido, ordenado por fecha más reciente
            $ultimoProceso = ProcesoPrenda::whereIn('prenda_pedido_id', $pedido->prendas()->pluck('id'))
                ->orderBy('created_at', 'desc')
                ->first();

            if ($ultimoProceso) {
                // Actualizar el área y la fecha del último proceso del pedido
                $pedido->update([
                    'area' => $ultimoProceso->proceso,
                    'fecha_ultimo_proceso' => $ultimoProceso->fecha_fin ?? $ultimoProceso->created_at
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error actualizando área del pedido: ' . $e->getMessage());
        }
    }
}
