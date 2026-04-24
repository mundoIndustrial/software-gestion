<?php

namespace App\Observers;

use App\Models\BodegaDetalleTalla;
use Illuminate\Support\Facades\Log;

class BodegaDetalleTallaObserver
{
    /**
     * Handle the BodegaDetalleTalla "creating" event.
     * Se ejecuta antes de crear el registro.
     */
    public function creating(BodegaDetalleTalla $bodegaDetalleTalla): void
    {
        // Si se está creando con estado Pendiente y no tiene fecha_pendiente, asignarla
        if ($bodegaDetalleTalla->estado_bodega === 'Pendiente' && !$bodegaDetalleTalla->fecha_pendiente) {
            $bodegaDetalleTalla->fecha_pendiente = now();
            
            Log::info('[BodegaDetalleTallaObserver] Nueva fila creada con estado Pendiente', [
                'numero_pedido' => $bodegaDetalleTalla->numero_pedido,
                'talla' => $bodegaDetalleTalla->talla,
                'fecha_pendiente' => $bodegaDetalleTalla->fecha_pendiente,
            ]);
        }
    }

    /**
     * Handle the BodegaDetalleTalla "created" event.
     */
    public function created(BodegaDetalleTalla $bodegaDetalleTalla): void
    {
        //
    }

    /**
     * Handle the BodegaDetalleTalla "updating" event.
     * Se ejecuta antes de actualizar el registro.
     */
    public function updating(BodegaDetalleTalla $bodegaDetalleTalla): void
    {
        // Verificar si el estado está siendo cambiado a "Pendiente"
        $estadoAnterior = $bodegaDetalleTalla->getOriginal('estado_bodega');
        $estadoNuevo = $bodegaDetalleTalla->estado_bodega;
        
        // Si el estado cambió a Pendiente y no tiene fecha_pendiente, asignarla
        if ($estadoNuevo === 'Pendiente' && $estadoAnterior !== 'Pendiente') {
            $bodegaDetalleTalla->fecha_pendiente = now();
            
            Log::info('[BodegaDetalleTallaObserver] Estado cambiado a Pendiente', [
                'numero_pedido' => $bodegaDetalleTalla->numero_pedido,
                'talla' => $bodegaDetalleTalla->talla,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $estadoNuevo,
                'fecha_pendiente' => $bodegaDetalleTalla->fecha_pendiente,
            ]);
        }
        
        // Si el estado está siendo cambiado a algo diferente de Pendiente, limpiar la fecha
        // (opcional, depende de la lógica de negocio)
        // if ($estadoNuevo !== 'Pendiente' && $estadoAnterior === 'Pendiente') {
        //     $bodegaDetalleTalla->fecha_pendiente = null;
        // }
    }

    /**
     * Handle the BodegaDetalleTalla "updated" event.
     */
    public function updated(BodegaDetalleTalla $bodegaDetalleTalla): void
    {
        //
    }

    /**
     * Handle the BodegaDetalleTalla "deleted" event.
     */
    public function deleted(BodegaDetalleTalla $bodegaDetalleTalla): void
    {
        //
    }

    /**
     * Handle the BodegaDetalleTalla "restored" event.
     */
    public function restored(BodegaDetalleTalla $bodegaDetalleTalla): void
    {
        //
    }

    /**
     * Handle the BodegaDetalleTalla "force deleted" event.
     */
    public function forceDeleted(BodegaDetalleTalla $bodegaDetalleTalla): void
    {
        //
    }
}

