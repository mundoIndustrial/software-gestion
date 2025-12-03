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
     * Handle the ProcesoPrenda "deleting" event.
     * Actualizar el área del pedido cuando se elimina un proceso
     */
    public function deleting(ProcesoPrenda $procesoPrenda): void
    {
        // Actualizar el área del pedido al próximo proceso disponible
        $this->actualizarAreaAlEliminar($procesoPrenda);
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

    /**
     * Actualizar el área del pedido cuando se elimina un proceso
     * Busca el ÚLTIMO proceso por fecha_inicio (sin importar estado)
     */
    private function actualizarAreaAlEliminar(ProcesoPrenda $procesoPrenda): void
    {
        try {
            $numeroPedido = $procesoPrenda->numero_pedido;
            $procesoEliminado = $procesoPrenda->proceso;
            
            if (!$numeroPedido) {
                return;
            }

            // Obtener el pedido
            $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
            
            if (!$pedido) {
                return;
            }

            \Log::info("🔍 Buscando último proceso después de eliminar", [
                'numero_pedido' => $numeroPedido,
                'proceso_eliminado' => $procesoEliminado,
                'area_actual' => $pedido->area
            ]);

            // Obtener el ÚLTIMO proceso por fecha_inicio (sin importar estado)
            $ultimoProceso = ProcesoPrenda::where('numero_pedido', $numeroPedido)
                ->where('id', '!=', $procesoPrenda->id)  // Excluir el que se está eliminando
                ->orderBy('fecha_inicio', 'DESC')  // Más reciente primero
                ->orderBy('id', 'DESC')
                ->first();

            \Log::info("📋 Procesos disponibles después de eliminar", [
                'numero_pedido' => $numeroPedido,
                'procesos_totales' => ProcesoPrenda::where('numero_pedido', $numeroPedido)->count(),
                'ultimo_proceso' => $ultimoProceso ? $ultimoProceso->proceso : 'NINGUNO',
                'estado_ultimo' => $ultimoProceso ? $ultimoProceso->estado_proceso : 'N/A'
            ]);

            if ($ultimoProceso) {
                $nuevaArea = $ultimoProceso->proceso;
                
                // Actualizar siempre (sin importar si es el mismo valor)
                $pedido->update([
                    'area' => $nuevaArea,
                    'fecha_ultimo_proceso' => $ultimoProceso->fecha_fin ?? $ultimoProceso->fecha_inicio
                ]);
                
                \Log::info("✅ Área actualizada al eliminar proceso", [
                    'numero_pedido' => $numeroPedido,
                    'proceso_eliminado' => $procesoEliminado,
                    'area_anterior' => $pedido->area,
                    'area_nueva' => $nuevaArea,
                    'estado_nuevo' => $ultimoProceso->estado_proceso
                ]);
            } else {
                \Log::warning("⚠️ No hay procesos restantes después de eliminar", [
                    'numero_pedido' => $numeroPedido,
                    'proceso_eliminado' => $procesoEliminado
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('❌ Error actualizando área al eliminar proceso: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
