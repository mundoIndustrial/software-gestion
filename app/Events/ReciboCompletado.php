<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReciboCompletado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $reciboId;
    public string $consecutivo;
    public int $pedidoProduccionId;
    public ?int $prendaId;
    public string $tipoRecibo;
    public string $area;
    public string $nombreOperario;

    public function __construct(array $data)
    {
        $this->reciboId = (int) ($data['recibo_id'] ?? 0);
        $this->consecutivo = (string) ($data['consecutivo'] ?? '');
        $this->pedidoProduccionId = (int) ($data['pedido_produccion_id'] ?? 0);
        $this->prendaId = isset($data['prenda_id']) ? (int) $data['prenda_id'] : null;
        $this->tipoRecibo = (string) ($data['tipo_recibo'] ?? '');
        $this->area = (string) ($data['area'] ?? '');
        $this->nombreOperario = (string) ($data['nombre_operario'] ?? '');

        \Log::info('[ReciboCompletado] Evento creado', $data);
    }

    public function broadcastOn()
    {
        return [
            new Channel('recibos-costura'),
        ];
    }

    public function broadcastAs()
    {
        return 'recibo.completado';
    }

    public function broadcastWith()
    {
        \Log::info('Broadcasting ReciboCompletado event', [
            'recibo_id' => $this->reciboId,
            'consecutivo' => $this->consecutivo,
            'pedido_id' => $this->pedidoProduccionId,
            'area' => $this->area,
        ]);

        return [
            'recibo_id' => $this->reciboId,
            'consecutivo' => $this->consecutivo,
            'pedido_produccion_id' => $this->pedidoProduccionId,
            'prenda_id' => $this->prendaId,
            'tipo_recibo' => $this->tipoRecibo,
            'area' => $this->area,
            'nombre_operario' => $this->nombreOperario,
            'timestamp' => now()->toISOString(),
        ];
    }
}
