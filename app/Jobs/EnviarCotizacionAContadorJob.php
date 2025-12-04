<?php

namespace App\Jobs;

use App\Models\Cotizacion;
use App\Models\User;
use App\Notifications\CotizacionEnviadaAContadorNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class EnviarCotizacionAContadorJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [10, 30, 60];
    public $timeout = 60;

    public function __construct(
        public Cotizacion $cotizacion
    ) {}

    /**
     * Execute the job - Notificar a Contador que hay cotización para revisar
     */
    public function handle(): void
    {
        try {
            // Refrescar el modelo desde BD
            $this->cotizacion->refresh();

            Log::info("Iniciando EnviarCotizacionAContadorJob", [
                'cotizacion_id' => $this->cotizacion->id,
                'cliente' => $this->cotizacion->cliente,
            ]);

            // Obtener asesor que creó la cotización
            $asesor = $this->cotizacion->createdBy ?? User::find(1); // fallback a usuario 1

            // Obtener todos los usuarios con rol "contador"
            $contadores = User::where('rol', 'contador')->get();

            // Enviar notificación a cada contador
            foreach ($contadores as $contador) {
                Notification::send($contador, new CotizacionEnviadaAContadorNotification($this->cotizacion, $asesor));
            }

            Log::info("Notificación enviada a contadores", [
                'cotizacion_id' => $this->cotizacion->id,
                'contadores_notificados' => $contadores->count(),
                'cliente' => $this->cotizacion->cliente,
            ]);
        } catch (\Exception $e) {
            Log::error("Error en EnviarCotizacionAContadorJob: " . $e->getMessage(), [
                'cotizacion_id' => $this->cotizacion->id,
                'exception' => $e,
            ]);
            throw $e;
        }
    }
}
