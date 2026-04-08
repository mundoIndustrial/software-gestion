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
        // Si cambió estado_proceso, created_at, fecha_inicio o fecha_fin, actualizar el área
        if ($procesoPrenda->isDirty(['estado_proceso', 'created_at', 'fecha_inicio', 'fecha_fin', 'proceso'])) {
            \Log::info(' [Observer] Cambio detectado en proceso', [
                'proceso_id' => $procesoPrenda->id,
                'numero_pedido' => $procesoPrenda->numero_pedido,
                'campos_modificados' => $procesoPrenda->getDirty(),
            ]);
            
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
     * Actualizar el área del pedido basado en el proceso actual activo
     */
    private function actualizarAreaPedido(ProcesoPrenda $procesoPrenda): void
    {
        try {
            $numeroPedido = $procesoPrenda->numero_pedido;
            
            if (!$numeroPedido) {
                return;
            }

            // Si el proceso es 'Entrega', no recalcular el área (es un estado final)
            // Se mantiene como 'Entrega' explícitamente
            if ($procesoPrenda->proceso && strtolower(trim($procesoPrenda->proceso)) === 'entrega') {
                \Log::info(' [Observer] Ignorando Entrega - es un estado final', [
                    'numero_pedido' => $numeroPedido,
                    'proceso' => $procesoPrenda->proceso,
                ]);
                return;
            }

            // Obtener el pedido
            $pedido = PedidoProduccion::where('numero_pedido', $numeroPedido)->first();
            
            if (!$pedido) {
                return;
            }

            \Log::info(' [Observer] Actualizando área del pedido', [
                'numero_pedido' => $numeroPedido,
                'area_actual' => $pedido->area,
            ]);

            // Obtener todos los procesos del pedido EXCEPTO 'Entrega'
            $procesos = ProcesoPrenda::where('numero_pedido', $numeroPedido)
                ->whereRaw('LOWER(TRIM(proceso)) != ?', ['entrega'])
                ->get();

            if ($procesos->isEmpty()) {
                return;
            }

            // Orden de prioridad de procesos
            $procesosPrioritarios = [
                'Despacho',
                'Insumos y Telas',
                'Costura',
                'Corte',
                'Control Calidad',
                'Creación de Orden',
                'tcc'
            ];

            // Prioridad 1: Buscar proceso "En Progreso"
            foreach ($procesosPrioritarios as $nombreProceso) {
                $proceso = $procesos
                    ->where('estado_proceso', 'En Progreso')
                    ->where('proceso', $nombreProceso)
                    ->first();
                
                if ($proceso) {
                    $pedido->update([
                        'area' => $proceso->proceso,
                        'fecha_ultimo_proceso' => $proceso->fecha_fin ?? $proceso->created_at
                    ]);
                    
                    \Log::info(' [Observer] Área actualizada (En Progreso)', [
                        'numero_pedido' => $numeroPedido,
                        'area_nueva' => $proceso->proceso,
                        'estado_proceso' => $proceso->estado_proceso,
                    ]);

                    // Actualizar el área en consecutivos_recibos_pedidos
                    if ($procesoPrenda->prenda_pedido_id) {
                        $this->actualizarAreaEnConsecutivos($pedido->id, $procesoPrenda->prenda_pedido_id, $proceso->proceso, $proceso->proceso);
                    } else {
                        $this->actualizarAreaEnConsecutivos($pedido->id, null, $proceso->proceso, $proceso->proceso);
                    }
                    
                    return;
                }
            }

            // Prioridad 2: Buscar proceso "Pendiente"
            foreach ($procesosPrioritarios as $nombreProceso) {
                $proceso = $procesos
                    ->where('estado_proceso', 'Pendiente')
                    ->where('proceso', $nombreProceso)
                    ->first();
                
                if ($proceso) {
                    $pedido->update([
                        'area' => $proceso->proceso,
                        'fecha_ultimo_proceso' => $proceso->fecha_fin ?? $proceso->created_at
                    ]);
                    
                    \Log::info(' [Observer] Área actualizada (Pendiente)', [
                        'numero_pedido' => $numeroPedido,
                        'area_nueva' => $proceso->proceso,
                        'estado_proceso' => $proceso->estado_proceso,
                    ]);

                    // Actualizar el área en consecutivos_recibos_pedidos
                    if ($procesoPrenda->prenda_pedido_id) {
                        $this->actualizarAreaEnConsecutivos($pedido->id, $procesoPrenda->prenda_pedido_id, $proceso->proceso, $proceso->proceso);
                    } else {
                        $this->actualizarAreaEnConsecutivos($pedido->id, null, $proceso->proceso, $proceso->proceso);
                    }
                    
                    return;
                }
            }

            // Prioridad 3: Último proceso creado
            $ultimoProceso = $procesos->sortByDesc('created_at')->first();
            
            if ($ultimoProceso) {
                $pedido->update([
                    'area' => $ultimoProceso->proceso,
                    'fecha_ultimo_proceso' => $ultimoProceso->fecha_fin ?? $ultimoProceso->created_at
                ]);
                
                \Log::info(' [Observer] Área actualizada (Último proceso)', [
                    'numero_pedido' => $numeroPedido,
                    'area_nueva' => $ultimoProceso->proceso,
                    'estado_proceso' => $ultimoProceso->estado_proceso,
                ]);

                // NUEVO: Actualizar el área en consecutivos_recibos_pedidos
                if ($procesoPrenda->prenda_pedido_id) {
                    $this->actualizarAreaEnConsecutivos($pedido->id, $procesoPrenda->prenda_pedido_id, $ultimoProceso->proceso, $ultimoProceso->proceso);
                } else {
                    // Si no tiene prenda específica, actualizar todos los consecutivos del pedido
                    $this->actualizarAreaEnConsecutivos($pedido->id, null, $ultimoProceso->proceso, $ultimoProceso->proceso);
                }
            }
        } catch (\Exception $e) {
            \Log::error(' Error actualizando área del pedido: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
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

            \Log::info(" Buscando último proceso después de eliminar", [
                'numero_pedido' => $numeroPedido,
                'proceso_eliminado' => $procesoEliminado,
                'area_actual' => $pedido->area
            ]);

            // Obtener el ÚLTIMO proceso por fecha_inicio (sin importar estado)
            // IMPORTANTE: Usar whereNull('deleted_at') para excluir procesos eliminados (soft delete)
            // IMPORTANTE: Excluir procesos 'Entrega' pues es un estado final
            $ultimoProceso = ProcesoPrenda::where('numero_pedido', $numeroPedido)
                ->where('id', '!=', $procesoPrenda->id)  // Excluir el que se está eliminando
                ->whereNull('deleted_at')  // Excluir procesos ya eliminados (soft delete)
                ->whereRaw('LOWER(TRIM(proceso)) != ?', ['entrega'])  // Excluir Entrega
                ->orderBy('fecha_inicio', 'DESC')  // Más reciente primero
                ->orderBy('id', 'DESC')
                ->first();

            \Log::info(" Procesos disponibles después de eliminar", [
                'numero_pedido' => $numeroPedido,
                'procesos_totales' => ProcesoPrenda::where('numero_pedido', $numeroPedido)->whereNull('deleted_at')->count(),
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
                
                \Log::info(" Área actualizada al eliminar proceso", [
                    'numero_pedido' => $numeroPedido,
                    'proceso_eliminado' => $procesoEliminado,
                    'area_anterior' => $pedido->area,
                    'area_nueva' => $nuevaArea,
                    'estado_nuevo' => $ultimoProceso->estado_proceso
                ]);

                // NUEVO: Actualizar el área en consecutivos_recibos_pedidos
                if ($procesoPrenda->prenda_pedido_id) {
                    $this->actualizarAreaEnConsecutivos($pedido->id, $procesoPrenda->prenda_pedido_id, $nuevaArea, $procesoPrenda->proceso);
                } else {
                    // Si no tiene prenda específica, actualizar todos los consecutivos del pedido
                    $this->actualizarAreaEnConsecutivos($pedido->id, null, $nuevaArea, $procesoPrenda->proceso);
                }
            } else {
                \Log::warning(" No hay procesos restantes después de eliminar", [
                    'numero_pedido' => $numeroPedido,
                    'proceso_eliminado' => $procesoEliminado
                ]);
            }
        } catch (\Exception $e) {
            \Log::error(' Error actualizando área al eliminar proceso: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Actualizar el área en la tabla consecutivos_recibos_pedidos
     */
    private function actualizarAreaEnConsecutivos($pedidoProduccionId, $prendaId = null, $nuevaArea, $proceso = null): void
    {
        try {
            // NO actualizar cuando el proceso es "Control de Calidad" 
            // porque el ReciboCosturaController ya lo hace de forma específica
            if ($proceso && strtolower(trim($proceso)) === 'control de calidad') {
                \Log::info(" Observer ignorando Control de Calidad - será actualizado específicamente por el controlador", [
                    'pedido_produccion_id' => $pedidoProduccionId,
                    'prenda_id' => $prendaId,
                    'proceso' => $proceso
                ]);
                return;
            }

            // NO actualizar cuando el proceso es "Entrega"
            // porque es un estado final y el ControlCalidadController ya lo hace explícitamente
            if ($proceso && strtolower(trim($proceso)) === 'entrega') {
                \Log::info(" Observer ignorando Entrega - será actualizado específicamente por el controlador de Control de Calidad", [
                    'pedido_produccion_id' => $pedidoProduccionId,
                    'prenda_id' => $prendaId,
                    'proceso' => $proceso
                ]);
                return;
            }

            $query = \DB::table('consecutivos_recibos_pedidos')
                ->where('pedido_produccion_id', $pedidoProduccionId);
            
            if ($prendaId) {
                $query->where('prenda_id', $prendaId);
            }
            
            $actualizado = $query->update(['area' => $nuevaArea]);
            
            \Log::info(" Área actualizada en consecutivos_recibos_pedidos", [
                'pedido_produccion_id' => $pedidoProduccionId,
                'prenda_id' => $prendaId,
                'nueva_area' => $nuevaArea,
                'proceso' => $proceso,
                'registros_actualizados' => $actualizado
            ]);
        } catch (\Exception $e) {
            \Log::error(' Error actualizando área en consecutivos: ' . $e->getMessage());
        }
    }
}
