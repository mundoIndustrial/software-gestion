<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReciboFijadoActualizado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $encargado;
    public ?int $idRecibo;
    public string $accion; // 'fijar' | 'limpiar'

    public function __construct(string $encargado, ?int $idRecibo, string $accion)
    {
        $this->encargado = $encargado;
        $this->idRecibo = $idRecibo;
        $this->accion = $accion;
    }

    public function broadcastOn()
    {
        return [new Channel('tableros-ordenes')];
    }

    public function broadcastAs(): string
    {
        return 'recibo.fijado.actualizado';
    }

    public function broadcastWith(): array
    {
        return [
            'encargado' => $this->encargado,
            'id_recibo' => $this->idRecibo,
            'accion' => $this->accion,
        ];
    }
}
