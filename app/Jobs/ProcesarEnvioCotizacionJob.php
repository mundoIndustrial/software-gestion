<?php

namespace App\Jobs;

use App\Application\Commands\EnviarCotizacionCommand;
use App\Application\Handlers\EnviarCotizacionHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcesarEnvioCotizacionJob
 *
 * Job para procesar el envío de cotizaciones de forma asincrónica.
 * Se ejecuta en una cola para evitar bloqueos cuando múltiples usuarios
 * envían cotizaciones simultáneamente.
 *
 * Responsabilidad: Procesar el envío de una cotización en background
 */
class ProcesarEnvioCotizacionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * ID de la cotización a enviar
     */
    private int $cotizacionId;

    /**
     * ID del tipo de cotización
     */
    private int $tipoCotizacionId;

    /**
     * Número de intentos
     */
    public int $tries = 3;

    /**
     * Tiempo de espera entre intentos (segundos)
     */
    public int $backoff = 5;

    /**
     * Constructor
     */
    public function __construct(int $cotizacionId, int $tipoCotizacionId)
    {
        $this->cotizacionId = $cotizacionId;
        $this->tipoCotizacionId = $tipoCotizacionId;
    }

    /**
     * Ejecuta el job
     */
    public function handle(EnviarCotizacionHandler $handler): void
    {
        Log::info('🔵 ProcesarEnvioCotizacionJob - Iniciando procesamiento', [
            'cotizacion_id' => $this->cotizacionId,
            'tipo_cotizacion_id' => $this->tipoCotizacionId,
            'job_id' => $this->job->getJobId() ?? 'unknown'
        ]);

        try {
            // Crear y ejecutar el comando
            $command = new EnviarCotizacionCommand(
                $this->cotizacionId,
                $this->tipoCotizacionId
            );

            $cotizacion = $handler->handle($command);

            Log::info('✅ ProcesarEnvioCotizacionJob - Completado exitosamente', [
                'cotizacion_id' => $cotizacion->id,
                'numero_cotizacion' => $cotizacion->numero_cotizacion,
                'job_id' => $this->job->getJobId() ?? 'unknown'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ ProcesarEnvioCotizacionJob - Error al procesar', [
                'cotizacion_id' => $this->cotizacionId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'job_id' => $this->job->getJobId() ?? 'unknown'
            ]);

            // Reintentar si no es la última vez
            if ($this->attempts() < $this->tries) {
                Log::info('🔄 Reintentando envío de cotización', [
                    'cotizacion_id' => $this->cotizacionId,
                    'intento' => $this->attempts() + 1,
                    'max_intentos' => $this->tries
                ]);
                $this->release($this->backoff);
            } else {
                Log::error('❌ ProcesarEnvioCotizacionJob - Máximo de intentos alcanzado', [
                    'cotizacion_id' => $this->cotizacionId,
                    'intentos' => $this->attempts()
                ]);
                $this->fail($e);
            }
        }
    }

    /**
     * Maneja el fallo del job
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('❌ ProcesarEnvioCotizacionJob - Job fallido permanentemente', [
            'cotizacion_id' => $this->cotizacionId,
            'error' => $exception->getMessage(),
            'job_id' => $this->job->getJobId() ?? 'unknown'
        ]);
    }
}
