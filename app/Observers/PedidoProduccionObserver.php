<?php

namespace App\Observers;

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PedidoProduccionObserver
{
    /**
     * Handle the PedidoProduccion "updated" event.
     */
    public function updated(PedidoProduccion $pedido): void
    {
        // Detectar si se asignó la fecha estimada por primera vez
        if ($pedido->wasChanged('fecha_estimada_de_entrega')) {
            
            // Solo crear notificación si la fecha fue cambiada de null a algo
            if (is_null($pedido->getOriginal('fecha_estimada_de_entrega')) && 
                !is_null($pedido->fecha_estimada_de_entrega)) {
                
                // No crear notificación para el mismo asesor que creó el pedido
                $usuarioActual = Auth::id();
                
                if ($pedido->asesor_id && $pedido->asesor_id !== $usuarioActual) {
                    // Crear notificación en la tabla notifications de Laravel
                    DB::table('notifications')->insert([
                        'id' => \Illuminate\Support\Str::uuid(),
                        'notifiable_type' => 'App\\Models\\User',
                        'notifiable_id' => $pedido->asesor_id,
                        'type' => 'App\\Notifications\\FechaEstimadaAsignada',
                        'data' => json_encode([
                            'pedido_id' => $pedido->id,
                            'numero_pedido' => $pedido->numero_pedido,
                            'fecha_estimada' => $pedido->fecha_estimada_de_entrega->format('d/m/Y'),
                            'usuario_que_genero_id' => $usuarioActual,
                            'usuario_que_genero_nombre' => Auth::user()->name ?? 'Sistema',
                        ]),
                        'read_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    \Log::info('Notificación de fecha estimada creada', [
                        'pedido_id' => $pedido->id,
                        'asesor_id' => $pedido->asesor_id,
                        'fecha_estimada' => $pedido->fecha_estimada_de_entrega,
                    ]);
                }
            }
        }
    }
}
