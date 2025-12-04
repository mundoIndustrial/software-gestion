<?php

namespace App\Notifications;

use App\Models\PedidoProduccion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class PedidoAprobadoYEnviadoAProduccionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected PedidoProduccion $pedido,
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
            ->subject('Pedido Aprobado y Enviado a Producción')
            ->greeting("Hola {$notifiable->name},")
            ->line("El pedido ha sido aprobado por el supervisor y ha sido asignado un número de producción.")
            ->line("El pedido está siendo enviado al área de producción.")
            ->line("**Detalles del Pedido:**")
            ->line("- **Número de Pedido**: {$this->pedido->numero_pedido}")
            ->line("- **ID**: {$this->pedido->id}")
            ->line("- **Cliente**: {$this->pedido->cliente_nombre}")
            ->line("- **Valor**: \$" . number_format($this->pedido->valor_total ?? 0, 2))
            ->line("- **Estado**: En Producción")
            ->line("- **Fecha de Aprobación**: " . $this->pedido->aprobado_por_supervisor_en?->format('d/m/Y h:i A'))
            ->action('Seguir Pedido', route('pedidos.show', $this->pedido->id))
            ->line('El pedido está en el sistema de producción.')
            ->salutation('Saludos cordiales,')
            ->markdown('vendor.notifications.email');
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'titulo' => 'Pedido Enviado a Producción',
            'mensaje' => "Pedido #{$this->pedido->numero_pedido} del cliente {$this->pedido->cliente_nombre} está en producción",
            'tipo' => 'success',
            'icono' => 'rocket',
            'pedido_id' => $this->pedido->id,
            'cliente_nombre' => $this->pedido->cliente_nombre,
            'valor' => $this->pedido->valor_total,
            'numero_pedido' => $this->pedido->numero_pedido,
            'estado' => 'EN_PRODUCCION',
            'accion_url' => route('pedidos.show', $this->pedido->id),
            'accion_texto' => 'Ver Pedido',
            'prioridad' => 'normal',
        ]);
    }

    public function databaseType(object $notifiable): string
    {
        return 'pedido-en-produccion';
    }
}
