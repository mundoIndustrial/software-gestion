<?php

namespace App\Notifications;

use App\Models\Cotizacion;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class CotizacionListaParaAprobacionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Cotizacion $cotizacion,
        protected User $contador,
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
            ->subject('Cotización Aprobada por Contador - Requiere Aprobación Final')
            ->greeting("Hola {$notifiable->name},")
            ->line("La cotización ha sido revisada y aprobada por contador **{$this->contador->name}**.")
            ->line("Ahora requiere su aprobación final como **Aprobador de Cotizaciones**.")
            ->line("**Detalles de la Cotización:**")
            ->line("- **ID**: {$this->cotizacion->id}")
            ->line("- **Número**: {$this->cotizacion->numero_cotizacion}")
            ->line("- **Cliente**: {$this->cotizacion->cliente_nombre}")
            ->line("- **Valor**: \$" . number_format($this->cotizacion->valor_total ?? 0, 2))
            ->line("- **Revisado por**: {$this->contador->name}")
            ->line("- **Fecha de Revisión**: " . $this->cotizacion->aprobada_por_contador_en?->format('d/m/Y h:i A'))
            ->action('Aprobar o Rechazar', route('cotizaciones.show', $this->cotizacion->id))
            ->line('Por favor, revise y apruebe la cotización.')
            ->salutation('Saludos cordiales,')
            ->markdown('vendor.notifications.email');
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'titulo' => 'Cotización Lista para Aprobación',
            'mensaje' => "Cotización #{$this->cotizacion->numero_cotizacion} del cliente {$this->cotizacion->cliente_nombre} está lista para aprobación final",
            'tipo' => 'success',
            'icono' => 'check-circle',
            'cotizacion_id' => $this->cotizacion->id,
            'cliente_nombre' => $this->cotizacion->cliente_nombre,
            'valor' => $this->cotizacion->valor_total,
            'numero_cotizacion' => $this->cotizacion->numero_cotizacion,
            'contador_nombre' => $this->contador->name,
            'estado' => 'APROBADA_CONTADOR',
            'accion_url' => route('cotizaciones.show', $this->cotizacion->id),
            'accion_texto' => 'Ver Cotización',
            'prioridad' => 'normal',
        ]);
    }

    public function databaseType(object $notifiable): string
    {
        return 'cotizacion-lista-aprobacion';
    }
}
