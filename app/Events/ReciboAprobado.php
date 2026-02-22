<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReciboAprobado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $reciboId;
    public int $consecutivo;
    public int $pedidoProduccionId;
    public ?int $prendaId;
    public string $tipoRecibo;
    public string $estado;
    public string $area;
    public string $cliente;
    public ?string $numeroPedido;

    public function __construct(array $data)
    {
        $this->reciboId = $data['recibo_id'];
        $this->consecutivo = $data['consecutivo'];
        $this->pedidoProduccionId = $data['pedido_produccion_id'];
        $this->prendaId = $data['prenda_id'] ?? null;
        $this->tipoRecibo = $data['tipo_recibo'];
        $this->estado = $data['estado'];
        $this->area = $data['area'];
        $this->cliente = $data['cliente'] ?? '';
        $this->numeroPedido = $data['numero_pedido'] ?? null;

        \Log::info('[ReciboAprobado] Evento creado', $data);
    }

    public function broadcastOn()
    {
        return [
            new Channel('recibos-costura'),
        ];
    }

    public function broadcastAs()
    {
        return 'recibo.aprobado';
    }

    public function broadcastWith()
    {
        return [
            'recibo_id' => $this->reciboId,
            'consecutivo' => $this->consecutivo,
            'pedido_produccion_id' => $this->pedidoProduccionId,
            'prenda_id' => $this->prendaId,
            'tipo_recibo' => $this->tipoRecibo,
            'estado' => $this->estado,
            'area' => $this->area,
            'cliente' => $this->cliente,
            'numero_pedido' => $this->numeroPedido,
            'timestamp' => now()->toISOString(),
        ];
    }
}
