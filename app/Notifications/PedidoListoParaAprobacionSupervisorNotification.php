<?php

namespace App\Notifications;

use App\Models\PedidoProduccion;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class PedidoListoParaAprobacionSupervisorNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected PedidoProduccion $pedido,
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
            ->subject('Nuevo Pedido de Producción para Aprobación')
            ->greeting("Hola {$notifiable->name},")
            ->line("El asesor **{$this->asesor->name}** ha creado un nuevo pedido de producción que requiere su aprobación.")
            ->line("**Detalles del Pedido:**")
            ->line("- **ID**: {$this->pedido->id}")
            ->line("- **Cliente**: {$this->pedido->cliente_nombre}")
            ->line("- **Valor**: \$" . number_format($this->pedido->valor_total ?? 0, 2))
            ->line("- **Estado**: Pendiente de Supervisor")
            ->line("- **Creado por**: {$this->asesor->name}")
            ->line("- **Fecha**: " . $this->pedido->created_at->format('d/m/Y h:i A'))
            ->action('Revisar Pedido', route('pedidos.show', $this->pedido->id))
            ->line('Por favor, revise y apruebe el pedido para que pueda enviarse a producción.')
            ->salutation('Saludos cordiales,')
            ->markdown('vendor.notifications.email');
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'titulo' => 'Nuevo Pedido de ' . $this->asesor->name,
            'mensaje' => "Pedido #{$this->pedido->id} del cliente {$this->pedido->cliente_nombre} está pendiente de aprobación",
            'tipo' => 'warning',
            'icono' => 'inbox',
            'pedido_id' => $this->pedido->id,
            'cliente_nombre' => $this->pedido->cliente_nombre,
            'valor' => $this->pedido->valor_total,
            'asesor' => $this->asesor->name,
            'estado' => 'PENDIENTE_SUPERVISOR',
            'accion_url' => route('pedidos.show', $this->pedido->id),
            'accion_texto' => 'Ver Pedido',
            'prioridad' => 'alta',
        ]);
    }

    public function databaseType(object $notifiable): string
    {
        return 'pedido-pendiente-supervisor';
    }
}
