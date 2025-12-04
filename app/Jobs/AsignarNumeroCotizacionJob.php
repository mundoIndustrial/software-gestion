<?php

namespace App\Jobs;

use App\Models\Cotizacion;
use App\Services\CotizacionEstadoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AsignarNumeroCotizacionJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [10, 30, 60];
    public $timeout = 60;

    public function __construct(
        public Cotizacion $cotizacion
    ) {}

    /**
     * Execute the job
     */
    public function handle(CotizacionEstadoService $service): void
    {
        try {
            // Refrescar el modelo desde BD
            $this->cotizacion->refresh();

            Log::info("Iniciando AsignarNumeroCotizacionJob", [
                'cotizacion_id' => $this->cotizacion->id,
                'estado' => $this->cotizacion->estado,
            ]);

            // Asignar número de cotización
            $service->asignarNumeroCotizacion($this->cotizacion);

            // Enviar a Aprobador de Cotizaciones
            dispatch(new EnviarCotizacionAAprobadorJob($this->cotizacion));

            Log::info("AsignarNumeroCotizacionJob completado", [
                'cotizacion_id' => $this->cotizacion->id,
                'numero_cotizacion' => $this->cotizacion->numero_cotizacion,
            ]);
        } catch (\Exception $e) {
            Log::error("Error en AsignarNumeroCotizacionJob: " . $e->getMessage(), [
                'cotizacion_id' => $this->cotizacion->id,
                'exception' => $e,
            ]);
            throw $e;
        }
    }
}
