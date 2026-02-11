<?php

namespace App\Jobs;

use App\Events\PedidoCreado;
use App\Models\PedidoProduccion;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class BroadcastPedidoCreado implements ShouldQueue
{
    use Queueable;

    public $timeout = 60;
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private int $pedidoId,
        private int $asesorId
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('[BroadcastPedidoCreado] Iniciando broadcast', [
                'pedido_id' => $this->pedidoId,
                'asesor_id' => $this->asesorId,
            ]);

            // Obtener pedido y asesor frescos de la BD
            $pedido = PedidoProduccion::find($this->pedidoId);
            $asesor = User::find($this->asesorId);

            if (!$pedido || !$asesor) {
                Log::warning('[BroadcastPedidoCreado] Pedido o asesor no encontrado', [
                    'pedido_id' => $this->pedidoId,
                    'asesor_id' => $this->asesorId,
                    'pedido_exists' => !!$pedido,
                    'asesor_exists' => !!$asesor,
                ]);
                return;
            }

            // Disparar evento desde la cola
            PedidoCreado::dispatch($pedido, $asesor);

            Log::info('[BroadcastPedidoCreado]  Event broadcasted exitosamente', [
                'pedido_id' => $this->pedidoId,
                'numero_pedido' => $pedido->numero_pedido,
                'asesor_id' => $asesor->id,
            ]);
        } catch (\Exception $e) {
            Log::error('[BroadcastPedidoCreado] Error al hacer broadcast', [
                'pedido_id' => $this->pedidoId,
                'asesor_id' => $this->asesorId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // Re-lanzar para que la cola lo reintente
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[BroadcastPedidoCreado] Job falló después de ' . $this->tries . ' intentos', [
            'pedido_id' => $this->pedidoId,
            'asesor_id' => $this->asesorId,
            'error' => $exception->getMessage(),
        ]);
    }
}
