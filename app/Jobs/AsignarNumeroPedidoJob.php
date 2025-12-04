<?php

namespace App\Jobs;

use App\Models\PedidoProduccion;
use App\Models\User;
use App\Services\PedidoEstadoService;
use App\Notifications\PedidoAprobadoYEnviadoAProduccionNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class AsignarNumeroPedidoJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [10, 30, 60];
    public $timeout = 60;

    public function __construct(
        public PedidoProduccion $pedido
    ) {}

    /**
     * Execute the job - Asignar número de pedido y enviar a producción
     */
    public function handle(PedidoEstadoService $service): void
    {
        try {
            // Refrescar el modelo desde BD
            $this->pedido->refresh();

            Log::info("Iniciando AsignarNumeroPedidoJob", [
                'pedido_id' => $this->pedido->id,
                'estado' => $this->pedido->estado,
            ]);

            // Asignar número de pedido
            $service->asignarNumeroPedido($this->pedido);

            // Cambiar estado a EN_PRODUCCION
            $service->enviarAProduccion($this->pedido);

            // Notificar a asesor que pidió el pedido
            $asesor = $this->pedido->createdBy ?? User::where('rol', 'asesor')->first();
            if ($asesor) {
                Notification::send($asesor, new PedidoAprobadoYEnviadoAProduccionNotification($this->pedido));
            }

            // Notificar a supervisores
            $supervisores = User::where('rol', 'supervisor_produccion')->get();
            foreach ($supervisores as $supervisor) {
                Notification::send($supervisor, new PedidoAprobadoYEnviadoAProduccionNotification($this->pedido));
            }

            Log::info("AsignarNumeroPedidoJob completado", [
                'pedido_id' => $this->pedido->id,
                'numero_pedido' => $this->pedido->numero_pedido,
                'usuarios_notificados' => ($asesor ? 1 : 0) + $supervisores->count(),
            ]);
        } catch (\Exception $e) {
            Log::error("Error en AsignarNumeroPedidoJob: " . $e->getMessage(), [
                'pedido_id' => $this->pedido->id,
                'exception' => $e,
            ]);
            throw $e;
        }
    }
}
