<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento que se dispara cuando cambia el estado de una cotización
 */
class CotizacionEstadoCambiado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $cotizacionId,
        public string $nuevoEstado,
        public string $estadoAnterior,
        public int $asesorId,
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
        return 'cotizacion.estado.cambiado';
    }

    /**
     * Datos que se transmitirán con el evento
     */
    public function broadcastWith(): array
    {
        return [
            'cotizacion_id' => $this->cotizacionId,
            'nuevo_estado' => $this->nuevoEstado,
            'estado_anterior' => $this->estadoAnterior,
            'asesor_id' => $this->asesorId,
            'cotizacion' => $this->cotizacionData,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
