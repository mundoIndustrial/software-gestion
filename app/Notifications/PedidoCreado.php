<?php

namespace App\Notifications;

use App\Models\PedidoProduccion;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PedidoCreado extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public PedidoProduccion $pedido,
        public User $asesor
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'pedido_id' => $this->pedido->id,
            'numero_pedido' => $this->pedido->numero_pedido,
            'cliente' => $this->pedido->cliente,
            'asesor_id' => $this->asesor->id,
            'asesor_nombre' => $this->asesor->name,
            'cantidad_prendas' => $this->pedido->prendas()->count(),
            'titulo' => "Nuevo pedido #{$this->pedido->numero_pedido} creado",
            'mensaje' => "El asesor {$this->asesor->name} ha creado un pedido para {$this->pedido->cliente}",
            'tipo' => 'pedido_creado'
        ];
    }
}
