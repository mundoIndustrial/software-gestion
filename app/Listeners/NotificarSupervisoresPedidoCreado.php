<?php

namespace App\Listeners;

use App\Events\PedidoCreado;
use App\Models\User;
use App\Notifications\PedidoCreado as NotificacionPedidoCreado;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class NotificarSupervisoresPedidoCreado implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PedidoCreado $event): void
    {
        // Obtener todos los usuarios y filtrar por rol supervisor_pedido
        $supervisores = User::all()->filter(function ($user) {
            return $user->hasRole('supervisor_pedido');
        })->values();

        // Enviar notificación a cada supervisor
        foreach ($supervisores as $supervisor) {
            $supervisor->notify(new NotificacionPedidoCreado($event->pedido, $event->asesor));
        }

        // Log de la acción
        \Log::info(' Notificaciones de pedido enviadas a supervisores', [
            'pedido_id' => $event->pedido->id,
            'numero_pedido' => $event->pedido->numero_pedido,
            'asesor' => $event->asesor->name,
            'supervisores_notificados' => $supervisores->count()
        ]);
    }
}
