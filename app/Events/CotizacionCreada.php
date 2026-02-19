<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento que se dispara cuando se crea una nueva cotización
 */
class CotizacionCreada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $cotizacionId,
        public int $asesorId,
        public string $estado,
        public array $cotizacionData
    ) {
    }

    /**
     * Canales en los que se transmitirá el evento
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('cotizaciones'),
            new Channel('cotizaciones.asesor.' . $this->asesorId),
            new Channel('cotizaciones.contador'),
        ];
    }

    /**
     * Nombre del evento que se transmitirá
     */
    public function broadcastAs(): string
    {
        return 'cotizacion.creada';
    }

    /**
     * Datos que se transmitirán con el evento
     */
    public function broadcastWith(): array
    {
        return [
            'cotizacion_id' => $this->cotizacionId,
            'asesor_id' => $this->asesorId,
            'estado' => $this->estado,
            'cotizacion' => $this->cotizacionData,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
