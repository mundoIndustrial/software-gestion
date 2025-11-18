<?php

namespace App\Jobs;

use App\Models\OrdenAsesor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConfirmarOrdenJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $ordenId;

    /**
     * Create a new job instance.
     */
    public function __construct($ordenId)
    {
        $this->ordenId = $ordenId;
        $this->queue = 'ordenes'; // Cola específica para órdenes
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $orden = OrdenAsesor::findOrFail($this->ordenId);

            if (!$orden->esBorrador()) {
                Log::warning("Intento de confirmar orden ya confirmada: {$this->ordenId}");
                return;
            }

            // Usar transacción con lock pessimista para evitar race conditions
            DB::transaction(function () use ($orden) {
                // Lock para lectura y actualización
                $orden = OrdenAsesor::lockForUpdate()->find($this->ordenId);

                // Verificar nuevamente después del lock
                if (!$orden->esBorrador()) {
                    Log::warning("Orden ya fue confirmada por otro proceso: {$this->ordenId}");
                    return;
                }

                // Obtener siguiente número de pedido con lock
                $ultimoPedido = DB::table('ordenes_asesores')
                    ->lockForUpdate()
                    ->whereNotNull('pedido')
                    ->max('pedido');

                $siguientePedido = $ultimoPedido ? $ultimoPedido + 1 : 1;

                // Confirmar la orden
                $orden->update([
                    'pedido' => $siguientePedido,
                    'es_borrador' => false,
                    'estado_pedido' => 'confirmado',
                    'fecha_confirmacion' => now(),
                    'estado' => 'en_proceso',
                ]);

                Log::info("Orden confirmada correctamente", [
                    'orden_id' => $this->ordenId,
                    'numero_pedido' => $siguientePedido,
                    'timestamp' => now()
                ]);
            }, attempts: 3); // Reintentar 3 veces si hay deadlock

        } catch (\Throwable $e) {
            Log::error("Error al confirmar orden: {$this->ordenId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Reintentar después de 5 minutos si falla
            if ($this->attempts() < 3) {
                $this->release(300); // Release por 5 minutos
            } else {
                // Fallar permanentemente después de 3 intentos
                $this->fail($e);
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Job de confirmación de orden falló definitivamente", [
            'orden_id' => $this->ordenId,
            'error' => $exception->getMessage()
        ]);
    }
}
