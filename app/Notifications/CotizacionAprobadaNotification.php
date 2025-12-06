<?php

namespace App\Notifications;

use App\Models\Cotizacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class CotizacionAprobadaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Cotizacion $cotizacion
    ) {
        $this->queue = 'notifications';
        $this->tries = 3;
        $this->backoff = [10, 30, 60];
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('¡Cotización Aprobada!')
            ->greeting("Hola {$notifiable->name},")
            ->line("Tu cotización ha sido **aprobada** por el aprobador de cotizaciones.")
            ->line("**Detalles de la Cotización:**")
            ->line("- **Número**: {$this->cotizacion->numero_cotizacion}")
            ->line("- **Cliente**: " . ($this->cotizacion->cliente ? $this->cotizacion->cliente : 'N/A'))
            ->line("- **Estado**: Aprobada")
            ->line("- **Fecha de Aprobación**: " . now()->format('d/m/Y h:i A'))
            ->line("El cliente puede proceder con su pedido en el sistema.")
            ->action('Ver Cotización', url('/cotizaciones/' . $this->cotizacion->id))
            ->salutation('Saludos cordiales,');
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => '¡Cotización Aprobada!',
            'body' => "Tu cotización #{$this->cotizacion->numero_cotizacion} ha sido aprobada correctamente.",
            'cotizacion_id' => $this->cotizacion->id,
            'tipo' => 'cotizacion_aprobada',
            'url' => url('/cotizaciones/' . $this->cotizacion->id),
        ]);
    }
}
