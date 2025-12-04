<?php

namespace App\Jobs;

use App\Models\Cotizacion;
use App\Models\User;
use App\Services\CotizacionEstadoService;
use App\Notifications\CotizacionListaParaAprobacionNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class EnviarCotizacionAAprobadorJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [10, 30, 60];
    public $timeout = 60;

    public function __construct(
        public Cotizacion $cotizacion
    ) {}

    /**
     * Execute the job - Cambiar estado a APROBADA_COTIZACIONES y notificar a Aprobador
     */
    public function handle(CotizacionEstadoService $service): void
    {
        try {
            // Refrescar el modelo desde BD
            $this->cotizacion->refresh();

            Log::info("Iniciando EnviarCotizacionAAprobadorJob", [
                'cotizacion_id' => $this->cotizacion->id,
                'numero_cotizacion' => $this->cotizacion->numero_cotizacion,
            ]);

            // Cambiar estado a APROBADA_COTIZACIONES
            $service->aprobarComoAprobador($this->cotizacion);

            // Obtener contador que aprobó
            $contador = $this->cotizacion->aprobadoPorContador ?? User::find(1); // fallback

            // Obtener todos los usuarios con rol "aprobador_cotizaciones"
            $aprobadores = User::where('rol', 'aprobador_cotizaciones')->get();

            // Enviar notificación a cada aprobador
            foreach ($aprobadores as $aprobador) {
                Notification::send($aprobador, new CotizacionListaParaAprobacionNotification($this->cotizacion, $contador));
            }

            Log::info("Notificación enviada a aprobadores de cotizaciones", [
                'cotizacion_id' => $this->cotizacion->id,
                'numero_cotizacion' => $this->cotizacion->numero_cotizacion,
                'aprobadores_notificados' => $aprobadores->count(),
                'cliente' => $this->cotizacion->cliente,
            ]);
        } catch (\Exception $e) {
            Log::error("Error en EnviarCotizacionAAprobadorJob: " . $e->getMessage(), [
                'cotizacion_id' => $this->cotizacion->id,
                'exception' => $e,
            ]);
            throw $e;
        }
    }
}
