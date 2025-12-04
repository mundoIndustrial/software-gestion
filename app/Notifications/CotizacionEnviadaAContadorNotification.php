<?php

namespace App\Notifications;

use App\Models\Cotizacion;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class CotizacionEnviadaAContadorNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Cotizacion $cotizacion,
        protected User $asesor,
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
            ->subject('Nueva Cotización para Revisar')
            ->greeting("Hola {$notifiable->name},")
            ->line("El asesor **{$this->asesor->name}** ha enviado una nueva cotización para su revisión.")
            ->line("**Detalles de la Cotización:**")
            ->line("- **ID**: {$this->cotizacion->id}")
            ->line("- **Cliente**: {$this->cotizacion->cliente_nombre}")
            ->line("- **Valor**: \$" . number_format($this->cotizacion->valor_total ?? 0, 2))
            ->line("- **Estado**: Enviada a Contador")
            ->line("- **Fecha**: " . $this->cotizacion->created_at->format('d/m/Y h:i A'))
            ->action('Revisar Cotización', route('cotizaciones.show', $this->cotizacion->id))
            ->line('Por favor, revise la cotización en el sistema.')
            ->salutation('Saludos cordiales,')
            ->markdown('vendor.notifications.email');
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'titulo' => 'Nueva Cotización de ' . $this->asesor->name,
            'mensaje' => "Cotización #{$this->cotizacion->id} del cliente {$this->cotizacion->cliente_nombre} está lista para revisar",
            'tipo' => 'info',
            'icono' => 'document-text',
            'cotizacion_id' => $this->cotizacion->id,
            'cliente_nombre' => $this->cotizacion->cliente_nombre,
            'valor' => $this->cotizacion->valor_total,
            'asesor' => $this->asesor->name,
            'estado' => 'ENVIADA_CONTADOR',
            'accion_url' => route('cotizaciones.show', $this->cotizacion->id),
            'accion_texto' => 'Ver Cotización',
            'prioridad' => 'alta',
        ]);
    }

    public function databaseType(object $notifiable): string
    {
        return 'cotizacion-enviada-contador';
    }
}
