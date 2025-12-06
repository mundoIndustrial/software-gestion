<?php

namespace App\Notifications;

use App\Models\Cotizacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class CotizacionRechazadaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Cotizacion $cotizacion,
        protected string $observaciones = ''
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
            ->subject('Cotización Requiere Correcciones')
            ->greeting("Hola {$notifiable->name},")
            ->line("Tu cotización ha sido **devuelta** por el aprobador de cotizaciones requiere correcciones.")
            ->line("**Detalles de la Cotización:**")
            ->line("- **Número**: {$this->cotizacion->numero_cotizacion}")
            ->line("- **Cliente**: " . ($this->cotizacion->cliente ?? 'N/A'))
            ->line("- **Estado**: Requiere Correcciones")
            ->line("**Observaciones:**")
            ->line($this->observaciones)
            ->line("Por favor, revisa la cotización, realiza las correcciones necesarias y reenvíala.")
            ->action('Editar Cotización', url('/cotizaciones/' . $this->cotizacion->id . '/edit'))
            ->salutation('Saludos cordiales,');
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => 'Cotización Requiere Correcciones',
            'body' => "Tu cotización #{$this->cotizacion->numero_cotizacion} requiere correcciones. Observaciones: {$this->observaciones}",
            'cotizacion_id' => $this->cotizacion->id,
            'tipo' => 'cotizacion_rechazada',
            'observaciones' => $this->observaciones,
            'url' => url('/cotizaciones/' . $this->cotizacion->id . '/edit'),
        ]);
    }
}
