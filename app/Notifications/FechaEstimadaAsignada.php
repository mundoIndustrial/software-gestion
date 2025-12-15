<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FechaEstimadaAsignada extends Notification
{
    use Queueable;

    public $pedidoData;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $pedidoData)
    {
        $this->pedidoData = $pedidoData;
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
            ->line('Se ha asignado la fecha estimada de entrega para un pedido.')
            ->line('Pedido: #' . $this->pedidoData['numero_pedido'])
            ->line('Fecha: ' . $this->pedidoData['fecha_estimada'])
            ->action('Ver Pedido', url('/asesores/pedidos'))
            ->line('Gracias por usar nuestra aplicación.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'pedido_id' => $this->pedidoData['pedido_id'],
            'numero_pedido' => $this->pedidoData['numero_pedido'],
            'fecha_estimada' => $this->pedidoData['fecha_estimada'],
            'usuario_que_genero_nombre' => $this->pedidoData['usuario_que_genero_nombre'] ?? 'Sistema',
        ];
    }
}
